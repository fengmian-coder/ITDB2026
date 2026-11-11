<?php
/**
 * WorldCup Journey - CSV 导入真实数据库回归
 * 模块：B；负责人：B；依赖：C 的 install.sql、MatchDataImportService、MySQL、curl、DOM
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
$process = null;
$client = null;
$router = $directory . '/router.php';
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
$editCsvCell = static function (string $name, int $column, string $value) use ($directory): void {
    $file = $directory . '/' . $name;
    $handle = fopen($file, 'rb');
    $rows = [];
    while (($row = fgetcsv($handle, 0, ',', '"', '')) !== false) {
        $rows[] = $row;
    }
    fclose($handle);
    $rows[count($rows) - 1][$column] = $value;
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
    foreach (['2', '-1', '1.5', 'true'] as $value) {
        $editCsvCell('stories.csv', 6, $value);
        $failed = false;
        try {
            $service->import($directory);
        } catch (RuntimeException $exception) {
            $failed = str_contains($exception->getMessage(), 'stories.csv');
        }
        $check($failed, 'invalid publication rejected ' . $value);
        $check($snapshot() === $empty, 'invalid publication preserves empty database');
    }
    copy($root . '/data/import/stories.csv', $directory . '/stories.csv');
    $editCsvCell('events.csv', 1, 'ZZZ');
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
    $check((int) $pdo->query('SELECT COUNT(*) FROM match_story WHERE is_published=1')->fetchColumn() === 8, 'all eight publication states restored');
    $check((int) $pdo->query("SELECT COUNT(*) FROM wc_match WHERE stage_code='GROUP'")->fetchColumn() === 72, '72 group matches');
    $check((int) $pdo->query("SELECT COUNT(*) FROM wc_match WHERE stage_code<>'GROUP'")->fetchColumn() === 32, '32 knockout matches');
    $check((int) $pdo->query('SELECT COUNT(*) FROM wc_match m JOIN tournament t ON t.id=m.tournament_id WHERE t.year=2026')->fetchColumn() === 104, 'real tournament ID mapping');
    $check($pdo->query('SELECT created_at FROM match_story LIMIT 1')->fetchColumn() !== false, 'database timestamp defaults');
    $check((int) $pdo->query('SELECT COUNT(*) FROM match_event e JOIN wc_match m ON m.id=e.match_id WHERE e.team_id IS NOT NULL AND e.team_id NOT IN (m.home_team_id,m.away_team_id)')->fetchColumn() === 0, 'event team membership');

    // 使用刚还原的独立数据库检查真实前台，数据包中的业务编号映射为本库主键。
    $socket = stream_socket_server('tcp://127.0.0.1:0');
    $port = (int) substr(strrchr(stream_socket_get_name($socket, false), ':'), 1);
    fclose($socket);
    $routerCode = '<?php define("YII_ENV", "test"); define("YII_DEBUG", false);'
        . 'require ' . var_export($root . '/vendor/autoload.php', true) . ';'
        . 'require ' . var_export($root . '/vendor/yiisoft/yii2/Yii.php', true) . ';'
        . '$config=require ' . var_export($root . '/config/web.php', true) . ';'
        . '$config["components"]["db"]["dsn"]=' . var_export($config['dsn'], true) . ';'
        . '(new yii\\web\\Application($config))->run();';
    file_put_contents($router, $routerCode);
    $log = $root . '/runtime/' . $database . '-server.log';
    $process = proc_open([PHP_BINARY, '-S', '127.0.0.1:' . $port, '-t', $root . '/web', $router],
        [0 => ['pipe', 'r'], 1 => ['file', $log, 'a'], 2 => ['file', $log, 'a']], $pipes, $root,
        null, ['bypass_shell' => true]);
    if (!is_resource($process)) {
        throw new RuntimeException('Import preview server failed to start');
    }
    fclose($pipes[0]);
    $ready = false;
    for ($attempt = 0; $attempt < 50; ++$attempt) {
        $probe = @fsockopen('127.0.0.1', $port);
        if ($probe) {
            fclose($probe);
            $ready = true;
            break;
        }
        usleep(100000);
    }
    $check($ready, 'restored dataset HTTP server ready');
    $client = curl_init();
    curl_setopt_array($client, [CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 15]);
    $sourceHandle = fopen($directory . '/stories.csv', 'rb');
    $sourceHeader = fgetcsv($sourceHandle, 0, ',', '"', '');
    $sourceStories = [];
    while (($values = fgetcsv($sourceHandle, 0, ',', '"', '')) !== false) {
        $sourceStories[] = array_combine($sourceHeader, $values);
    }
    fclose($sourceHandle);
    $queryService = new app\services\MatchQueryService();
    $publicEvents = 0;
    $publicTags = 0;
    foreach ($sourceStories as $source) {
        $statement = $pdo->prepare('SELECT s.* FROM match_story s JOIN wc_match m ON m.id=s.match_id'
            . ' JOIN tournament t ON t.id=m.tournament_id WHERE t.year=2026 AND m.match_no=:number');
        $statement->execute(['number' => $source['match_no']]);
        $stored = $statement->fetch(PDO::FETCH_ASSOC);
        $check($stored !== false && $stored['title'] === $source['title']
            && $stored['summary'] === $source['summary'] && $stored['body'] === $source['body'],
            'published text restored ' . $source['match_no']);
        $detail = $queryService->getMatchDetail((int) $stored['match_id']);
        $check($detail['story'] !== null && $detail['story']['is_published'] === true,
            'public service sees restored story ' . $source['match_no']);
        curl_setopt($client, CURLOPT_URL, 'http://127.0.0.1:' . $port . '/index.php?r=wc-match/view&id=' . $stored['match_id']);
        $html = curl_exec($client);
        if ($html === false) {
            throw new RuntimeException('Restored public page request failed');
        }
        $doc = new DOMDocument();
        $previous = libxml_use_internal_errors(true);
        $doc->loadHTML('<?xml encoding="UTF-8">' . $html);
        libxml_clear_errors();
        libxml_use_internal_errors($previous);
        $xpath = new DOMXPath($doc);
        $body = $xpath->query('//div[contains(concat(" ",normalize-space(@class)," ")," wc-story-body ")]')->item(0);
        $eventCount = $xpath->query('//li[contains(concat(" ",normalize-space(@class)," ")," wc-event ")]')->length;
        $tagCount = $xpath->query('//article[@aria-labelledby="match-story-title"]//ul/li')->length;
        $check((int) curl_getinfo($client, CURLINFO_RESPONSE_CODE) === 200 && $body !== null
            && trim($body->textContent) === trim($source['body']), 'public story text ' . $source['match_no']);
        $check($eventCount === count($detail['events']) && $tagCount === count($detail['tags']),
            'public timeline and tags ' . $source['match_no']);
        $publicEvents += $eventCount;
        $publicTags += $tagCount;
    }
    $check($publicEvents === 104 && $publicTags === 27, 'restored pages show 104 events and 27 tag associations');
    $first = $snapshot();
    $repeat = $service->import($directory);
    foreach ($tables as $table => $expected) {
        $check($repeat['tables'][$table] === ['inserted' => 0, 'skipped' => $expected], 'idempotent report ' . $table);
    }
    $check($snapshot() === $first, 'repeat preserves every value and timestamp');
    $pdo->exec('UPDATE match_story SET is_published=0 ORDER BY id LIMIT 1');
    $pdo->exec("UPDATE match_story SET cover_path='images/stories/test-only-cover.jpg' ORDER BY id LIMIT 1");
    $pdo->exec("UPDATE tournament_team SET final_result_code='GROUP' ORDER BY team_id LIMIT 1");
    $edited = $snapshot();
    $service->import($directory);
    $check($snapshot() === $edited, 'repeat preserves withdrawal, cover and reliable final result');
    $withdrawnMatch = (int) $pdo->query('SELECT match_id FROM match_story WHERE is_published=0')->fetchColumn();
    $withdrawn = $queryService->getMatchDetail($withdrawnMatch);
    $check($withdrawn['story'] === null && $withdrawn['events'] === [] && $withdrawn['tags'] === [],
        'withdrawn story stays hidden after repeat');
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
    if ($client !== null) {
        curl_close($client);
    }
    if (is_resource($process)) {
        proc_terminate($process);
        proc_close($process);
    }
    if (Yii::$app !== null && Yii::$app->has('db')) {
        Yii::$app->db->close();
    }
    if ($created) {
        $pdo->exec('USE information_schema');
        $pdo->exec('DROP DATABASE ' . $database);
        echo "Temporary import database removed\n";
    }
    if (is_dir($directory)) {
        if (is_file($router)) {
            unlink($router);
        }
        foreach (glob($directory . '/*.csv') as $file) {
            unlink($file);
        }
        rmdir($directory);
    }
}
