<?php
/**
 * WorldCup Journey - C/D 空库还原与重复部署真实回归
 * 模块：C/D；负责人：B 协助；依赖：MySQL、PHP 8.2、C 建表文件及统一数据包
 * 最近修改：2026-11-11
 * 运行：php tests/integration/deployment.php，结束仅删除本次随机生成的测试库。
 */
declare(strict_types=1);

$root = dirname(__DIR__, 2);
require $root . '/vendor/autoload.php';
defined('YII_ENV') || define('YII_ENV', 'test');
defined('YII_DEBUG') || define('YII_DEBUG', false);
require $root . '/vendor/yiisoft/yii2/Yii.php';
$config = require $root . '/config/db.php';
$name = 'wcj_deploy_test_' . bin2hex(random_bytes(6));
$recovery = $name . '_recovery';
$directory = $root . '/runtime/' . $name;
$dsn = preg_replace('/dbname=[^;]+/', 'dbname=' . $name, $config['dsn']);
$pdo = new PDO($config['dsn'], $config['username'], $config['password'], [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
$assertions = 0;
$server = null;
$check = static function (bool $condition, string $label) use (&$assertions): void {
    if (!$condition) { throw new RuntimeException('FAIL ' . $label); }
    ++$assertions;
};
$run = static function (array $command, string $input = '') use ($root): array {
    $process = proc_open($command, [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes, $root,
        null, ['bypass_shell' => true]);
    if (!is_resource($process)) { throw new RuntimeException('Unable to start deployment test'); }
    fwrite($pipes[0], $input);
    fclose($pipes[0]);
    $output = stream_get_contents($pipes[1]);
    $errors = stream_get_contents($pipes[2]);
    fclose($pipes[1]); fclose($pipes[2]);
    return [proc_close($process), $output, $errors];
};
try {
    if (!preg_match('/^wcj_deploy_test_[a-f0-9]{12}$/D', $name)) { throw new RuntimeException('Unsafe test database name'); }
    mkdir($directory);
    $configurationRoot = $directory . '/first-run';
    mkdir($configurationRoot); mkdir($configurationRoot . '/scripts'); mkdir($configurationRoot . '/config');
    $configure = $configurationRoot . '/scripts/configure-local.php';
    copy($root . '/scripts/configure-local.php', $configure);
    $check($run([PHP_BINARY, $configure, '--check'])[0] === 2, 'missing local config requests first setup');
    $testConfig = ['host' => '127.0.0.1', 'port' => 3306, 'database' => 'test_only_config',
        'username' => 'test_only_user', 'password' => 'TEST ONLY 中文 \"quoted\" $value'];
    $check($run([PHP_BINARY, $configure], json_encode($testConfig))[0] === 0, 'first setup saves valid config');
    $local = require $configurationRoot . '/config/db.local.php';
    $check($local['password'] === $testConfig['password'] && $local['username'] === $testConfig['username'], 'special characters preserved in private config');
    $hash = hash_file('sha256', $configurationRoot . '/config/db.local.php');
    $check($run([PHP_BINARY, $configure, '--check'])[0] === 0, 'existing config detected');
    $check($run([PHP_BINARY, $configure], '{}')[0] === 0
        && hash_file('sha256', $configurationRoot . '/config/db.local.php') === $hash, 'existing credentials retained');
    unlink($configurationRoot . '/config/db.local.php');
    $testConfig['database'] = 'invalid;dbname';
    $check($run([PHP_BINARY, $configure], json_encode($testConfig))[0] === 1
        && !is_file($configurationRoot . '/config/db.local.php'), 'unsafe config rejected before write');
    $dbConfig = $config;
    $dbConfig['dsn'] = $dsn;
    $console = $directory . '/console.php';
    $bootstrap = 'require ' . var_export($root . '/vendor/autoload.php', true) . '; require '
        . var_export($root . '/vendor/yiisoft/yii2/Yii.php', true) . ';';
    file_put_contents($console, '<?php define("YII_ENV", "prod"); define("YII_DEBUG", false);' . $bootstrap
        . '$config=require ' . var_export($root . '/config/console.php', true) . ';'
        . '$config["components"]["db"]["dsn"]=' . var_export($dsn, true) . ';'
        . '$config["runtimePath"]=' . var_export($directory, true) . ';'
        . '$app=new yii\console\Application($config); exit($app->runAction("deploy/prepare"));');
    [$status, $output, $error] = $run([PHP_BINARY, $console]);
    $check($status === 0, 'fresh deploy command ' . $error);
    $pdo->exec('USE `' . $name . '`');
    $check(count($pdo->query('SHOW TABLES')->fetchAll()) === 15, '15 C tables restored');
    foreach (['national_team' => 48, 'tournament_team' => 48, 'wc_match' => 104, 'match_story' => 8,
        'admin_user' => 1, 'project_team' => 1, 'project_member' => 4, 'project_document' => 6] as $table => $count) {
        $check((int) $pdo->query('SELECT COUNT(*) FROM ' . $table)->fetchColumn() === $count, 'deployment row count ' . $table);
    }
    $check((int) $pdo->query('SELECT COUNT(*) FROM tournament_team WHERE final_result_code IS NULL')->fetchColumn() === 0, 'final results restored');
    $check((int) $pdo->query('SELECT COUNT(*) FROM match_story WHERE is_published=1')->fetchColumn() === 8, 'published stories restored');
    preg_match('/Generated password \(shown once\): (\S+)/', $output, $credential);
    $admin = $pdo->query('SELECT username,password_hash FROM admin_user')->fetch(PDO::FETCH_ASSOC);
    $check($admin['username'] === 'wcj_admin' && isset($credential[1])
        && password_verify($credential[1], $admin['password_hash']), 'generated local administrator credential');
    unset($credential, $output);
    $snapshot = static function () use ($pdo): string {
        $data = [];
        foreach ($pdo->query('SHOW TABLES')->fetchAll(PDO::FETCH_COLUMN) as $table) {
            $rows = array_map('json_encode', $pdo->query('SELECT * FROM ' . $table)->fetchAll(PDO::FETCH_ASSOC));
            sort($rows); $data[$table] = $rows;
        }
        return hash('sha256', json_encode($data));
    };
    $before = $snapshot();
    [$status, $output, $error] = $run([PHP_BINARY, $console]);
    $check($status === 0, 'repeat deploy command ' . $error);
    $check($snapshot() === $before, 'repeat keeps every value, timestamp, administrator and document');
    $check(!str_contains($output, 'Generated password'), 'repeat preserves existing administrator');

    // HTTP 直接使用还原库，验证公开页面、资料下载及后台访问控制。
    $socket = stream_socket_server('tcp://127.0.0.1:0');
    $port = (int) substr(strrchr(stream_socket_get_name($socket, false), ':'), 1);
    fclose($socket);
    $router = $directory . '/router.php';
    file_put_contents($router, '<?php define("YII_ENV", "prod"); define("YII_DEBUG", false);' . $bootstrap
        . '$config=require ' . var_export($root . '/config/web.php', true) . ';'
        . '$config["components"]["db"]["dsn"]=' . var_export($dsn, true) . ';'
        . '(new yii\web\Application($config))->run();');
    $server = proc_open([PHP_BINARY, '-S', '127.0.0.1:' . $port, '-t', $root . '/web', $router],
        [0 => ['pipe', 'r'], 1 => ['file', $directory . '/server.log', 'a'], 2 => ['file', $directory . '/server.log', 'a']], $pipes, $root,
        null, ['bypass_shell' => true]);
    if (!is_resource($server)) { throw new RuntimeException('Preview server failed'); }
    fclose($pipes[0]);
    for ($attempt = 0; $attempt < 50; ++$attempt) {
        $socket = @fsockopen('127.0.0.1', $port);
        if ($socket) { fclose($socket); break; }
        usleep(100000);
    }
    $client = curl_init();
    curl_setopt_array($client, [CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 15]);
    foreach (['site/index', 'national-team/index', 'national-team/compare', 'insight/index',
        'wc-match/index', 'document/index', 'auth/login'] as $route) {
        curl_setopt($client, CURLOPT_URL, 'http://127.0.0.1:' . $port . '/index.php?r=' . $route);
        $html = curl_exec($client);
        $check($html !== false && (int) curl_getinfo($client, CURLINFO_RESPONSE_CODE) === 200, 'restored public route ' . $route);
    }
    $champion = (int) $pdo->query("SELECT id FROM national_team WHERE fifa_code='ESP'")->fetchColumn();
    $runnerUp = (int) $pdo->query("SELECT id FROM national_team WHERE fifa_code='ARG'")->fetchColumn();
    foreach (['national-team/view&id=' . $champion, 'national-team/compare&left_id=' . $champion . '&right_id=' . $runnerUp] as $route) {
        curl_setopt($client, CURLOPT_URL, 'http://127.0.0.1:' . $port . '/index.php?r=' . $route);
        $html = curl_exec($client);
        $check($html !== false && (int) curl_getinfo($client, CURLINFO_RESPONSE_CODE) === 200 && str_contains($html, '冠军'),
            'restored sourced final result displayed ' . $route);
    }
    curl_setopt($client, CURLOPT_URL, 'http://127.0.0.1:' . $port . '/index.php?r=admin-story/index');
    curl_exec($client);
    $check((int) curl_getinfo($client, CURLINFO_RESPONSE_CODE) === 403, 'restored backend requires login');
    curl_close($client);

    // 仅在另一个本次创建的空测试库模拟 DDL 中断，检查检查点续建及未知表保护。
    $pdo->exec('CREATE DATABASE `' . $recovery . '` CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci');
    $pdo->exec('USE `' . $recovery . '`');
    $sql = file_get_contents($root . '/data/install.sql');
    preg_match('/CREATE TABLE `tournament` \([\s\S]*?\) ENGINE=InnoDB[^;]*;/', $sql, $first);
    $pdo->exec($first[0]);
    $dbConfig['dsn'] = preg_replace('/dbname=[^;]+/', 'dbname=' . $recovery, $config['dsn']);
    new yii\console\Application(['id' => 'deploy-test', 'basePath' => $root, 'runtimePath' => $directory, 'components' => ['db' => $dbConfig]]);
    $setup = new app\services\DatabaseSetupService();
    $failed = false;
    try { $setup->prepare(); } catch (RuntimeException $exception) { $failed = str_contains($exception->getMessage(), '检查点'); }
    $check($failed && count($pdo->query('SHOW TABLES')->fetchAll()) === 1, 'unknown partial schema preserved');
    $marker = $directory . '/deploy-schema-' . hash('sha256', $dbConfig['dsn']) . '.json';
    file_put_contents($marker, json_encode(['schema_sha256' => hash('sha256', $sql)]));
    $resumed = $setup->prepare();
    $check(count($resumed['created_tables']) === 14 && count($pdo->query('SHOW TABLES')->fetchAll()) === 15, 'own interrupted schema resumes');
    $check(!is_file($marker), 'completed schema checkpoint removed');
    $check($setup->prepare()['created_tables'] === [], 'existing complete schema preserved');
    $pdo->exec('CREATE TABLE test_only_unrelated (id INT)');
    $failed = false;
    try { $setup->prepare(); } catch (RuntimeException $exception) { $failed = str_contains($exception->getMessage(), '项目之外'); }
    $check($failed && count($pdo->query('SHOW TABLES')->fetchAll()) === 16, 'unrelated tables protected');
    echo "PASS {$assertions} real deployment/database checks\n";
} finally {
    if (is_resource($server)) { proc_terminate($server); proc_close($server); }
    if (Yii::$app !== null && Yii::$app->has('db')) { Yii::$app->db->close(); }
    $pdo->exec('USE information_schema');
    foreach ([$name, $recovery] as $testDatabase) {
        $pdo->exec('DROP DATABASE IF EXISTS `' . $testDatabase . '`');
    }
    // runtime 内仅保留被忽略的测试日志供失败排查，删除含连接配置的辅助 PHP。
    foreach (glob($directory . '/*.php') as $file) { unlink($file); }
    if (isset($configurationRoot) && is_dir($configurationRoot)) {
        foreach ([$configurationRoot . '/scripts/configure-local.php', $configurationRoot . '/config/db.local.php'] as $file) {
            if (is_file($file)) { unlink($file); }
        }
        rmdir($configurationRoot . '/scripts'); rmdir($configurationRoot . '/config'); rmdir($configurationRoot);
    }
    echo "Temporary deployment databases removed\n";
}
