<?php
/** Read-only verification of the Croatia sample, image provenance and all public team pages. */
declare(strict_types=1);
use app\models\NationalTeam;
use app\services\TeamProfileFeatureService;
use app\services\TeamProfileService;

$root = dirname(__DIR__, 2);
require $root . '/vendor/autoload.php';
require $root . '/vendor/yiisoft/yii2/Yii.php';
new yii\console\Application(require $root . '/config/console.php');
$base = rtrim($argv[1] ?? 'http://127.0.0.1:8080', '/');
$parts = parse_url($base);
if (($parts['scheme'] ?? '') !== 'http' || !in_array($parts['host'] ?? '', ['localhost','127.0.0.1'], true)) {
    throw new RuntimeException('Only local preview URLs are allowed');
}
$checks = 0;
$check = static function (bool $ok, string $label) use (&$checks): void { if (!$ok) throw new RuntimeException('FAIL ' . $label); ++$checks; };
$bundle = json_decode(file_get_contents($root . '/data/team-profiles-2026.json'), true, 512, JSON_THROW_ON_ERROR);
$features = json_decode(file_get_contents($root . '/data/team-profile-features-2026.json'), true, 512, JSON_THROW_ON_ERROR);
$credits = json_decode(file_get_contents($root . '/web/images/team-profiles/credits.json'), true, 512, JSON_THROW_ON_ERROR);
$check($features['source_sha256'] === $bundle['source_sha256'], 'roster source version is retained');
$service = new TeamProfileFeatureService();
$profile = $bundle['teams']['CRO'];
$sample = $service->get('CRO', $profile);
$check($sample !== null && count($sample['roster']) === 26, '26 player roster');
$check($sample['counts'] === ['GK'=>3,'DF'=>8,'MF'=>9,'FW'=>6], 'verified position counts');
$check(array_column($sample['roster'], 'shirt_number') === range(1,26), 'unique 1-26 shirt numbers');
$check(count(array_unique(array_column($sample['roster'], 'name'))) === 26, 'unique roster names');
$check(count($sample['highlighted']) === 3 && $sample['coach_photo'] !== null, 'four matching profile images');
foreach ($sample['roster'] as $player) {
    $check(isset($player['name_zh'], $player['club_zh']) && trim($player['name_zh']) !== '' && trim($player['club_zh']) !== '', 'Chinese names and club labels');
}
foreach ($sample['highlighted'] as $player) {
    $check($player['photo']['name'] === $player['name'], 'player photograph identity');
    $check($player['photo']['date'] === '2026-07-02' && $player['photo']['author'] === 'Bryan Berlin / WikiPortraits', 'same-event 2026 photographs');
}
foreach ($credits as $photo) {
    $path = $root . '/web/' . $photo['path'];
    $size = getimagesize($path);
    $check($size !== false && $size[0] === $photo['width'] && $size[1] === $photo['height'], 'image dimensions');
    $check(hash_file('sha256', $path) === $photo['sha256'], 'image SHA256');
    $check(parse_url($photo['source_url'], PHP_URL_HOST) === 'commons.wikimedia.org'
        && parse_url($photo['license_url'], PHP_URL_HOST) === 'creativecommons.org' && $photo['author'] !== '', 'image attribution');
}
$modified = $profile; $modified['summary'] = '管理员编辑的球队简介';
$check($service->get('CRO', $modified)['summary'] === $modified['summary'], 'custom team summary retained');
$modified = $profile; $modified['coach_name'] = 'Different coach';
$check($service->get('CRO', $modified)['coach_photo'] === null, 'coach identity change removes mismatched photo');
$check($service->get('CRO', $modified)['coach_name_zh'] === $modified['coach_name'], 'coach identity change removes stale Chinese name');
$modified = $profile; $modified['coach_description'] = '管理员编辑的教练经历';
$check($service->get('CRO', $modified)['coach_facts'] === $modified['coach_description'], 'custom coach biography retained');
$modified = $profile; $modified['players'][0]['name'] = 'Different player';
$check(count($service->get('CRO', $modified)['highlighted']) === 2, 'player identity change removes mismatched photo');
$modified = $profile; $modified['edition'] = 'Another edition';
$check($service->get('CRO', $modified) === null, 'different roster edition does not receive sample');
$check($service->get('ESP', $bundle['teams']['ESP']) === null, 'other teams retain the existing layout');
$http = curl_init();
curl_setopt_array($http, [CURLOPT_RETURNTRANSFER=>true,CURLOPT_NOPROXY=>'*',CURLOPT_TIMEOUT=>15]);
foreach (NationalTeam::find()->where(['fifa_code'=>array_keys($bundle['teams'])])->all() as $team) {
    curl_setopt($http, CURLOPT_URL, $base . '/index.php?r=national-team%2Fview&id=' . $team->id);
    $html = curl_exec($http);
    $check($html !== false && curl_getinfo($http, CURLINFO_HTTP_CODE) === 200, 'team page ' . $team->fifa_code);
    $check(str_contains($html, '本届赛事表现'), 'existing statistics ' . $team->fifa_code);
    $check(str_contains($html, 'team-profile-feature') === ($team->fifa_code === 'CRO'), 'sample scope ' . $team->fifa_code);
    if ($team->fifa_code === 'CRO') {
        $check(substr_count($html, 'class="team-roster-number"') === 26, 'all 26 roster rows rendered');
        $check(substr_count($html, 'class="team-person-photo') === 4, 'four image figures rendered');
        $check(!str_contains($html, '<details class="team-roster mt-4" open'), 'full roster is initially collapsed');
        $check(str_contains($html, '名单与图片来源') && str_contains($html, 'CC BY-SA 4.0'), 'visible image credit links');
        $check(str_contains($html, '球队概况') && !str_contains($html, '认识这支球队') && !str_contains($html, '2026 · 球队档案'), 'single useful overview heading');
        $check(str_contains($html, '世界杯最佳') && str_contains($html, '2018 年亚军') && str_contains($html, '2022 年季军'), 'verified history facts rendered');
        $check(str_contains($html, '莫德里奇 <span class="team-name-original">（Luka Modric）</span>') && str_contains($html, 'AC 米兰 <span class="team-name-original">（AC Milan (ITA)）</span>'), 'card names and clubs show Chinese with parenthesized originals');
        $check(!str_contains($html, '显示原文名称') && substr_count($html, 'class="team-roster-original"') === 52, 'all roster names and clubs show originals without a toggle');
    }
}
curl_close($http);
echo 'PASS ', $checks, ' read-only team-profile feature checks', PHP_EOL;
