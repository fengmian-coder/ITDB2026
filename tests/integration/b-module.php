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
    // C 球队后台通过实际管理员 HTTP 写入，仅操作本次独立测试库。
    $check($request($guest, 'admin-team/index')[0] === 403, 'guest denied team management');
    $check($request($admin, 'admin-team/index')[0] === 200, 'team management authorized');
    $check($request($admin, 'admin-team/delete&id=' . $home)[0] === 405, 'team GET deletion forbidden');
    $check($request($admin, 'admin-team/create', ['NationalTeam' => []])[0] === 400, 'team CSRF required');
    $csrf = $token($request($admin, 'admin-team/create')[1]);
    $teamFields = ['fifa_code' => 'ZZA', 'name_zh' => 'TEST_ONLY_TEAM', 'name_en' => 'TEST_ONLY_TEAM', 'is_active' => '1'];
    $check($request($admin, 'admin-team/create', ['_csrf' => $csrf, 'NationalTeam' => $teamFields])[0] === 302, 'team HTTP create');
    $crudTeam = (int) $scalar("SELECT id FROM national_team WHERE fifa_code='ZZA'");
    $check($crudTeam > 0, 'team persisted');
    [$status, $html] = $request($admin, 'admin-team/view&id=' . $crudTeam);
    $check($status === 200 && str_contains($html, '参赛信息') && str_contains($html, 'TournamentTeam[group_code]'), 'team detail and participation form rendered');
    $check($request($admin, 'admin-team/create', ['_csrf' => $csrf, 'NationalTeam' => $teamFields])[0] === 200, 'duplicate FIFA code rejected');
    $teamFields['fifa_code'] = 'bad';
    $check($request($admin, 'admin-team/create', ['_csrf' => $csrf, 'NationalTeam' => $teamFields])[0] === 200, 'invalid FIFA code rejected');
    $check($request($admin, 'admin-team/update&id=' . $crudTeam, ['_csrf' => $csrf, 'NationalTeam' => ['name_zh' => 'TEST_ONLY_UPDATED', 'id' => $home, 'created_at' => '2000-01-01']])[0] === 302, 'team update whitelist');
    $check($scalar('SELECT name_zh FROM national_team WHERE id=' . $crudTeam) === 'TEST_ONLY_UPDATED', 'team update saved to original ID');
    $check(!str_starts_with($scalar('SELECT created_at FROM national_team WHERE id=' . $crudTeam), '2000-01-01'), 'team audit fields excluded');
    $request($admin, 'admin-team/update&id=' . $crudTeam, ['_csrf' => $csrf, 'NationalTeam' => ['is_active' => '0', 'name_zh' => '<script>TEAM_ADMIN_XSS</script>']]);
    [$status, $html] = $request($admin, 'admin-team/index&NationalTeamSearch[is_active]=0');
    $check($status === 200 && str_contains($html, '&lt;script&gt;TEAM_ADMIN_XSS&lt;/script&gt;')
        && !str_contains($html, '<script>TEAM_ADMIN_XSS</script>'), 'inactive filter and team output escaping');
    $check($request($admin, 'admin-team/delete&id=' . $crudTeam, ['_csrf' => $csrf])[0] === 400, 'team deletion acknowledgement required');
    $check($request($admin, 'admin-team/update&id=' . $crudTeam, ['_csrf' => $csrf, 'NationalTeam' => ['is_active' => '']])[0] === 400, 'blank activation rejected');
    $check($request($admin, 'admin-team/view&id=0')[0] === 400, 'invalid team admin ID rejected');
    $check($request($admin, 'admin-team/view&id=999999')[0] === 404, 'absent team admin ID rejected');
    $check($request($admin, 'admin-team/index&NationalTeamSearch[is_active]=2')[0] === 400, 'invalid active filter rejected');
    $check($request($admin, 'admin-team/index&page=999')[0] === 400, 'team pagination bounds');
    $check($request($admin, 'admin-team/update&id=' . $crudTeam, ['_csrf' => $csrf, 'NationalTeam' => ['flag_path' => 'javascript:alert(1)']])[0] === 200, 'unsafe team image rejected');
    $request($admin, 'admin-team/delete&id=' . $crudTeam, ['_csrf' => $csrf, 'confirm_delete' => '1']);
    $check((int) $scalar('SELECT COUNT(*) FROM national_team WHERE id=' . $crudTeam) === 0, 'unreferenced team deleted');
    $request($admin, 'admin-team/delete&id=' . $home, ['_csrf' => $csrf, 'confirm_delete' => '1']);
    $check((int) $scalar('SELECT COUNT(*) FROM national_team WHERE id=' . $home) === 1, 'referenced team protected');
    $check($request($admin, 'admin-team/participation&id=' . $home, ['_csrf' => $csrf, 'TournamentTeam' => ['tournament_id' => $tournament, 'group_code' => 'A', 'final_result_code' => 'CHAMPION', 'team_id' => $away]])[0] === 302, 'participation update');
    $check($scalar('SELECT final_result_code FROM tournament_team WHERE team_id=' . $home) === 'CHAMPION'
        && $scalar('SELECT final_result_code FROM tournament_team WHERE team_id=' . $away) === null, 'participation IDs controlled by server');
    $request($admin, 'admin-team/participation&id=' . $home, ['_csrf' => $csrf, 'TournamentTeam' => ['tournament_id' => $tournament, 'group_code' => 'Z', 'final_result_code' => 'UNKNOWN']]);
    $check($scalar('SELECT group_code FROM tournament_team WHERE team_id=' . $home) === 'A', 'invalid participation not saved');
    $db->createCommand()->update('tournament_team', ['final_result_code' => null], ['team_id' => $home])->execute();
    $registeredTeam = $insert('national_team', ['fifa_code' => 'ZZB', 'name_zh' => 'TEST ONLY registration', 'name_en' => 'TEST ONLY registration']);
    $check($request($admin, 'admin-team/participation&id=' . $registeredTeam, ['_csrf' => $csrf, 'TournamentTeam' => [
        'tournament_id' => $tournament, 'group_code' => 'B', 'final_result_code' => '']])[0] === 302, 'new participation registered by HTTP');
    $check($scalar('SELECT group_code FROM tournament_team WHERE team_id=' . $registeredTeam) === 'B', 'new participation persisted');
    $db->createCommand()->delete('tournament_team', ['team_id' => $registeredTeam])->execute();
    $db->createCommand()->delete('national_team', ['id' => $registeredTeam])->execute();
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
    $teamStats = new app\services\TeamStatsService();
    $stats = $teamStats->getTeamStats($home);
    $check($stats['matches_played'] === 2 && $stats['wins'] === 1 && $stats['draws'] === 1
        && $stats['losses'] === 0, 'C statistics use finished matches and field results');
    $check($stats['goals_for'] === 3 && $stats['goals_against'] === 2 && $stats['penalty_wins'] === 1,
        'C cumulative extra time and penalty wins');
    $check($stats['goals_per_match'] === 1.5 && $stats['goals_against_per_match'] === 1
        && $stats['win_rate'] === 0.5, 'C per-match and win rate');
    $compare = $teamStats->getCompareStats($away, $home);
    $check($compare['left']['team_id'] === $away && $compare['right'] === $stats, 'C comparison keeps requested order');
    $ranking = $teamStats->getScoringRanking();
    $check(array_column($ranking, 'team_id') === [$home, $away], 'C scoring ranking');
    [$status, $json] = $request($guest, 'api/team-stats&id=' . $home);
    $payload = json_decode($json, true, 512, JSON_THROW_ON_ERROR);
    $check($status === 200 && $payload['success'] && $payload['data']['goals_for'] === 3
        && $payload['error'] === null, 'team API calls actual C service');
    [$status, $json] = $request($guest, 'api/team-compare&left_id=' . $away . '&right_id=' . $home);
    $payload = json_decode($json, true, 512, JSON_THROW_ON_ERROR);
    $check($status === 200 && $payload['data']['left']['team_id'] === $away
        && $payload['data']['right']['team_id'] === $home, 'comparison API real database');
    foreach (['api/team-stats&id=0', 'api/team-stats&id[]=1',
        'api/team-compare&left_id=' . $home . '&right_id=' . $home] as $route) {
        $check($request($guest, $route)[0] === 400, 'invalid statistics request ' . $route);
    }
    $check($request($guest, 'api/team-stats&id=999999')[0] === 404, 'absent statistics team 404');
    $check($request($guest, 'national-team/index')[0] === 200, 'C national team list');
    $activeNavigation = static function (string $html): array {
        $dom = new DOMDocument();
        $previous = libxml_use_internal_errors(true);
        $dom->loadHTML('<?xml encoding="UTF-8">' . $html);
        libxml_clear_errors(); libxml_use_internal_errors($previous);
        $links = (new DOMXPath($dom))->query('//header[@id="header"]//a[contains(concat(" ",normalize-space(@class)," ")," active ")]');
        return array_map(static fn ($link) => trim($link->textContent), iterator_to_array($links));
    };
    foreach (['national-team/index', 'national-team/view&id=' . $home, 'national-team/compare', 'journey/view&team_id=' . $home] as $route) {
        $check($activeNavigation($request($guest, $route)[1]) === ['球队'], 'team navigation active for ' . $route);
    }
    $check($activeNavigation($request($guest, 'wc-match/view&id=' . $m1)[1]) === ['比赛'], 'match detail navigation active');
    $check($activeNavigation($request($guest, 'insight/index')[1]) === ['数据总览'], 'overview navigation active');
    [$status, $html] = $request($guest, 'national-team/index');
    $check(str_contains($html, 'class="wc-team-card"') && !str_contains($html, 'badge text-bg-dark'), 'whole team card is link without code badge');
    [$status, $html] = $request($guest, 'national-team/index&q=ZZX');
    $check($status === 200 && str_contains($html, 'TEST ONLY home')
        && !str_contains($html, 'TEST ONLY away'), 'C actual team search');
    $check(str_contains($html, 'name="r" value="national-team/index"'), 'C GET search retains Yii route');
    $check($request($guest, 'national-team/index&page=2')[0] === 400, 'C out-of-range page rejected');
    $check($request($guest, 'national-team/index&q[]=ZZX')[0] === 400, 'C invalid search format rejected');
    $check($request($guest, 'national-team/view&id=' . $home)[0] === 200, 'C national team DNA');
    [$status, $html] = $request($guest, 'national-team/compare');
    $check($status === 200 && str_contains($html, '选好球队，开始比较'), 'PK initial empty form');
    $check(str_contains($html, 'name="r" value="national-team/compare"'), 'PK GET form retains route');
    [$status, $html] = $request($guest, 'national-team/compare&left_id=' . $home);
    $check($status === 200 && str_contains($html, 'value="' . $home . '" selected'), 'PK DNA deep link preselects participant');
    [$status, $html] = $request($guest, 'national-team/compare&left_id=' . $home . '&right_id=' . $away);
    $check($status === 200 && str_contains($html, '50.0%') && str_contains($html, '1.50'), 'PK C rates formatted without new aggregation');
    $check(str_contains($html, '交换球队') && str_contains($html, 'right_id=' . $home), 'PK swap preserves reverse IDs');
    $check(str_contains($html, 'id="compare-chart"') && str_contains($html, 'js/team-compare.js'), 'comparison owns WDL chart');
    preg_match('/<script type="application\/json" id="team-compare-data">(.*?)<\/script>/s', $html, $pairSeed);
    $pairData = json_decode($pairSeed[1] ?? '', true, 512, JSON_THROW_ON_ERROR);
    $check($pairData === $teamStats->getCompareStats($home, $away), 'WDL chart reuses exact comparison service values');
    foreach (['left_id=0', 'left_id[]=1', 'left_id=-1', 'left_id=1.5', 'left_id=100000000000000000000',
        'left_id=' . $home . '&right_id=' . $home] as $query) {
        $check($request($guest, 'national-team/compare&' . $query)[0] === 400, 'PK invalid parameter ' . $query);
    }
    $check($request($guest, 'national-team/compare&right_id=999999')[0] === 404, 'PK missing partial participant 404');
    [$status, $html] = $request($guest, 'insight/index');
    $check($status === 200 && str_contains($html, 'id="ranking-chart"')
        && !str_contains($html, 'id="compare-chart"') && str_contains($html, 'id="stages-chart"'), 'overview separates tournament charts from team comparison');
    $check(str_contains($html, 'id="scatter-team-link"') && str_contains($html, 'id="scatter-compare-link"'), 'overview connects selected team to profile and comparison');
    $check(str_contains($html, 'vendor/echarts/echarts.min.js') && str_contains($html, 'js/insight-charts.js'), 'single local chart library registered');
    $extractChartData = static function (string $html): array {
        preg_match('/<script type="application\/json" id="insight-data">(.*?)<\/script>/s', $html, $match);
        return json_decode($match[1] ?? '', true, 512, JSON_THROW_ON_ERROR);
    };
    $chartData = $extractChartData($html);
    $check(array_column($chartData['ranking'], 'team_id') === [$home, $away], 'chart seed uses complete C ranking');
    $check($chartData['ranking'][0]['goals_for'] === 3 && $chartData['ranking'][0]['draws'] === 1
        && $chartData['ranking'][0]['penalty_wins'] === 1, 'chart data separates extra time and penalties');
    $check($chartData['stages'] === $summary, 'chart stages equal B service');
    $check(str_contains($html, 'id="flow-chart"') && str_contains($html, 'id="scatter-chart"'), 'flow and scatter chart containers');
    $flowService = new app\services\JourneyFlowService();
    $flow = $flowService->getFlow();
    $check($chartData['flow'] === $flow, 'flow SSR uses real service data');
    $check(count($flow['teams']) === 2 && $flow['links'][0]['value'] === 2, 'flow counts participants once per stage');
    $check($flow['teams'][0]['path'] === ['entry', 'GROUP', 'FINAL', 'pending:FINAL'], 'flow uses observed stages without inventing missing rounds or winner');
    $check(!in_array('result:CHAMPION', array_column($flow['nodes'], 'name'), true), 'penalty winner alone does not invent final result');
    [$flowStatus, $flowJson] = $request($guest, 'api/insights&chart=journey-flow');
    $check($flowStatus === 200 && json_decode($flowJson, true)['data'] === $flow, 'flow API matches SSR');
    $db->createCommand()->update('tournament_team', ['final_result_code' => 'CHAMPION'], ['team_id' => $home])->execute();
    $confirmedFlow = $flowService->getFlow();
    $check(end($confirmedFlow['teams'][0]['path']) === 'result:CHAMPION', 'flow reads recorded final result');
    $db->createCommand()->update('tournament_team', ['final_result_code' => null], ['team_id' => $home])->execute();
    $repeatGroup = $insert('wc_match', array_replace($base, ['match_no' => 101, 'status' => 'FINISHED', 'home_score_90' => 0, 'away_score_90' => 0]));
    $check($flowService->getFlow() === $flow, 'repeated group matches do not multiply team flow');
    $db->createCommand()->delete('wc_match', ['id' => $repeatGroup])->execute();
    $db->createCommand()->update('wc_match', ['stage_code' => 'THIRD'], ['id' => $m2])->execute();
    $db->createCommand()->update('tournament_team', ['final_result_code' => 'THIRD'], ['team_id' => $home])->execute();
    $thirdFlow = $flowService->getFlow();
    $thirdLabels = array_column($thirdFlow['nodes'], 'label', 'name');
    $check($thirdLabels['THIRD'] === '季军赛' && $thirdLabels['result:THIRD'] === '季军', 'third place round differs from third place result');
    $check($thirdFlow['teams'][0]['path'] === ['entry', 'GROUP', 'THIRD', 'result:THIRD'], 'third place path does not pass through final');
    $db->createCommand()->update('wc_match', ['stage_code' => 'FINAL'], ['id' => $m2])->execute();
    $db->createCommand()->update('tournament_team', ['final_result_code' => null], ['team_id' => $home])->execute();
    [$status, $json] = $request($guest, 'api/insights&chart=scoring-ranking');
    $payload = json_decode($json, true, 512, JSON_THROW_ON_ERROR);
    $check($status === 200 && $payload['data'] === $ranking, 'ranking API preserves C contract');
    $check($request($guest, 'api/insights&chart=unknown')[0] === 400, 'unknown chart still rejected');
    $check($request($guest, 'api/insights&chart[]=scoring-ranking')[0] === 400, 'array chart rejected');
    // 仅修改独立测试库，验证刷新 API 确实重读已保存的比分。
    $db->createCommand()->update('wc_match', ['home_score_90' => 2], ['id' => $m1])->execute();
    $updatedRanking = json_decode($request($guest, 'api/insights&chart=scoring-ranking')[1], true, 512, JSON_THROW_ON_ERROR)['data'];
    $updatedStages = json_decode($request($guest, 'api/insights&chart=stage-goals')[1], true, 512, JSON_THROW_ON_ERROR)['data'];
    $check($updatedRanking[0]['goals_for'] === 4 && $updatedStages[0]['total_goals'] === 2, 'saved score refresh changes ranking and stage API');
    $db->createCommand()->update('wc_match', ['home_score_90' => 1], ['id' => $m1])->execute();
    $scheduled = $insert('wc_match', array_replace($base, ['match_no' => 100, 'stage_code' => 'SF', 'group_code' => null, 'status' => 'SCHEDULED']));
    $check($flowService->getFlow() === $flow, 'scheduled round does not imply played advancement');
    $stagesWithNull = json_decode($request($guest, 'api/insights&chart=stage-goals')[1], true, 512, JSON_THROW_ON_ERROR)['data'];
    $sf = array_values(array_filter($stagesWithNull, static fn ($row) => $row['stage_code'] === 'SF'))[0];
    $check($sf['match_count'] === 1 && $sf['finished_match_count'] === 0 && $sf['total_goals'] === null, 'chart stage without finished games keeps null');
    $db->createCommand()->delete('wc_match', ['id' => $scheduled])->execute();
    $check($request($guest, 'site/explore')[0] === 302, 'A explore delegates to C list');
    $detail = $service->getMatchDetail($m2);
    $check($detail['home_team']['id'] === $home && $detail['away_team']['id'] === $away
        && $detail['winner_team']['id'] === $home, 'three match team roles eager loaded');
    $check($list['items'][0]['home_team']['name_zh'] === 'TEST ONLY home'
        && count($list['teams']) === 2, 'match list exposes real names and participant options');
    [$status, $html] = $request($guest, 'wc-match/index&stage=GROUP&team_id=' . $home . '&page_size=1');
    $check($status === 200 && str_contains($html, 'TEST ONLY away')
        && str_contains($html, 'name="team_id"'), 'named match filter and cards');
    $check(str_contains($html, 'page=2') && str_contains($html, 'stage=GROUP')
        && str_contains($html, 'team_id=' . $home) && str_contains($html, 'page_size=1'), 'pagination retains filters');
    $db->createCommand()->update('tournament_team', ['final_result_code' => 'CHAMPION'],
        ['tournament_id' => $tournament, 'team_id' => $home])->execute();
    $profileService = new app\services\JourneyService();
    $check($profileService->getTeamProfile($home)['final_result_code'] === 'CHAMPION', 'Journey reads C final result');
    $check($profileService->getTeamJourney($home)[0]['opponent']['name_zh'] === 'TEST ONLY away', 'Journey real opponent name');
    [$status, $html] = $request($guest, 'journey/view&team_id=' . $home);
    $check($status === 200 && str_contains($html, 'TEST ONLY home的世界杯之旅')
        && str_contains($html, '冠军'), 'Journey profile and final result rendered');
    $db->createCommand()->update('tournament_team', ['final_result_code' => null],
        ['tournament_id' => $tournament, 'team_id' => $home])->execute();

    [$status, $html] = $request($admin, 'admin-story/create');
    $csrf = $token($html);
    $storyData = ['match_id' => $m2, 'title' => 'TEST ONLY story', 'summary' => 'TEST ONLY summary', 'body' => 'TEST_ONLY_PRIVATE_BODY <script>alert(1)</script>', 'is_published' => '0'];
    $check($request($admin, 'admin-story/create', ['_csrf' => $csrf, 'MatchStory' => $storyData])[0] === 302, 'story created by HTTP');
    $story = (int) $scalar('SELECT id FROM match_story');
    $check($scalar('SELECT created_at FROM match_story') !== null, 'database audit defaults');
    $check($service->getMatchDetail($m2)['story'] === null, 'draft hidden');
    $check($request($admin, 'admin-story/create', ['_csrf' => $csrf, 'MatchStory' => $storyData])[0] === 200 && (int) $scalar('SELECT COUNT(*) FROM match_story') === 1, 'unique story per match');
    $eventData = ['team_id' => $home, 'minute' => '45', 'added_minute' => '2', 'period_code' => 'H1', 'event_type' => 'OTHER', 'description' => 'TEST_ONLY_EVENT_LATE', 'sort_order' => 10];
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
    $check($detail['events'][0]['team']['id'] === $home, 'published event team eager loaded');
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
    [$status, $html] = $request($guest, 'national-team/index&q=ZZZ');
    $check($status === 200 && !str_contains($html, 'TEST ONLY outsider'), 'public list excludes active nonparticipant');
    $otherYear = $insert('tournament', ['year' => 2030, 'name' => 'TEST ONLY other year', 'start_date' => '2030-06-01', 'end_date' => '2030-07-01']);
    $db->createCommand()->insert('tournament_team', ['tournament_id' => $otherYear, 'team_id' => $outsider, 'group_code' => 'A'])->execute();
    $check(!str_contains($request($guest, 'national-team/index&q=ZZZ')[1], 'TEST ONLY outsider'), 'other tournament participation excluded');
    $db->createCommand()->delete('tournament_team', ['tournament_id' => $otherYear])->execute();
    $db->createCommand()->delete('tournament', ['id' => $otherYear])->execute();
    $emptyTeam = $insert('national_team', ['fifa_code' => 'ZZW', 'name_zh' => 'TEST ONLY no games', 'name_en' => 'TEST ONLY no games']);
    $db->createCommand()->insert('tournament_team', ['tournament_id' => $tournament, 'team_id' => $emptyTeam, 'group_code' => 'A'])->execute();
    $emptyStats = $teamStats->getTeamStats($emptyTeam);
    $check($emptyStats['matches_played'] === 0 && $emptyStats['goals_for'] === 0
        && $emptyStats['goals_per_match'] === null && $emptyStats['win_rate'] === null,
        'C no finished matches retains null averages');
    $emptyFlow = $flowService->getFlow();
    $emptyPath = array_values(array_filter($emptyFlow['teams'], static fn ($team) => $team['team_id'] == $emptyTeam))[0];
    $check($emptyPath['path'] === ['entry', 'pending:entry'], 'participant without games awaits matches');
    foreach (['api/team-stats&id=' . $outsider, 'wc-match/index&team_id=' . $outsider,
        'wc-match/index&team_id=999999'] as $route) {
        $check($request($guest, $route)[0] === 404, 'nonparticipant or absent team rejected ' . $route);
    }
    $check($service->getMatchList(['team_id' => $emptyTeam])['items'] === [], 'participant without matches empty list');
    $check($request($guest, 'national-team/view&id=' . $emptyTeam)[0] === 200, 'C empty DNA page');
    [$status, $html] = $request($guest, 'national-team/compare&left_id=' . $emptyTeam . '&right_id=' . $away);
    $check($status === 200 && str_contains($html, '暂无'), 'PK no-games participant renders nullable rates');
    $check($request($guest, 'national-team/compare&left_id=' . $outsider)[0] === 404, 'PK nonparticipant rejected');
    $check($request($guest, 'national-team/compare&left_id=' . $home . '&right_id=' . $outsider)[0] === 404, 'PK nonparticipant in pair rejected');
    // 全部赛程取消时检查页面空状态与 API；测试后立即恢复原状态。
    $savedMatches = $db->createCommand('SELECT * FROM wc_match WHERE id IN (:first, :second)', [':first' => $m1, ':second' => $m2])->queryAll();
    $db->createCommand()->update('wc_match', ['status' => 'CANCELLED', 'home_score_90' => null,
        'away_score_90' => null, 'home_score_120' => null, 'away_score_120' => null,
        'home_penalties' => null, 'away_penalties' => null, 'winner_team_id' => null], ['id' => [$m1, $m2]])->execute();
    [$status, $html] = $request($guest, 'insight/index');
    $emptyCharts = $extractChartData($html);
    $check($status === 200 && array_sum(array_column($emptyCharts['ranking'], 'matches_played')) === 0,
        'charts keep real zero counts when no finished matches');
    $check($emptyCharts['stages'][0]['total_goals'] === null, 'SSR seed keeps no-finished null');
    $db->createCommand()->update('wc_match', ['status' => 'CANCELLED'], ['match_no' => 4])->execute();
    $emptyCharts = $extractChartData($request($guest, 'insight/index')[1]);
    $check($emptyCharts['stages'] === [], 'cancelled-only schedule produces empty stage data');
    $check(count($emptyCharts['flow']['links']) === 1 && $emptyCharts['flow']['links'][0]['target'] === 'pending:entry', 'cancelled matches excluded from flow');
    $db->createCommand()->update('wc_match', ['status' => 'SCHEDULED'], ['match_no' => 4])->execute();
    foreach ($savedMatches as $savedMatch) {
        $db->createCommand()->update('wc_match', $savedMatch, ['id' => $savedMatch['id']])->execute();
    }
    foreach (['national-team/compare', 'insight/index'] as $route) {
        $check($request($guest, $route, [])[0] === 405, 'public chart POST method rejected ' . $route);
        $check($request($admin, $route, ['_csrf' => $token($request($admin, 'auth/login')[1])])[0] === 405,
            'public chart POST with CSRF method rejected ' . $route);
    }
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
    [$status, $html] = $request($guest, 'insight/index');
    $check($status === 200 && str_contains($html, '赛事数据尚未就绪'), 'uninitialized chart page has useful empty state');
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
    $profileCount = static function (callable $operation) use ($db): int {
        $db->enableProfiling = true;
        $logger = Yii::getLogger();
        $logger->flush();
        $operation();
        return count($logger->getProfiling(['yii\\db\\Command::query']));
    };
    $smallQueryCount = $profileCount(static fn () => $service->getMatchList(['page_size' => 1]));
    $largeQueryCount = $profileCount(static fn () => $service->getMatchList(['page_size' => 100]));
    $check($smallQueryCount <= 8 && $largeQueryCount <= 8, 'match related queries bounded independently of page size');
    $check($teamStats->getTeamStats($home)['matches_played'] === 2, 'C statistics retains inactive historical team');
    $check($service->getMatchDetail($m2)['home_team']['name_zh'] === 'TEST ONLY home', 'match retains inactive historical team');
    $check($journey->getTeamJourney($away)[0]['opponent']['name_zh'] === 'TEST ONLY home', 'Journey retains inactive opponent');
    $check($request($guest, 'national-team/view&id=' . $home)[0] === 200, 'C inactive historical DNA');
    $check($request($guest, 'national-team/compare&left_id=' . $home . '&right_id=' . $away)[0] === 200, 'PK retains inactive historical participant');
    $check($request($guest, 'journey/view&team_id=' . $home)[0] === 200, 'named inactive historical journey');
    $check($memory->getEditor($away, $m2)['card']['stats']['team_id'] === $away, 'A memory receives C statistics');
    // 临时库中检查球队文本与素材路径的输出边界。
    $db->createCommand()->update('national_team', ['name_zh' => '<script>TEAM_XSS</script>',
        'flag_path' => 'javascript:alert(1)'], ['id' => $home])->execute();
    foreach (['wc-match/index', 'wc-match/view&id=' . $m2, 'journey/view&team_id=' . $away,
        'national-team/compare&left_id=' . $home . '&right_id=' . $away, 'insight/index'] as $route) {
        [$status, $html] = $request($guest, $route);
        $check($status === 200 && str_contains($html, '&lt;script&gt;TEAM_XSS&lt;/script&gt;')
            && !str_contains($html, '<script>TEAM_XSS</script>') && !str_contains($html, 'src="javascript:'),
            'team output escaped ' . $route);
    }
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
