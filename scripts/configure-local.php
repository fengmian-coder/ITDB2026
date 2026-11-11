<?php
/**
 * WorldCup Journey - 首次运行的本机数据库配置
 * 模块：C/D 本地部署；负责人：C/D，B 协助实现
 * 依赖：PHP 8.2、标准输入 JSON、被 Git 忽略的 db.local.php
 * 最近修改：2026-11-11
 */
declare(strict_types=1);

$file = dirname(__DIR__) . '/config/db.local.php';
$existing = is_file($file) ? require $file : [];
if (($existing['username'] ?? '') !== '') {
    exit(0);
}
if (($argv[1] ?? '') === '--check') {
    exit(2);
}
try {
    $input = json_decode(stream_get_contents(STDIN), true, 512, JSON_THROW_ON_ERROR);
    if (!is_array($input) || !is_string($input['host'] ?? null)
        || !preg_match('/^[A-Za-z0-9.:-]+$/D', $input['host'])
        || !is_int($input['port'] ?? null) || $input['port'] < 1 || $input['port'] > 65535
        || !is_string($input['database'] ?? null) || !preg_match('/^[A-Za-z0-9_]{1,64}$/D', $input['database'])
        || !is_string($input['username'] ?? null) || trim($input['username']) === ''
        || !is_string($input['password'] ?? null)) {
        throw new RuntimeException('数据库配置参数格式无效。');
    }
    $config = [
        'dsn' => 'mysql:host=' . $input['host'] . ';port=' . $input['port'] . ';dbname=' . $input['database'],
        'username' => $input['username'], 'password' => $input['password'],
    ];
    $contents = "<?php\n// WorldCup Journey 本机数据库账号，禁止提交。\nreturn " . var_export($config, true) . ";\n";
    if (file_put_contents($file, $contents, LOCK_EX) !== strlen($contents)) {
        throw new RuntimeException('无法保存本机数据库配置。');
    }
    echo "本机数据库配置已保存。\n";
} catch (Throwable $exception) {
    fwrite(STDERR, "本机数据库配置失败，请检查输入和 config 目录权限。\n");
    exit(1);
}
