<?php
/**
 * WorldCup Journey - 首页与纪念卡的本机数据库 / HTTP 验收。
 * 模块：A 首页与纪念卡；负责人：A。
 * 依赖：C 的表结构、B 的共享服务、本地运行的 Web 服务；不验证浏览器 PNG 下载。
 * 最近修改：2026-11-10。
 * 随机测试球队与比赛在 finally 中清理，不保留虚构赛事数据。
 */
declare(strict_types=1);

use app\services\MemoryCardService;
use app\services\MatchQueryService;
use yii\db\Query;
use yii\web\BadRequestHttpException;

$root = dirname(__DIR__, 2);
require $root . '/vendor/autoload.php';
require $root . '/vendor/yiisoft/yii2/Yii.php';
defined('YII_DEBUG') || define('YII_DEBUG', false);
defined('YII_ENV') || define('YII_ENV', 'test');
$app = new yii\console\Application(require $root . '/config/console.php');
$db = $app->db;
if ($db->createCommand('SELECT DATABASE()')->queryScalar() !== 'worldcup_journey') {
    throw new RuntimeException('请使用本地 worldcup_journey 开发数据库。');
}
$curl = curl_init();
$get = static function (string $route) use ($curl): array {
    curl_setopt_array($curl, [CURLOPT_URL => 'http://127.0.0.1:8080/index.php?r=' . $route,
        CURLOPT_RETURNTRANSFER => true, CURLOPT_NOPROXY => '127.0.0.1', CURLOPT_TIMEOUT => 10]);
    $body = curl_exec($curl);
    if ($body === false) throw new RuntimeException('本地 Web 服务不可用。');
    return ['status' => curl_getinfo($curl, CURLINFO_RESPONSE_CODE), 'body' => $body];
};
$checks = 0;
$check = static function (bool $ok, string $label) use (&$checks): void {
    if (!$ok) throw new RuntimeException('FAIL: ' . $label);
    echo 'PASS: ' . $label . PHP_EOL;
    ++$checks;
};
$insert = static function (string $table, array $data) use ($db): int {
    $db->createCommand()->insert($table, $data)->execute();
    return (int) $db->getLastInsertID();
};
$marker = 'a_memory_' . bin2hex(random_bytes(5));
$createdTournament = false;
$tournamentId = null;
$stadiumId = null;
$teamIds = [];
$matchId = null;
$storyId = null;
$tagId = null;
try {
    $check($get('site/index')['status'] === 200 && $get('memory/card')['status'] === 200, '首页和纪念卡入口无需登录');
    $check($get('memory/card&team_id=-1')['status'] === 400
        && $get('memory/card&match_id%5B%5D=1')['status'] === 400, '纪念卡拒绝非法和数组 ID');
    $check($get('site/explore&q%5B%5D=1')['status'] === 400, '搜索词拒绝数组输入');
    $tournamentId = (new Query())->from('tournament')->select('id')->where(['year' => 2026])->scalar();
    if ($tournamentId === false) {
        $tournamentId = $insert('tournament', ['year' => 2026, 'name' => $marker, 'start_date' => '2026-06-01', 'end_date' => '2026-08-01']);
        $createdTournament = true;
    }
    $occupied = (new Query())->from('wc_match')->select('match_no')->where(['tournament_id' => $tournamentId])->column();
    $freeNumbers = array_values(array_diff(range(1, 104), $occupied));
    if ($freeNumbers === []) throw new RuntimeException('本地 104 场数据已齐全，请在独立测试数据库执行此验收。');
    $stadiumId = $insert('stadium', ['name' => $marker, 'city' => 'Test City', 'country_code' => 'US', 'timezone_name' => 'UTC']);
    foreach (range(1, 3) as $index) {
        do {
            $code = chr(random_int(65, 90)) . chr(random_int(65, 90)) . chr(random_int(65, 90));
        } while ((new Query())->from('national_team')->where(['fifa_code' => $code])->exists());
        $id = $insert('national_team', ['fifa_code' => $code, 'name_zh' => $marker . '_' . $index, 'name_en' => $marker . '_' . $index, 'is_active' => 1]);
        $teamIds[] = $id;
        $db->createCommand()->insert('tournament_team', ['tournament_id' => $tournamentId, 'team_id' => $id, 'group_code' => 'A'])->execute();
    }
    $matchId = $insert('wc_match', [
        'tournament_id' => $tournamentId, 'match_no' => $freeNumbers[0],
        'home_team_id' => $teamIds[0], 'away_team_id' => $teamIds[1], 'stadium_id' => $stadiumId,
        'kickoff_utc' => '2026-07-01 20:00:00', 'stage_code' => 'R16', 'status' => 'FINISHED',
        'home_score_90' => 1, 'away_score_90' => 1, 'home_score_120' => 2, 'away_score_120' => 2,
        'home_penalties' => 4, 'away_penalties' => 3, 'winner_team_id' => $teamIds[0],
    ]);
    $storyId = $insert('match_story', ['match_id' => $matchId, 'title' => $marker,
        'summary' => $marker . '_draft', 'body' => $marker, 'is_published' => 0]);
    $service = new MemoryCardService();
    $data = $service->getEditor($teamIds[0], $matchId);
    $check($data['card']['score'] === '2 : 2' && $data['card']['penalties'] === '点球 4 : 3', '复用 B 的累计加时比分，点球分开显示');
    $check($data['card']['date'] === '2026.07.02 04:00 北京时间', 'UTC 跨日转换为北京时间');
    $check($data['card']['story'] === null, '草稿故事不会进入纪念卡');
    $db->createCommand()->update('match_story', ['is_published' => 1], ['id' => $storyId])->execute();
    $check($service->getEditor($teamIds[1], $matchId)['card']['story'] === $marker . '_draft', '已发布故事可以进入纪念卡');
    $check((new MatchQueryService())->getMatchDetail($matchId)['tags'] === [], '已发布故事没有标签时正常返回空数组');
    $tagId = $insert('match_tag', ['code' => $marker, 'name' => '联调测试标签']);
    $db->createCommand()->insert('match_story_tag', ['story_id' => $storyId, 'tag_id' => $tagId])->execute();
    $tags = (new MatchQueryService())->getMatchDetail($matchId)['tags'];
    $check(count($tags) === 1 && $tags[0]['code'] === $marker, '已发布故事可以预加载关联标签');
    $check($service->getEditor(null, $matchId)['selectedTeamId'] === $teamIds[0], '仅传比赛时自动选择主队');
    $wrongTeamRejected = false;
    try { $service->getEditor($teamIds[2], $matchId); } catch (BadRequestHttpException $exception) { $wrongTeamRejected = true; }
    $check($wrongTeamRejected, '不能组合不相关的球队和比赛');
    $editor = $get('memory/card&team_id=' . $teamIds[0] . '&match_id=' . $matchId);
    $check($editor['status'] === 200 && str_contains($editor['body'], 'id="memory-canvas"')
        && str_contains($editor['body'], 'id="memory-data"'), '真实接口生成纪念卡 Canvas 与数据');
    $check($get('memory/card&team_id=' . $teamIds[2] . '&match_id=' . $matchId)['status'] === 400, 'HTTP 接口同样拒绝不相关球队');
    $homepage = $get('site/index')['body'];
    $check(str_contains($homepage, '卡片示例') && str_contains($homepage, 'memory-example-ferran.png')
        && !str_contains($homepage, $marker . '_1'), '首页固定展示个人作业原型样例，不与正式比赛数据混用');
    $search = $get('site/explore&q=' . rawurlencode($marker . '_3'));
    $check($search['status'] === 200 && str_contains($search['body'], $marker . '_3')
        && !str_contains($search['body'], $marker . '_1'), '球队入口可以按关键词筛选');
    $db->createCommand()->update('wc_match', ['status' => 'SCHEDULED',
        'home_score_90' => null, 'away_score_90' => null, 'home_score_120' => null, 'away_score_120' => null,
        'home_penalties' => null, 'away_penalties' => null, 'winner_team_id' => null], ['id' => $matchId])->execute();
    $scheduled = $service->getEditor($teamIds[0], $matchId)['card'];
    $check($scheduled['score'] === '尚未开赛' && $scheduled['penalties'] === null, '未开赛比赛不会伪造为 0 比 0');
    echo 'SUCCESS: ' . $checks . ' checks passed.' . PHP_EOL;
} finally {
    if ($storyId !== null) $db->createCommand()->delete('match_story', ['id' => $storyId])->execute();
    if ($tagId !== null) $db->createCommand()->delete('match_tag', ['id' => $tagId])->execute();
    if ($matchId !== null) $db->createCommand()->delete('wc_match', ['id' => $matchId])->execute();
    foreach ($teamIds as $id) {
        $db->createCommand()->delete('tournament_team', ['tournament_id' => $tournamentId, 'team_id' => $id])->execute();
        $db->createCommand()->delete('national_team', ['id' => $id])->execute();
    }
    if ($stadiumId !== null) $db->createCommand()->delete('stadium', ['id' => $stadiumId])->execute();
    if ($createdTournament) $db->createCommand()->delete('tournament', ['id' => $tournamentId])->execute();
    curl_close($curl);
}
