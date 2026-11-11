<?php
/**
 * WorldCup Journey - CSV 导入真实数据库回归
 * 模块：B；负责人：B；依赖：C 的 install.sql、MatchDataImportService、MySQL
 * 最近修改：2026-11-11
 * 运行：php tests/integration/match-data-import.php
 */
declare(strict_types=1);

use app\services\MatchDataImportService;

$root = dirname(__DIR__, 2);
require $root . '/vendor/autoload.php';
require $root . '/vendor/yiisoft/yii2/Yii.php';
defined('YII_ENV') || define('YII_ENV', 'test');
defined('YII_DEBUG') || define('YII_DEBUG', false);
$config = require $root . '/config/db.php';
$pdo = new PDO($config['dsn'], $config['username'], $config['password'], [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
$database = 'wcj_import_test_' . bin2hex(random_bytes(6));
$directory = $root . '/runtime/' . $database;
$created = false;
$tables = ['tournament' => 1, 'national_team' => 48, 'tournament_team' => 48, 'stadium' => 16,
    'wc_match' => 104, 'match_story' => 8, 'match_tag' => 16, 'match_story_tag' => 27, 'match_event' => 104];
$assertions = 0;
$check = static function (bool $condition, string $name) use (&$assertions): void {
    if (!$condition) {
        throw new RuntimeException('FAIL ' . $name);
    }
    ++$assertions;
};
$snapshot = static function () use ($pdo, $tables): string {
    $data = [];
    foreach ($tables as $table => $count) {
        $rows = $pdo->query('SELECT * FROM ' . $table)->fetchAll(PDO::FETCH_ASSOC);
        $encoded = array_map('json_encode', $rows);
        sort($encoded);
        $data[$table] = $encoded;
    }
    return hash('sha256', json_encode($data));
};
$editEvent = static function (string $teamCode) use ($directory): void {
    $file = $directory . '/events.csv';
    $handle = fopen($file, 'rb');
    $rows = [];
    while (($row = fgetcsv($handle, 0, ',', '"', '')) !== false) {
        $rows[] = $row;
    }
    fclose($handle);
    $rows[count($rows) - 1][1] = $teamCode;
    $handle = fopen($file, 'wb');
    foreach ($rows as $row) {
        fputcsv($handle, $row, ',', '"', '', "\r\n");
    }
    fclose($handle);
};

try {
    if (!preg_match('/^wcj_import_test_[a-f0-9]{12}$/D', $database)) {
        throw new RuntimeException('Invalid isolated database name');
    }
    $pdo->exec('CREATE DATABASE ' . $database . ' CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci');
    $created = true;
    $pdo->exec('USE ' . $database);
    $pdo->exec(file_get_contents($root . '/data/install.sql'));
    $config['dsn'] = preg_replace('/dbname=[^;]+/', 'dbname=' . $database, $config['dsn']);
    new yii\console\Application(['id' => 'import-regression', 'basePath' => $root, 'components' => ['db' => $config]]);
    mkdir($directory);
    foreach (glob($root . '/data/import/*.csv') as $file) {
        copy($file, $directory . '/' . basename($file));
    }
    $service = new MatchDataImportService();
    $empty = $snapshot();
    $editEvent('ZZZ');
    $failed = false;
    try {
        $service->import($directory);
    } catch (RuntimeException $exception) {
        $failed = str_contains($exception->getMessage(), 'events.csv');
    }
    $check($failed, 'late invalid team has record context');
    $check($snapshot() === $empty, 'late failure rolls back all business tables');
    copy($root . '/data/import/events.csv', $directory . '/events.csv');
    $trial = $service->import($directory, true);
    $check($trial['dry_run'] === true, 'dry run result');
    $check($snapshot() === $empty, 'dry run rolls back writes');
    $result = $service->import($directory);
    foreach ($tables as $table => $expected) {
        $check((int) $pdo->query('SELECT COUNT(*) FROM ' . $table)->fetchColumn() === $expected, 'row count ' . $table);
        $check($result['tables'][$table]['inserted'] === $expected, 'insert report ' . $table);
    }
    $check((int) $pdo->query('SELECT COUNT(*) FROM match_story WHERE is_published<>0')->fetchColumn() === 0, 'all stories remain drafts');
    $check((int) $pdo->query("SELECT COUNT(*) FROM wc_match WHERE stage_code='GROUP'")->fetchColumn() === 72, '72 group matches');
    $check((int) $pdo->query("SELECT COUNT(*) FROM wc_match WHERE stage_code<>'GROUP'")->fetchColumn() === 32, '32 knockout matches');
    $check((int) $pdo->query('SELECT COUNT(*) FROM wc_match m JOIN tournament t ON t.id=m.tournament_id WHERE t.year=2026')->fetchColumn() === 104, 'real tournament ID mapping');
    $check($pdo->query('SELECT created_at FROM match_story LIMIT 1')->fetchColumn() !== false, 'database timestamp defaults');
    $check((int) $pdo->query('SELECT COUNT(*) FROM match_event e JOIN wc_match m ON m.id=e.match_id WHERE e.team_id IS NOT NULL AND e.team_id NOT IN (m.home_team_id,m.away_team_id)')->fetchColumn() === 0, 'event team membership');
    $first = $snapshot();
    $repeat = $service->import($directory);
    foreach ($tables as $table => $expected) {
        $check($repeat['tables'][$table] === ['inserted' => 0, 'skipped' => $expected], 'idempotent report ' . $table);
    }
    $check($snapshot() === $first, 'repeat preserves every value and timestamp');
    $pdo->exec('UPDATE match_story SET is_published=1 ORDER BY id LIMIT 1');
    $pdo->exec("UPDATE tournament_team SET final_result_code='GROUP' ORDER BY team_id LIMIT 1");
    $edited = $snapshot();
    $service->import($directory);
    $check($snapshot() === $edited, 'repeat preserves publication and reliable final result');
    $pdo->exec("UPDATE wc_match SET summary='TEST ONLY manual edit' WHERE match_no=1");
    $edited = $snapshot();
    $failed = false;
    try {
        $service->import($directory);
    } catch (RuntimeException $exception) {
        $failed = str_contains($exception->getMessage(), 'matches.csv');
    }
    $check($failed, 'existing content conflict reported');
    $check($snapshot() === $edited, 'conflict preserves manual edits');
    echo "PASS {$assertions} real database import assertions\n";
} finally {
    if (Yii::$app !== null && Yii::$app->has('db')) {
        Yii::$app->db->close();
    }
    if ($created) {
        $pdo->exec('USE information_schema');
        $pdo->exec('DROP DATABASE ' . $database);
        echo "Temporary import database removed\n";
    }
    if (is_dir($directory)) {
        foreach (glob($directory . '/*.csv') as $file) {
            unlink($file);
        }
        rmdir($directory);
    }
}
