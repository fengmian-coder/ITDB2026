<?php
/**
 * WorldCup Journey - B 模块与 C 表结构、D 授权的真实联调
 * 模块：B；负责人：B；依赖：PHP 8.2、MySQL、curl、现有 Yii 配置及 C 的 install.sql
 * 最近修改：2026-11-11
 * 运行：php tests/integration/b-module.php
 * 使用当前本地账号创建唯一临时数据库，结束后删除本次创建的库。
 */
declare(strict_types=1);

$root = dirname(__DIR__, 2);
require $root . '/vendor/autoload.php';
require $root . '/vendor/yiisoft/yii2/Yii.php';
Yii::setAlias('@app', $root);
$config = require $root . '/config/db.php';
$suffix = bin2hex(random_bytes(6));
$database = 'wcj_b_test_' . $suffix;
$dsn = preg_replace('/dbname=[^;]+/', 'dbname=' . $database, $config['dsn']);
if ($dsn === $config['dsn'] || !preg_match('/^wcj_b_test_[a-f0-9]{12}$/D', $database)) {
    throw new RuntimeException('无法建立独立测试库连接');
}
$pdo = new PDO($config['dsn'], $config['username'], $config['password'], [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
$created = false;
$process = null;
$router = $root . '/runtime/b-test-router-' . $suffix . '.php';
$log = $root . '/runtime/b-test-server-' . $suffix . '.log';
$checks = 0;
$check = static function (bool $condition, string $name) use (&$checks): void {
    if (!$condition) {
        throw new RuntimeException('FAIL ' . $name);
    }
    ++$checks;
};
$admin = curl_init();
$guest = curl_init();
foreach ([$admin, $guest] as $client) {
    curl_setopt_array($client, [CURLOPT_RETURNTRANSFER => true, CURLOPT_COOKIEFILE => '', CURLOPT_TIMEOUT => 15]);
}

try {
    $pdo->exec('CREATE DATABASE ' . $database . ' CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci');
    $created = true;
    $pdo->exec('USE ' . $database);
    $pdo->exec(file_get_contents($root . '/data/install.sql'));
    $check(count($pdo->query('SHOW TABLES')->fetchAll()) === 15, 'C schema restores 15 tables');
    $dbConfig = $config;
    $dbConfig['dsn'] = $dsn;
    new yii\console\Application(['id' => 'b-integration', 'basePath' => $root, 'components' => ['db' => $dbConfig]]);
    $db = Yii::$app->db;
    $insert = static function (string $table, array $row) use ($db): int {
        $db->createCommand()->insert($table, $row)->execute();
        return (int) $db->getLastInsertID();
    };
    $tournament = $insert('tournament', ['year' => 2026, 'name' => 'TEST ONLY tournament', 'start_date' => '2026-06-11', 'end_date' => '2026-07-19']);
    $home = $insert('national_team', ['fifa_code' => 'ZZX', 'name_zh' => 'TEST ONLY home', 'name_en' => 'TEST ONLY home']);
    $away = $insert('national_team', ['fifa_code' => 'ZZY', 'name_zh' => 'TEST ONLY away', 'name_en' => 'TEST ONLY away']);
    foreach ([$home, $away] as $team) {
        $db->createCommand()->insert('tournament_team', ['tournament_id' => $tournament, 'team_id' => $team, 'group_code' => 'A'])->execute();
    }
    $stadium = $insert('stadium', ['name' => 'TEST ONLY stadium', 'city' => 'TEST ONLY city', 'country_code' => 'US']);
    $base = ['tournament_id' => $tournament, 'home_team_id' => $home, 'away_team_id' => $away,
        'stadium_id' => $stadium, 'kickoff_utc' => '2026-06-11 19:00:00', 'stage_code' => 'GROUP', 'group_code' => 'A'];
    $m1 = $insert('wc_match', array_replace($base, ['match_no' => 1, 'status' => 'FINISHED', 'home_score_90' => 1, 'away_score_90' => 0]));
    $m2 = $insert('wc_match', array_replace($base, ['match_no' => 2, 'stage_code' => 'FINAL', 'group_code' => null,
        'status' => 'FINISHED', 'home_score_90' => 1, 'away_score_90' => 1, 'home_score_120' => 2, 'away_score_120' => 2,
        'home_penalties' => 4, 'away_penalties' => 3, 'winner_team_id' => $home]));
    $insert('wc_match', array_replace($base, ['match_no' => 3, 'status' => 'CANCELLED']));
    $insert('wc_match', array_replace($base, ['match_no' => 4, 'status' => 'SCHEDULED']));
    $password = Yii::$app->security->generateRandomString(32);
    $insert('admin_user', ['username' => 'b_test', 'password_hash' => Yii::$app->security->generatePasswordHash($password),
        'auth_key' => Yii::$app->security->generateRandomString(), 'status' => 1]);

    $socket = stream_socket_server('tcp://127.0.0.1:0');
    $port = (int) substr(strrchr(stream_socket_get_name($socket, false), ':'), 1);
    fclose($socket);
    $routerCode = '<?php define("YII_ENV", "test"); define("YII_DEBUG", false);'
        . 'require ' . var_export($root . '/vendor/autoload.php', true) . ';'
        . 'require ' . var_export($root . '/vendor/yiisoft/yii2/Yii.php', true) . ';'
        . '$config=require ' . var_export($root . '/config/web.php', true) . ';'
        . '$config["components"]["db"]["dsn"]=' . var_export($dsn, true) . ';'
        . '(new yii\\web\\Application($config))->run();';
    file_put_contents($router, $routerCode);
    $process = proc_open([PHP_BINARY, '-S', '127.0.0.1:' . $port, '-t', $root . '/web', $router],
        [0 => ['pipe', 'r'], 1 => ['file', $log, 'a'], 2 => ['file', $log, 'a']], $pipes, $root,
        null, ['bypass_shell' => true]);
    if (!is_resource($process)) {
        throw new RuntimeException('测试 HTTP 服务启动失败');
    }
    fclose($pipes[0]);
    $url = 'http://127.0.0.1:' . $port . '/index.php?r=';
    $request = static function ($client, string $route, ?array $data = null) use ($url): array {
        curl_setopt_array($client, [CURLOPT_URL => $url . $route, CURLOPT_POST => $data !== null]);
        if ($data !== null) {
            curl_setopt($client, CURLOPT_POSTFIELDS, http_build_query($data));
        } else {
            curl_setopt($client, CURLOPT_HTTPGET, true);
        }
        $body = curl_exec($client);
        if ($body === false) {
            throw new RuntimeException('HTTP request failed');
        }
        return [(int) curl_getinfo($client, CURLINFO_RESPONSE_CODE), $body];
    };
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
    $check($ready, 'temporary HTTP server ready');
    $token = static function (string $html): string {
        $dom = new DOMDocument();
        $previous = libxml_use_internal_errors(true);
        $dom->loadHTML($html);
        libxml_clear_errors();
        libxml_use_internal_errors($previous);
        $input = (new DOMXPath($dom))->query('//input[@name="_csrf"]')->item(0);
        if (!$input) {
            throw new RuntimeException('未找到 CSRF 字段');
        }
        return $input->getAttribute('value');
    };
    $scalar = static fn (string $sql) => $pdo->query($sql)->fetchColumn();
    foreach (['admin-tournament', 'admin-stadium', 'admin-match', 'admin-story'] as $module) {
        $check($request($guest, $module . '/index')[0] === 403, 'guest denied ' . $module);
    }
    [$status, $html] = $request($admin, 'auth/login');
    $check($status === 200, 'login form');
    $csrf = $token($html);
    $check($request($admin, 'auth/login', ['_csrf' => $csrf, 'AdminLoginForm' => ['username' => 'b_test', 'password' => 'wrong']])[0] === 200, 'incorrect password rejected');
    $check($request($admin, 'auth/login', ['_csrf' => $csrf, 'AdminLoginForm' => ['username' => 'b_test', 'password' => $password]])[0] === 302, 'real administrator login');
    foreach (['admin-tournament', 'admin-stadium', 'admin-match', 'admin-story'] as $module) {
        $check($request($admin, $module . '/index')[0] === 200, 'authenticated list ' . $module);
        $check($request($admin, $module . '/create')[0] === 200, 'authenticated form ' . $module);
        $check($request($admin, $module . '/delete&id=1')[0] === 405, 'GET delete refused ' . $module);
    }
    $check($request($admin, 'admin-match/create', ['WcMatch' => []])[0] === 400, 'CSRF required');
    // 登录后 Yii 会更新 CSRF cookie，写操作使用登录后表单中的新 token。
    $csrf = $token($request($admin, 'admin-tournament/create')[1]);

    $tournamentResponse = $request($admin, 'admin-tournament/create', ['_csrf' => $csrf, 'Tournament' => [
        'year' => 2030, 'name' => 'TEST_ONLY_CRUD', 'start_date' => '2030-06-01', 'end_date' => '2030-07-01']]);
    $crudTournament = (int) $scalar("SELECT id FROM tournament WHERE year=2030");
    if ($crudTournament === 0) {
        preg_match('/<div class="alert alert-danger"[^>]*>(.*?)<\/div>/s', $tournamentResponse[1], $error);
        throw new RuntimeException('Tournament form HTTP ' . $tournamentResponse[0] . ' ' . strip_tags($error[1] ?? 'unknown error'));
    }
    $check($crudTournament > 0, 'tournament HTTP create');
    $request($admin, 'admin-tournament/update&id=' . $crudTournament, ['_csrf' => $csrf, 'Tournament' => ['name' => 'TEST_ONLY_UPDATED']]);
    $check($scalar('SELECT name FROM tournament WHERE id=' . $crudTournament) === 'TEST_ONLY_UPDATED', 'tournament HTTP update');
    $request($admin, 'admin-tournament/delete&id=' . $crudTournament, ['_csrf' => $csrf, 'confirm_delete' => '1']);
    $check((int) $scalar('SELECT COUNT(*) FROM tournament WHERE id=' . $crudTournament) === 0, 'unreferenced tournament delete');
    $request($admin, 'admin-stadium/create', ['_csrf' => $csrf, 'Stadium' => [
        'name' => 'TEST_ONLY_CRUD', 'city' => 'TEST_ONLY', 'country_code' => 'CA', 'timezone_name' => '']]);
    $crudStadium = (int) $scalar("SELECT id FROM stadium WHERE name='TEST_ONLY_CRUD'");
    $check($crudStadium > 0, 'stadium HTTP create');
    $request($admin, 'admin-stadium/update&id=' . $crudStadium, ['_csrf' => $csrf, 'Stadium' => ['city' => 'TEST_ONLY_UPDATED']]);
    $check($scalar('SELECT city FROM stadium WHERE id=' . $crudStadium) === 'TEST_ONLY_UPDATED', 'stadium HTTP update');
    $request($admin, 'admin-stadium/delete&id=' . $crudStadium, ['_csrf' => $csrf, 'confirm_delete' => '1']);
    $check((int) $scalar('SELECT COUNT(*) FROM stadium WHERE id=' . $crudStadium) === 0, 'unreferenced stadium delete');
    $request($admin, 'admin-match/create', ['_csrf' => $csrf, 'WcMatch' => array_replace($base, [
        'match_no' => 5, 'status' => 'SCHEDULED', 'summary' => 'TEST_ONLY_CRUD'])]);
    $crudMatch = (int) $scalar('SELECT id FROM wc_match WHERE match_no=5');
    $check($crudMatch > 0, 'match HTTP create');
    $request($admin, 'admin-match/update&id=' . $crudMatch, ['_csrf' => $csrf, 'WcMatch' => [
        'status' => 'FINISHED', 'home_score_90' => 1]]);
    $check($scalar('SELECT status FROM wc_match WHERE id=' . $crudMatch) === 'SCHEDULED', 'unpaired score rejected before saving');
    $request($admin, 'admin-match/update&id=' . $crudMatch, ['_csrf' => $csrf, 'WcMatch' => [
        'status' => 'FINISHED', 'home_score_90' => 0, 'away_score_90' => 0]]);
    $check($scalar('SELECT status FROM wc_match WHERE id=' . $crudMatch) === 'FINISHED', 'match score update');
    $request($admin, 'admin-match/delete&id=' . $crudMatch, ['_csrf' => $csrf, 'confirm_delete' => '1']);
    $check((int) $scalar('SELECT COUNT(*) FROM wc_match WHERE id=' . $crudMatch) === 0, 'unreferenced match delete');

    $service = new app\services\MatchQueryService();
    $list = $service->getMatchList(['team_id' => $home, 'stage' => 'GROUP', 'page_size' => 1]);
    $check($list['pagination']['total_count'] === 3 && count($list['items']) === 1, 'combined filter and count');
    $check($service->getMatchList(['team_id' => $away])['pagination']['total_count'] === 4, 'away team filter');
    $summary = $service->getStageSummary();
    $check($summary[0]['match_count'] === 2 && $summary[0]['finished_match_count'] === 1 && $summary[0]['total_goals'] === 1, 'cancelled excluded');
    $check($summary[1]['total_goals'] === 4, 'extra time cumulative and penalties excluded');
    $check($request($guest, 'wc-match/index')[0] === 200, 'public match list');
    $check($request($guest, 'wc-match/view&id=' . $m2)[0] === 200, 'public match detail');
    $check($request($guest, 'journey/view&team_id=' . $home)[0] === 200, 'journey real matches');
    $check($request($guest, 'wc-match/view&id=0')[0] === 400, 'invalid match 400');
    $check($request($guest, 'wc-match/view&id=999999')[0] === 404, 'absent match 404');
    $check($request($guest, 'api/insights&chart=stage-goals')[0] === 200, 'stage API real database');
    $check($request($guest, 'api/team-stats&id=' . $home)[0] === 500, 'missing C statistics explicit failure');

    [$status, $html] = $request($admin, 'admin-story/create');
    $csrf = $token($html);
    $storyData = ['match_id' => $m2, 'title' => 'TEST ONLY story', 'summary' => 'TEST ONLY summary', 'body' => 'TEST_ONLY_PRIVATE_BODY <script>alert(1)</script>', 'is_published' => '0'];
    $check($request($admin, 'admin-story/create', ['_csrf' => $csrf, 'MatchStory' => $storyData])[0] === 302, 'story created by HTTP');
    $story = (int) $scalar('SELECT id FROM match_story');
    $check($scalar('SELECT created_at FROM match_story') !== null, 'database audit defaults');
    $check($service->getMatchDetail($m2)['story'] === null, 'draft hidden');
    $check($request($admin, 'admin-story/create', ['_csrf' => $csrf, 'MatchStory' => $storyData])[0] === 200 && (int) $scalar('SELECT COUNT(*) FROM match_story') === 1, 'unique story per match');
    $eventData = ['minute' => '45', 'added_minute' => '2', 'period_code' => 'H1', 'event_type' => 'OTHER', 'description' => 'TEST_ONLY_EVENT_LATE', 'sort_order' => 10];
    $check($request($admin, 'admin-story/event-create&id=' . $story, ['_csrf' => $csrf, 'MatchEvent' => $eventData])[0] === 302, 'event created');
    $eventData['description'] = 'TEST_ONLY_EVENT_FIRST';
    $eventData['sort_order'] = 1;
    $request($admin, 'admin-story/event-create&id=' . $story, ['_csrf' => $csrf, 'MatchEvent' => $eventData]);
    $check((int) $scalar('SELECT COUNT(*) FROM match_event') === 2, 'second sorted event created');
    $check($request($admin, 'admin-story/tag-create&id=' . $story, ['_csrf' => $csrf, 'MatchTag' => ['code' => 'TEST_ONLY', 'name' => '<svg onload=alert(1)>']])[0] === 302, 'tag created');
    $tag = (int) $scalar('SELECT id FROM match_tag');
    $request($admin, 'admin-story/tag-add&id=' . $story . '&tag_id=' . $tag, ['_csrf' => $csrf]);
    $request($admin, 'admin-story/tag-add&id=' . $story . '&tag_id=' . $tag, ['_csrf' => $csrf]);
    $check((int) $scalar('SELECT COUNT(*) FROM match_story_tag') === 1, 'association idempotence');
    $check($service->getMatchDetail($m2)['events'] === [], 'draft events hidden');
    $request($admin, 'admin-story/update&id=' . $story, ['_csrf' => $csrf, 'MatchStory' => ['is_published' => '1']]);
    $check((int) $scalar('SELECT is_published FROM match_story') === 0, 'publication needs acknowledgement');
    $check($request($admin, 'admin-story/update&id=' . $story, ['_csrf' => $csrf, 'confirm_publish' => '1', 'MatchStory' => ['is_published' => '1']])[0] === 302, 'publication confirmed');
    $detail = $service->getMatchDetail($m2);
    $check(count($detail['tags']) === 1 && count($detail['events']) === 2, 'published relations real eager loading');
    $check($detail['events'][0]['sort_order'] === 1, 'event order');
    [$status, $html] = $request($guest, 'wc-match/view&id=' . $m2);
    $check($status === 200 && str_contains($html, 'TEST_ONLY_PRIVATE_BODY') && !str_contains($html, '<svg onload=alert(1)>'), 'published detail and escaping');
    $request($admin, 'admin-story/tag-delete&id=' . $story . '&tag_id=' . $tag, ['_csrf' => $csrf, 'confirm_shared' => '1', 'confirm_delete' => '1']);
    $check((int) $scalar('SELECT COUNT(*) FROM match_tag') === 1, 'referenced tag protected');
    $request($admin, 'admin-match/delete&id=' . $m2, ['_csrf' => $csrf, 'confirm_delete' => '1']);
    $check((int) $scalar('SELECT COUNT(*) FROM wc_match') === 4, 'referenced match protected');
    $request($admin, 'admin-story/update&id=' . $story, ['_csrf' => $csrf, 'MatchStory' => ['is_published' => '0']]);
    $check($service->getMatchDetail($m2)['tags'] === [], 'withdrawal hides tags');
    $request($admin, 'admin-story/delete&id=' . $story, ['_csrf' => $csrf, 'confirm_delete' => '1']);
    $check((int) $scalar('SELECT COUNT(*) FROM match_story') === 0 && (int) $scalar('SELECT COUNT(*) FROM match_story_tag') === 0, 'story and links deleted');
    $check((int) $scalar('SELECT COUNT(*) FROM match_event') === 2 && (int) $scalar('SELECT COUNT(*) FROM match_tag') === 1, 'events and dictionary preserved');

    [$guestStatus, $guestHtml] = $request($guest, 'guestbook/index&match_id=' . $m1);
    $guestCsrf = $token($guestHtml);
    $check($guestStatus === 200, 'public guestbook form');
    $request($guest, 'guestbook/create', ['_csrf' => $guestCsrf, 'nickname' => 'TEST_ONLY',
        'content' => 'TEST_ONLY_PRIVATE_MESSAGE', 'related_match_id' => $m1]);
    $message = (int) $scalar('SELECT id FROM guestbook_message');
    $check($message > 0 && $scalar('SELECT status FROM guestbook_message') === 'PENDING', 'match-related message pending');
    $check(!str_contains($request($guest, 'guestbook/index')[1], 'TEST_ONLY_PRIVATE_MESSAGE'), 'pending message hidden');
    $request($admin, 'admin-guestbook/moderate&id=' . $message, ['_csrf' => $csrf, 'status' => 'APPROVED']);
    $check(str_contains($request($guest, 'guestbook/index')[1], 'TEST_ONLY_PRIVATE_MESSAGE'), 'approved message visible');
    $request($admin, 'admin-guestbook/reply&id=' . $message, ['_csrf' => $csrf, 'content' => 'TEST_ONLY_REPLY']);
    $check((int) $scalar('SELECT COUNT(*) FROM guestbook_reply') === 1, 'administrator reply stored');
    $check($request($admin, 'admin-guestbook/reply&id=' . $message, ['_csrf' => $csrf, 'content' => 'TEST_ONLY_DUPLICATE'])[0] === 409, 'duplicate reply conflict');
    $request($admin, 'admin-guestbook/moderate&id=' . $message, ['_csrf' => $csrf, 'status' => 'REJECTED']);
    $check(!str_contains($request($guest, 'guestbook/index')[1], 'TEST_ONLY_REPLY'), 'rejection hides reply');
    $blocked = false;
    try {
        $pdo->exec('DELETE FROM wc_match WHERE id=' . $m1);
    } catch (PDOException $exception) {
        $blocked = $exception->getCode() === '23000';
    }
    $check($blocked, 'real guestbook foreign key protects match deletion');

    $check($request($admin, 'auth/logout', ['_csrf' => $csrf])[0] === 302, 'logout');
    $check($request($admin, 'admin-match/index')[0] === 403, 'logged out denied');
    // 新验证使用本次独立库，保留实际导入数据及开发库原貌。
    foreach (['0', '101', '-1', '1.5', '100000000000000000000'] as $size) {
        $check($request($guest, 'wc-match/index&page_size=' . $size)[0] === 400, 'invalid public page size ' . $size);
    }
    $check($request($guest, 'wc-match/index&page_size=100')[0] === 200, 'maximum page size accepted');
    $outsider = $insert('national_team', ['fifa_code' => 'ZZZ', 'name_zh' => 'TEST ONLY outsider', 'name_en' => 'TEST ONLY outsider']);
    $emptyTeam = $insert('national_team', ['fifa_code' => 'ZZW', 'name_zh' => 'TEST ONLY no games', 'name_en' => 'TEST ONLY no games']);
    $db->createCommand()->insert('tournament_team', ['tournament_id' => $tournament, 'team_id' => $emptyTeam, 'group_code' => 'A'])->execute();
    foreach (['home_team_id', 'away_team_id'] as $attribute) {
        $match = app\models\WcMatch::findOne($m1);
        $match->$attribute = $outsider;
        $check(!$match->validate() && $match->hasErrors($attribute), 'nonparticipant rejected ' . $attribute);
    }
    $match = app\models\WcMatch::findOne($m1);
    $match->home_team_id = 999999;
    $check(!$match->validate() && $match->hasErrors('home_team_id'), 'missing match team rejected');
    $match = app\models\WcMatch::findOne($m1);
    $match->home_team_id = $away;
    $match->away_team_id = $home;
    $check($match->validate(), 'valid participants keep existing match behavior');
    $eventFields = ['match_id' => $m1, 'team_id' => $home, 'period_code' => 'H1', 'event_type' => 'OTHER', 'minute' => 45, 'sort_order' => 0];
    foreach ([
        [['period_code' => 'ET'], 'period_code'],
        [['event_type' => 'UNKNOWN'], 'event_type'],
        [['minute' => null], 'minute'],
        [['period_code' => 'SHOOTOUT', 'minute' => null, 'added_minute' => 2], 'minute'],
        [['team_id' => $outsider], 'team_id'],
        [['team_id' => 999999], 'team_id'],
        [['match_id' => 999999], 'match_id'],
    ] as [$invalid, $attribute]) {
        $event = new app\models\MatchEvent(array_replace($eventFields, $invalid));
        $check(!$event->validate() && $event->hasErrors($attribute), 'event rejects invalid ' . $attribute);
    }
    foreach ([
        ['period_code' => 'SHOOTOUT', 'minute' => null],
        ['team_id' => null], ['team_id' => $away], ['minute' => 45, 'added_minute' => 2],
        ['period_code' => 'ET1', 'minute' => 105], ['period_code' => 'ET2', 'minute' => 120],
    ] as $valid) {
        $event = new app\models\MatchEvent(array_replace($eventFields, $valid));
        $check($event->validate(), 'valid event retains supported semantics');
    }
    $journey = new app\services\JourneyService();
    $check($journey->getTeamJourney($emptyTeam) === [], 'participant without matches gets empty journey');
    $check($request($guest, 'journey/view&team_id=' . $emptyTeam)[0] === 200, 'empty journey page');
    $check($request($guest, 'journey/view&team_id=' . $outsider)[0] === 404, 'nonparticipant journey 404');
    $check($request($guest, 'journey/view&team_id=999999')[0] === 404, 'missing team journey 404');
    $db->createCommand()->update('tournament', ['year' => 2030], ['id' => $tournament])->execute();
    $check($request($guest, 'journey/view&team_id=' . $home)[0] === 500, 'uninitialized 2026 is explicit server failure');
    $db->createCommand()->update('tournament', ['year' => 2026], ['id' => $tournament])->execute();
    $check(count($journey->getTeamJourney($home)) === 4, 'valid journey retains all matches');
    $csrf = $token($request($admin, 'auth/login')[1]);
    $request($admin, 'auth/login', ['_csrf' => $csrf, 'AdminLoginForm' => ['username' => 'b_test', 'password' => $password]]);
    $csrf = $token($request($admin, 'admin-story/create')[1]);
    $invalidMatch = array_replace($base, ['match_no' => 5, 'status' => 'SCHEDULED', 'home_team_id' => $outsider]);
    $check($request($admin, 'admin-match/create', ['_csrf' => $csrf, 'WcMatch' => $invalidMatch])[0] === 200,
        'nonparticipant match keeps editable form');
    $check((int) $scalar('SELECT COUNT(*) FROM wc_match WHERE match_no=5') === 0, 'invalid match is not saved');
    $validationStory = $insert('match_story', ['match_id' => $m1, 'title' => 'TEST ONLY validation',
        'summary' => 'TEST ONLY', 'body' => 'TEST ONLY']);
    $eventsBefore = (int) $scalar('SELECT COUNT(*) FROM match_event');
    foreach ([['minute' => ''], ['team_id' => $outsider], ['event_type' => 'UNKNOWN']] as $invalid) {
        $fields = array_replace($eventFields, $invalid);
        $check($request($admin, 'admin-story/event-create&id=' . $validationStory,
            ['_csrf' => $csrf, 'MatchEvent' => $fields])[0] === 200, 'invalid event keeps editable form');
        $check((int) $scalar('SELECT COUNT(*) FROM match_event') === $eventsBefore, 'invalid event is not saved');
    }
    $directory = new app\services\TeamDirectoryService();
    $memory = new app\services\MemoryCardService();
    $check($request($guest, 'site/index')[0] === 200, 'integrated A homepage');
    $check($request($guest, 'memory/card')[0] === 200, 'memory selection entry');
    $card = $memory->getEditor($home, $m2)['card'];
    $check($card['score'] === '2 : 2' && $card['penalties'] === '点球 4 : 3', 'memory score and penalties separated');
    $db->createCommand()->update('national_team', ['is_active' => 0], ['id' => $home])->execute();
    $activeIds = array_map('intval', array_column($directory->getTeams(), 'id'));
    $check(!in_array($home, $activeIds, true), 'public directory excludes inactive team');
    $history = $memory->getEditor($home, $m2);
    $check($history['card']['team_name'] === 'TEST ONLY home', 'inactive historical team remains readable');
    $check(in_array($home, array_map('intval', array_column($history['teams'], 'id')), true), 'historical selection retained');
    $check($memory->getEditor($away, $m2)['card']['home_name'] === 'TEST ONLY home', 'inactive opponent retains real name');
    $check($request($guest, 'memory/card&match_id=' . $m2)[0] === 200, 'historical match URL works for inactive home team');
    echo "PASS {$checks} real HTTP/database checks\n";
} finally {
    foreach ([$admin, $guest] as $client) {
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
        echo "Temporary test database removed\n";
    }
    if (is_file($router)) {
        unlink($router);
    }
}
