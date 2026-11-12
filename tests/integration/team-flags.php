<?php
/**
 * WorldCup Journey - 国旗资源完整性和安全回退检查，不连接数据库
 * 模块：A 公共前端；负责人：A；依赖：TeamFlag、本地 SVG、curl
 * 最近修改：2026-11-12
 */
declare(strict_types=1);
use app\components\TeamFlag;
$root = dirname(__DIR__, 2);
require $root . '/vendor/autoload.php';
require $root . '/vendor/yiisoft/yii2/Yii.php';
Yii::setAlias('@app', $root);
Yii::setAlias('@webroot', $root . '/web');
Yii::setAlias('@web', '/subdirectory');
$checks = 0;
$check = static function (bool $ok, string $message) use (&$checks): void {
    if (!$ok) throw new RuntimeException($message);
    ++$checks;
};
$manifest = json_decode(file_get_contents($root . '/web/images/flags/manifest.json'), true, 512, JSON_THROW_ON_ERROR);
$stream = fopen($root . '/data/import/teams.csv', 'r');
$columns = fgetcsv($stream); $codes = [];
while (($row = fgetcsv($stream)) !== false) $codes[] = array_combine($columns, $row)['fifa_code'];
fclose($stream);
sort($codes); $mapped = array_keys($manifest); sort($mapped);
$check($codes === $mapped && count($codes) === 48, 'Complete FIFA code mapping');
foreach ($manifest as $code => $entry) {
    $file = $root . '/web/images/flags/' . $code . '.svg';
    $check(hash_file('sha256', $file) === $entry['sha256'], 'Original asset integrity ' . $code);
    $check(TeamFlag::url($code) === '/subdirectory/images/flags/' . $code . '.svg', 'Subdirectory URL ' . $code);
    $svg = new DOMDocument(); $svg->load($file);
    $check($svg->documentElement->localName === 'svg' && $svg->getElementsByTagName('script')->length === 0, 'SVG structure ' . $code);
}
$check($manifest['ENG']['source'] === 'flags/4x3/gb-eng.svg' && $manifest['SCO']['source'] === 'flags/4x3/gb-sct.svg', 'Separate England and Scotland flags');
foreach (['../config/db.php', 'https://example.com/a.svg', 'javascript:alert(1)', 'images/%2e%2e/a.svg', '/config/db.php', "images/a\0.svg"] as $path) {
    $check(TeamFlag::url('ZZZ', $path) === null, 'Reject unsafe custom path');
}
$check(TeamFlag::url('ESP', 'images/missing.svg') === '/subdirectory/images/flags/ESP.svg', 'Missing image falls back');
$check(TeamFlag::url('ESP', 'images/stadium-hero.jpg') === '/subdirectory/images/stadium-hero.jpg', 'Custom local image retained');
$check(TeamFlag::url('../ESP') === null, 'Reject invalid code');
$check(!str_contains(TeamFlag::render('ESP', '<script>bad</script>'), 'alt="<script>'), 'Name is encoded');
$check(str_contains(TeamFlag::render('ZZZ', '<script>bad</script>'), '&lt;'), 'Unknown code placeholder encoded');
echo "PASS $checks flag checks (no database writes)\n";
