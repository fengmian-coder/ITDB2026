<?php
/**
 * WorldCup Journey - 世界杯旅程与球队表现动态图表
 * 模块：C 球队与统计；负责人：C，B 协助实现
 * 依赖：InsightController、InsightAsset、现有只读统计 API
 * 最近修改：2026-11-11
 */
declare(strict_types=1);

use app\assets\InsightAsset;
use yii\helpers\Html;
use yii\helpers\Json;
use yii\helpers\Url;

/** @var yii\web\View $this */
/** @var array $ranking */
/** @var array $calendar */
/** @var array $flow */
InsightAsset::register($this);
$this->title = '世界杯数据 | WorldCup Journey';
$this->params['breadcrumbs'][] = '数据总览';
$stageLabels = ['GROUP' => '小组赛', 'R32' => '32 强赛', 'R16' => '16 强赛',
    'QF' => '四分之一决赛', 'SF' => '半决赛', 'THIRD' => '季军赛', 'FINAL' => '决赛'];
$teamOptions = [];
foreach ($ranking as $team) {
    $teamOptions[$team['team_id']] = $team['team_name'] . ' · ' . $team['fifa_code'];
}
$seed = [
    'ranking' => $ranking, 'calendar' => $calendar, 'flow' => $flow,
    'stageLabels' => $stageLabels,
    'rankingUrl' => Url::to(['/api/insights', 'chart' => 'scoring-ranking']),
    'calendarUrl' => Url::to(['/api/insights', 'chart' => 'match-calendar']),
    'matchUrl' => Url::to(['/wc-match/view']),
    'flowUrl' => Url::to(['/api/insights', 'chart' => 'journey-flow']),
    'teamUrl' => Url::to(['/national-team/view']),
    'compareUrl' => Url::to(['/national-team/compare']),
    'journeyUrl' => Url::to(['/journey/view']),
];
?>
<div class="insight-page" id="insight-page">
    <header class="stats-hero insight-hero mb-4">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-3">
            <div class="insight-hero-copy">
                <p class="insight-season">2026 · 加拿大 / 墨西哥 / 美国</p>
                <h1>世界杯<br>数据总览</h1>
                <p class="mb-0">探索进球排名，比较球队表现，回看每一轮赛事</p>
            </div>
            <button type="button" class="btn btn-light" id="insight-refresh">刷新数据</button>
        </div>
        <nav class="insight-sections" aria-label="图表导航">
            <a href="#flow-title">晋级流向 <span aria-hidden="true">→</span></a>
            <a href="#ranking-title">进球排名 <span aria-hidden="true">→</span></a>
            <a href="#scatter-title">攻防表现 <span aria-hidden="true">→</span></a>
            <a href="#calendar-title">比赛日历 <span aria-hidden="true">→</span></a>
        </nav>
    </header>
    <noscript><p class="alert alert-info">启用 JavaScript 后可查看和刷新图表。下方数据表可以直接阅读</p></noscript>
    <div class="insight-grid">
    <section class="stats-panel flow-panel" aria-labelledby="flow-title">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-3">
            <div><h2 class="h4 mb-1" id="flow-title">通往冠军的旅程</h2>
                <p class="small text-body-secondary mb-0">流线宽度代表球队数量，选择球队可追踪已完成的比赛阶段</p></div>
            <div class="flow-picker">
                <?= Html::label('追踪球队', 'flow-team', ['class' => 'form-label small']) ?>
                <?= Html::dropDownList('flow_team', '', ['' => '全部球队'] + $teamOptions, ['id' => 'flow-team', 'class' => 'form-select form-select-sm']) ?>
            </div>
        </div>
        <p class="chart-status small text-body-secondary mt-2" id="flow-status" role="status" aria-live="polite"></p>
        <div class="flow-scroll" tabindex="0" role="region" aria-label="晋级流向图，窄屏可横向滚动">
            <div class="stats-chart" id="flow-chart" aria-label="球队晋级桑基图"></div>
        </div>
        <p class="flow-route small mt-2 mb-0" id="flow-route" aria-live="polite">选择球队查看路线</p>
        <details><summary>查看球队阶段记录</summary>
            <div class="stats-table table-responsive"><table class="table table-sm">
                <thead><tr><th scope="col">球队</th><th scope="col">已完成阶段及成绩</th></tr></thead>
                <tbody id="flow-table">
                <?php $nodeLabels = array_column($flow['nodes'], 'label', 'name'); ?>
                <?php foreach ($flow['teams'] as $row): ?>
                    <tr><td><?= Html::encode($teamOptions[$row['team_id']] ?? (string) $row['team_id']) ?></td>
                        <td><?= Html::encode(implode(' → ', array_map(static fn ($node) => $nodeLabels[$node], $row['path']))) ?></td></tr>
                <?php endforeach; ?>
                <?php if ($flow['teams'] === []): ?><tr><td colspan="2">暂无参赛球队数据</td></tr><?php endif; ?>
                </tbody>
            </table></div>
        </details>
    </section>
    <section class="stats-panel ranking-panel" aria-labelledby="ranking-title">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-3">
            <h2 class="h4 mb-0" id="ranking-title">球队进球排名</h2>
            <div class="d-flex align-items-center gap-2">
                <label for="ranking-limit" class="text-nowrap">展示范围</label>
                <select class="form-select form-select-sm" id="ranking-limit">
                    <option value="10">前 10 队</option><option value="20">前 20 队</option><option value="all">全部球队</option>
                </select>
            </div>
        </div>
        <p class="chart-status small text-body-secondary mt-2" id="ranking-status" role="status" aria-live="polite"></p>
        <div class="stats-chart stats-chart-ranking" id="ranking-chart" aria-label="球队进球排名柱状图"></div>
        <details><summary>查看完整排名数据表</summary>
            <div class="stats-table table-responsive"><table class="table table-sm">
                <caption>全部参赛球队，按进球数降序排列</caption>
                <thead><tr><th scope="col">球队</th><th scope="col">已完赛</th><th scope="col">进球</th><th scope="col">失球</th></tr></thead>
                <tbody id="ranking-table">
                    <?php foreach ($ranking as $team): ?>
                        <tr><td><?= Html::encode($team['team_name'] . ' · ' . $team['fifa_code']) ?></td><td><?= (int) $team['matches_played'] ?></td><td><?= (int) $team['goals_for'] ?></td><td><?= (int) $team['goals_against'] ?></td></tr>
                    <?php endforeach; ?>
                    <?php if ($ranking === []): ?><tr><td colspan="4">暂无参赛球队数据</td></tr><?php endif; ?>
                </tbody>
            </table></div>
        </details>
    </section>
    <section class="stats-panel scatter-panel" aria-labelledby="scatter-title">
                <h2 class="h4" id="scatter-title">攻防坐标</h2>
                <p class="small text-body-secondary mb-0">越靠右场均进球越多，越靠下场均失球越少。点击球队查看数据</p>
                <p class="chart-status small text-body-secondary mt-2" id="scatter-status" role="status" aria-live="polite"></p>
                <div class="stats-chart" id="scatter-chart" aria-label="各球队场均进球与场均失球散点图"></div>
                <div class="scatter-team-card" id="scatter-team-card" aria-live="polite">
                    <strong id="scatter-team-name">选择图中的球队</strong>
                    <p class="small mb-2" id="scatter-team-stats">也可以使用上方“追踪球队”选择器</p>
                    <div class="d-flex gap-3 flex-wrap">
                    <?= Html::a('查看球队', ['/national-team/view'], ['id' => 'scatter-team-link', 'class' => 'small', 'hidden' => true]) ?>
                    <?= Html::a('比较这支球队', ['/national-team/compare'], ['id' => 'scatter-compare-link', 'class' => 'small', 'hidden' => true]) ?>
                    <?= Html::a('查看比赛历程', ['/journey/view'], ['id' => 'scatter-journey-link', 'class' => 'btn btn-sm btn-primary', 'hidden' => true]) ?>
                    </div>
                </div>
                <p class="small text-body-secondary mt-2 mb-0">完整数值可在进球排名数据表中查阅，场均数据仅展示有完赛记录的球队</p>
            </section>
    <?= $this->render('_calendar', ['calendar' => $calendar]) ?>
    </div>
    <details class="insight-method mt-4"><summary>统计口径</summary>
        <p class="small text-body-secondary mt-2">数据取自当前数据库中的 2026 届已完赛记录。120 分钟比分为累计值，点球大战不计入进球。日历按北京时间归日并排除取消比赛，无完赛记录的日期进球显示“暂无”</p>
    </details>
</div>
<script type="application/json" id="insight-data"><?= Json::htmlEncode($seed) ?></script>
