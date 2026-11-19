<?php
/** Display semantics and guestbook validation; no database writes. */
declare(strict_types=1);
require dirname(__DIR__, 2) . '/vendor/autoload.php';
require dirname(__DIR__, 2) . '/vendor/yiisoft/yii2/Yii.php';
new yii\console\Application(require dirname(__DIR__, 2) . '/config/console.php');
use app\components\MatchDisplay;
use app\models\GuestbookMessage;

$checks = 0;
$check = static function (bool $ok, string $label) use (&$checks): void {
    if (!$ok) throw new RuntimeException('FAIL ' . $label);
    ++$checks;
};
$group = ['stage_code'=>'GROUP','status'=>'FINISHED','home_score_90'=>2,'away_score_90'=>0,
    'home_score_120'=>null,'away_score_120'=>null,'home_penalties'=>null,'away_penalties'=>null];
$check(MatchDisplay::scoreNote($group,'120') === '小组赛不设加时', 'group extra time is not missing data');
$check(MatchDisplay::scoreNote($group,'penalties') === '小组赛不设点球大战', 'group shootout is inapplicable');
$check(MatchDisplay::scoreNote($group,'90') === '', 'existing scores are preserved');
$knockout = array_replace($group, ['stage_code'=>'R16']);
$check(MatchDisplay::scoreNote($knockout,'120') === '未进行加时', 'finished regulation winner');
$check(MatchDisplay::scoreNote($knockout,'penalties') === '未进入点球大战', 'finished regulation winner has no shootout');
$extra = array_replace($knockout, ['home_score_90'=>1,'away_score_90'=>1,'home_score_120'=>2,'away_score_120'=>1]);
$check(MatchDisplay::scoreNote($extra,'120') === '', 'extra time values kept');
$check(MatchDisplay::scoreNote($extra,'penalties') === '未进入点球大战', 'extra-time winner has no shootout');
$unknown = array_replace($knockout, ['home_score_90'=>1,'away_score_90'=>1]);
$check(MatchDisplay::scoreNote($unknown,'120') === '是否进行及比分未注明', 'a null field alone cannot prove no extra time');
$check(MatchDisplay::scoreNote($unknown,'penalties') === '是否进行及比分未注明', 'a null field alone cannot prove no shootout');
$partial = array_replace($unknown,['home_score_120'=>2]);
$check(MatchDisplay::scoreNote($partial,'120') === '比分记录不完整', 'partial pair is not treated as inapplicable');
$missing = array_replace($knockout,['home_score_90'=>null,'away_score_90'=>null]);
$check(MatchDisplay::scoreNote($missing,'90') === '比分待补充', 'finished missing score');
foreach (['SCHEDULED'=>'尚未开赛','IN_PROGRESS'=>'比赛进行中','CANCELLED'=>'比赛已取消','POSTPONED'=>'比赛已延期'] as $status=>$label) {
    $check(MatchDisplay::scoreNote(array_replace($missing,['status'=>$status]),'90') === $label, 'unfinished state ' . $status);
}
$check(MatchDisplay::event('GOAL') === '进球' && MatchDisplay::event('YELLOW') === '黄牌', 'known event labels');
$check(MatchDisplay::event('OTHER') === '其他事件', 'OTHER does not guess a more specific event');
$check(MatchDisplay::period('H1') === '上半场' && MatchDisplay::period('ET2') === '加时下半场', 'known period labels');
$check(MatchDisplay::event('FUTURE_EVENT') === 'FUTURE_EVENT' && MatchDisplay::period('FUTURE_PERIOD') === 'FUTURE_PERIOD', 'unknown codes are retained');
$model = new GuestbookMessage(['nickname'=>'','content'=>'']);
$check(!$model->validate(['nickname','content']) && $model->hasErrors('nickname') && $model->hasErrors('content'), 'empty messages still rejected');
$check($model->getAttributeLabel('related_match_id') === '关联比赛', 'friendly validation field names');
echo 'PASS ', $checks, ' display and validation checks (no database writes)', PHP_EOL;
