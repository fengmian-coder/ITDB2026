<?php
/** Read-only verification of enhanced rosters, image provenance and all public team pages. */
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
// Position counts independently checked against the fixed FIFA PDF pages.
$expectedCounts = [
    'CRO'=>['GK'=>3,'DF'=>8,'MF'=>9,'FW'=>6],
    'UZB'=>['GK'=>3,'DF'=>10,'MF'=>10,'FW'=>3],
    'URU'=>['GK'=>3,'DF'=>7,'MF'=>11,'FW'=>5],
    'IRQ'=>['GK'=>3,'DF'=>9,'MF'=>7,'FW'=>7],
    'IRN'=>['GK'=>3,'DF'=>9,'MF'=>8,'FW'=>6],
    'COD'=>['GK'=>3,'DF'=>8,'MF'=>8,'FW'=>7],
    'CAN'=>['GK'=>3,'DF'=>9,'MF'=>7,'FW'=>7],
    'GHA'=>['GK'=>3,'DF'=>9,'MF'=>6,'FW'=>8],
    'QAT'=>['GK'=>3,'DF'=>9,'MF'=>5,'FW'=>9],
    'ECU'=>['GK'=>3,'DF'=>8,'MF'=>8,'FW'=>7],
    'CPV'=>['GK'=>3,'DF'=>8,'MF'=>12,'FW'=>3],
    'RSA'=>['GK'=>3,'DF'=>10,'MF'=>5,'FW'=>8],
    'GER'=>['GK'=>3,'DF'=>8,'MF'=>11,'FW'=>4],
    'COL'=>['GK'=>3,'DF'=>9,'MF'=>8,'FW'=>6],
    'TUR'=>['GK'=>3,'DF'=>9,'MF'=>5,'FW'=>9],
    'EGY'=>['GK'=>4,'DF'=>8,'MF'=>7,'FW'=>7],
    'SEN'=>['GK'=>3,'DF'=>8,'MF'=>7,'FW'=>8],
    'MEX'=>['GK'=>3,'DF'=>7,'MF'=>8,'FW'=>8],
    'AUT'=>['GK'=>3,'DF'=>8,'MF'=>11,'FW'=>4],
    'PAR'=>['GK'=>3,'DF'=>8,'MF'=>9,'FW'=>6],
    'PAN'=>['GK'=>3,'DF'=>11,'MF'=>8,'FW'=>4],
    'BRA'=>['GK'=>3,'DF'=>8,'MF'=>6,'FW'=>9],
    'CUW'=>['GK'=>3,'DF'=>8,'MF'=>7,'FW'=>8],
    'NOR'=>['GK'=>3,'DF'=>8,'MF'=>10,'FW'=>5],
    'CIV'=>['GK'=>3,'DF'=>8,'MF'=>6,'FW'=>9],
    'TUN'=>['GK'=>3,'DF'=>10,'MF'=>8,'FW'=>5],
    'JOR'=>['GK'=>3,'DF'=>9,'MF'=>8,'FW'=>6],
    'USA'=>['GK'=>3,'DF'=>10,'MF'=>6,'FW'=>7],
    'SCO'=>['GK'=>3,'DF'=>10,'MF'=>6,'FW'=>7],
    'ENG'=>['GK'=>3,'DF'=>9,'MF'=>7,'FW'=>7],
    'NED'=>['GK'=>3,'DF'=>8,'MF'=>8,'FW'=>7],
    'POR'=>['GK'=>3,'DF'=>9,'MF'=>6,'FW'=>8],
    'ESP'=>['GK'=>3,'DF'=>8,'MF'=>7,'FW'=>8],
    'ALG'=>['GK'=>3,'DF'=>9,'MF'=>7,'FW'=>7],
    'ARG'=>['GK'=>3,'DF'=>8,'MF'=>8,'FW'=>7],
    'KOR'=>['GK'=>3,'DF'=>9,'MF'=>11,'FW'=>3],
    'CZE'=>['GK'=>3,'DF'=>9,'MF'=>7,'FW'=>7],
    'MAR'=>['GK'=>3,'DF'=>9,'MF'=>9,'FW'=>5],
    'NZL'=>['GK'=>3,'DF'=>9,'MF'=>9,'FW'=>5],
    'JPN'=>['GK'=>3,'DF'=>9,'MF'=>9,'FW'=>5],
    'BEL'=>['GK'=>3,'DF'=>9,'MF'=>8,'FW'=>6],
    'KSA'=>['GK'=>3,'DF'=>10,'MF'=>6,'FW'=>7],
    'FRA'=>['GK'=>3,'DF'=>9,'MF'=>7,'FW'=>7],
    'BIH'=>['GK'=>3,'DF'=>8,'MF'=>8,'FW'=>7],
    'HAI'=>['GK'=>3,'DF'=>9,'MF'=>5,'FW'=>9],
    'AUS'=>['GK'=>3,'DF'=>10,'MF'=>5,'FW'=>8],
    'SWE'=>['GK'=>3,'DF'=>11,'MF'=>7,'FW'=>5],
    'SUI'=>['GK'=>3,'DF'=>8,'MF'=>8,'FW'=>7],
];
$check(count($features['teams']) === count($expectedCounts), 'all 48 team pages are enhanced');
foreach ($expectedCounts as $code => $expected) {
    $teamProfile = $bundle['teams'][$code];
    $teamFeature = $service->get($code, $teamProfile);
    $check($teamFeature !== null && count($teamFeature['roster']) === 26 && $teamFeature['counts'] === $expected, 'FIFA roster counts ' . $code);
    $check(array_column($teamFeature['roster'], 'shirt_number') === range(1,26), 'unique ordered shirt numbers ' . $code);
    $check(count($teamFeature['highlighted']) === 3, 'three representative players ' . $code);
    $check(($teamFeature['coach_photo']['name'] ?? null) === (in_array($code, ['HAI', 'KSA'], true) ? null : $teamProfile['coach_name']), 'coach photo identity ' . $code);
    foreach ($teamFeature['roster'] as $player) {
        $check(!empty($player['name_zh']) && !empty($player['club_zh']), 'bilingual roster labels ' . $code);
    }
    foreach ($teamFeature['highlighted'] as $player) {
        $check(($player['photo']['name'] ?? null) === ($code === 'HAI' && $player['name'] === 'Ricardo Ade' ? null : $player['name']), 'representative photo identity ' . $code);
    }
    $changed = $teamProfile; $changed['coach_name'] = 'Different coach';
    $check($service->get($code, $changed)['coach_photo'] === null, 'stale coach photo removed ' . $code);
    $changed = $teamProfile; $changed['edition'] = 'Different edition';
    $check($service->get($code, $changed) === null, 'version mismatch fallback ' . $code);
}
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
// Photographs with a documented source and unverified permission for redistribution.
// These checks verify truthful provenance, not permission from the rights holder.
$coursePhotos = [
    'cpv-coach' => [
        'name' => 'Bubista',
        'source_host' => 'baike.baidu.com',
        'path' => 'images/team-profiles/cpv-coach.png',
        'sha256' => '68a4b9a59fb57d263276c4d7c2329e3e4d52d6d77feb5d1ead3c0275eb1233b0',
        'acquisition' => 'user_attachment',
    ],
    'rsa-mokoena' => [
        'name' => 'Teboho Mokoena',
        'source_host' => 'sundownsfc.co.za',
        'path' => 'images/team-profiles/rsa-mokoena.jpg',
        'sha256' => 'c2b887b5d502e9477366c725e26ab4ab0eed8699bea9c2eb65f19efe19a96a72',
        'acquisition' => 'source_download',
    ],
];
$check(array_keys(array_filter($credits, static fn(array $p): bool => ($p['rights_status'] ?? '') === 'unverified')) === array_keys($coursePhotos), 'all course-preview photographs have an explicit provenance record');
foreach ($credits as $key => $photo) {
    $path = $root . '/web/' . $photo['path'];
    $size = getimagesize($path);
    $check($size !== false && $size[0] === $photo['width'] && $size[1] === $photo['height'], 'image dimensions');
    $check(hash_file('sha256', $path) === $photo['sha256'], 'image SHA256');
    if (isset($coursePhotos[$key])) {
        $selected = $coursePhotos[$key];
        $check($photo['name'] === $selected['name'] && $photo['path'] === $selected['path']
            && $photo['sha256'] === $selected['sha256']
            && parse_url($photo['source_url'], PHP_URL_HOST) === $selected['source_host']
            && $photo['acquisition'] === $selected['acquisition'] && $photo['author'] !== ''
            && $photo['rights_status'] === 'unverified' && $photo['license_url'] === null
            && $photo['license'] === '版权保留 · 未取得开放许可'
            && $photo['date_kind'] === 'accessed' && $photo['date'] === ($selected['accessed'] ?? '2026-11-19')
            && str_contains($photo['date_note'], '未注明拍摄日期')
            && str_contains($photo['usage_basis'], '不代表照片权利人授权'), 'truthful course photo provenance ' . $key);
    } else {
        $check(parse_url($photo['source_url'], PHP_URL_HOST) === 'commons.wikimedia.org'
            && parse_url($photo['license_url'], PHP_URL_HOST) === 'creativecommons.org' && $photo['author'] !== '', 'image attribution');
    }
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
$check($service->get('XXX', $bundle['teams']['JPN']) === null, 'unknown teams fall back without enhanced data');
$http = curl_init();
curl_setopt_array($http, [CURLOPT_RETURNTRANSFER=>true,CURLOPT_NOPROXY=>'*',CURLOPT_TIMEOUT=>15]);
foreach (NationalTeam::find()->where(['fifa_code'=>array_keys($bundle['teams'])])->all() as $team) {
    curl_setopt($http, CURLOPT_URL, $base . '/index.php?r=national-team%2Fview&id=' . $team->id);
    $html = curl_exec($http);
    $check($html !== false && curl_getinfo($http, CURLINFO_HTTP_CODE) === 200, 'team page ' . $team->fifa_code);
    $check(str_contains($html, '本届赛事表现'), 'existing statistics ' . $team->fifa_code);
    $check(str_contains($html, 'team-profile-feature') === isset($expectedCounts[$team->fifa_code]), 'enhancement scope ' . $team->fifa_code);
    if (isset($expectedCounts[$team->fifa_code])) {
        $check(substr_count($html, 'class="team-roster-number"') === 26 && substr_count($html, 'class="team-person-photo') === (['HAI' => 2, 'KSA' => 3][$team->fifa_code] ?? 4), 'complete roster and available local photos ' . $team->fifa_code);
        $check(!str_contains($html, '外部資料照') && !str_contains($html, '外部资料照') && !str_contains($html, '外部照片'), 'person cards use local photo assets ' . $team->fifa_code);
        $check(substr_count($html, 'class="team-roster-original"') === 52 && str_contains($html, '完整参赛名单（26人）'), 'bilingual names and compact roster header ' . $team->fifa_code);
    }
    if ($team->fifa_code === 'HAI') {
        $check(preg_match('~<figure[^>]*team-player-photo-framed[^>]*>\s*<img[^>]*src="[^"]*/hai-danley\.jpg"~', $html) === 1,
            'Danley portrait uses a wider frame without clipping the face');
    }
    if ($team->fifa_code === 'KSA') {
        $check(str_contains($html, '2026 年 4 月出任'), 'Saudi Arabia coach appointment caption');
    }
    if ($team->fifa_code === 'NZL') {
        $check(str_contains($html, '/images/team-profiles/nzl-bazeley-2026.jpg'), 'New Zealand coach uses the new local photo');
    }
    if ($team->fifa_code === 'SUI') {
        $check(preg_match('~<figure[^>]*overflow-hidden[^>]*>\s*<img[^>]*src="[^"]*/sui-xhaka-2018-world-cup\.jpg"[^>]*style="transform: scale\(1\.55\);~', $html) === 1,
            'Xhaka photo uses the local image and stays inside its frame');
    }
    if (in_array($team->fifa_code, ['NZL', 'AUS'], true)) {
        $file = $team->fifa_code === 'NZL' ? 'nzl-just-portrait.jpg' : 'aus-irvine-2026.jpg';
        $check(preg_match('~<img[^>]*src="[^"]*/' . preg_quote($file, '~') . '"[^>]*style="object-position: center 10%;"~', $html) === 1,
            'landscape card uses the revised head-and-shoulders photo ' . $team->fifa_code);
    }
    if (in_array($team->fifa_code, ['CPV', 'RSA'], true)) {
        $check(str_contains($html, '收录日期 2026-11-19') && str_contains($html, '版权保留 · 未取得开放许可'), 'course photo date and rights statement rendered ' . $team->fifa_code);
        $check(!str_contains($html, '使用 Commons 预览版'), 'mixed image sources are not described as entirely Commons ' . $team->fifa_code);
    }
    if ($team->fifa_code === 'CPV') {
        $check(str_contains($html, '/images/team-profiles/cpv-coach.png') && str_contains($html, 'object-fit: contain;'), 'user supplied PNG is displayed without cropping its watermark');
    }
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
