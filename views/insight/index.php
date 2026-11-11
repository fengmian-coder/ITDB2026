<?php
/**
 * WorldCup Journey - 三张数据库驱动动态图表
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
/** @var array $stages */
InsightAsset::register($this);
$this->title = '世界杯数据 | WorldCup Journey';
$this->params['breadcrumbs'][] = '数据图表';
$stageLabels = ['GROUP' => '小组赛', 'R32' => '32 强赛', 'R16' => '16 强赛',
    'QF' => '四分之一决赛', 'SF' => '半决赛', 'THIRD' => '季军赛', 'FINAL' => '决赛'];
$teamOptions = [];
foreach ($ranking as $team) {
    $teamOptions[$team['team_id']] = $team['team_name'] . ' · ' . $team['fifa_code'];
}
// 默认选进球排名前两支真实参赛球队，完整对比由既有 API 提供。
$initialCompare = count($ranking) >= 2 ? ['left' => $ranking[0], 'right' => $ranking[1]] : null;
$seed = [
    'ranking' => $ranking, 'stages' => $stages, 'comparison' => $initialCompare,
    'stageLabels' => $stageLabels,
    'rankingUrl' => Url::to(['/api/insights', 'chart' => 'scoring-ranking']),
    'stagesUrl' => Url::to(['/api/insights', 'chart' => 'stage-goals']),
    'compareUrl' => Url::to(['/api/team-compare']),
];
?>
<div class="insight-page" id="insight-page">
    <header class="stats-hero insight-hero mb-4">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-3">
            <div class="insight-hero-copy">
                <p class="insight-season">2026 · 加拿大 / 墨西哥 / 美国</p>
                <h1>世界杯<br>数据看板</h1>
                <p class="mb-0">探索进球排名，比较球队表现，回看每一轮赛事。</p>
            </div>
            <button type="button" class="btn btn-light" id="insight-refresh">刷新数据</button>
        </div>
        <nav class="insight-sections" aria-label="图表导航">
            <a href="#ranking-title">进球排名 <span aria-hidden="true">↗</span></a>
            <a href="#compare-title">球队对比 <span aria-hidden="true">↗</span></a>
            <a href="#stages-title">赛事进程 <span aria-hidden="true">↗</span></a>
        </nav>
    </header>
    <noscript><p class="alert alert-info">启用 JavaScript 后可查看和刷新图表。下方数据表可以直接阅读。</p></noscript>
    <div class="insight-grid">
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
                <caption>全部参赛球队，按进球数降序排列。</caption>
                <thead><tr><th scope="col">球队</th><th scope="col">已完赛</th><th scope="col">进球</th><th scope="col">失球</th></tr></thead>
                <tbody id="ranking-table">
                    <?php foreach ($ranking as $team): ?>
                        <tr><td><?= Html::encode($team['team_name'] . ' · ' . $team['fifa_code']) ?></td><td><?= (int) $team['matches_played'] ?></td><td><?= (int) $team['goals_for'] ?></td><td><?= (int) $team['goals_against'] ?></td></tr>
                    <?php endforeach; ?>
                    <?php if ($ranking === []): ?><tr><td colspan="4">暂无参赛球队数据。</td></tr><?php endif; ?>
                </tbody>
            </table></div>
        </details>
    </section>
            <section class="stats-panel compare-panel" aria-labelledby="compare-title">
                <h2 class="h4" id="compare-title">两队胜平负</h2>
                <div class="row g-2 mt-1">
                    <?php foreach (['left' => '第一支球队', 'right' => '第二支球队'] as $side => $label): ?>
                        <div class="col-sm-6">
                            <?= Html::label($label, 'insight-' . $side, ['class' => 'form-label small']) ?>
                            <?= Html::dropDownList($side . '_id', $initialCompare[$side]['team_id'] ?? null, $teamOptions, ['id' => 'insight-' . $side, 'class' => 'form-select form-select-sm', 'disabled' => count($ranking) < 2]) ?>
                        </div>
                    <?php endforeach; ?>
                </div>
                <div class="insight-duel" aria-label="所选球队胜率">
                    <?php foreach (['left', 'right'] as $side): $selected = $initialCompare[$side] ?? null; ?>
                        <div class="insight-team insight-team-<?= $side ?>">
                            <span class="insight-team-code" id="compare-<?= $side ?>-code"><?= Html::encode($selected['fifa_code'] ?? '—') ?></span>
                            <strong id="compare-<?= $side ?>-name"><?= Html::encode($selected['team_name'] ?? '选择球队') ?></strong>
                            <span class="insight-team-rate" id="compare-<?= $side ?>-rate"><?= isset($selected['win_rate']) ? Html::encode(number_format((float) $selected['win_rate'] * 100, 1) . '%') : '暂无' ?></span>
                            <span class="small text-body-secondary">胜率</span>
                        </div>
                    <?php endforeach; ?>
                </div>
                <p class="chart-status small text-body-secondary mt-2" id="compare-status" role="status" aria-live="polite"></p>
                <div class="stats-chart" id="compare-chart" aria-label="两队胜平负分组柱状图"></div>
                <details><summary>查看对比数据表</summary>
                    <div class="table-responsive"><table class="table table-sm">
                        <caption>场上平局计为平局，点球胜场单列。</caption>
                        <thead><tr><th scope="col">球队</th><th scope="col">胜</th><th scope="col">平</th><th scope="col">负</th><th scope="col">点球胜</th></tr></thead>
                        <tbody id="compare-table">
                            <?php foreach ($initialCompare ?? [] as $team): ?>
                                <tr><td><?= Html::encode($team['team_name'] . ' · ' . $team['fifa_code']) ?></td><td><?= (int) $team['wins'] ?></td><td><?= (int) $team['draws'] ?></td><td><?= (int) $team['losses'] ?></td><td><?= (int) $team['penalty_wins'] ?></td></tr>
                            <?php endforeach; ?>
                            <?php if ($initialCompare === null): ?><tr><td colspan="5">至少需要两支参赛球队。</td></tr><?php endif; ?>
                        </tbody>
                    </table></div>
                </details>
                <?= Html::a('打开完整球队 PK', ['/national-team/compare', 'left_id' => $initialCompare['left']['team_id'] ?? null, 'right_id' => $initialCompare['right']['team_id'] ?? null], ['class' => 'btn btn-sm btn-outline-primary mt-2', 'id' => 'insight-pk-link']) ?>
            </section>
            <section class="stats-panel stages-panel" aria-labelledby="stages-title">
                <div class="d-flex flex-wrap justify-content-between gap-2 align-items-center">
                    <h2 class="h4 mb-0" id="stages-title">阶段场次与进球</h2>
                    <p class="small text-body-secondary mb-0">从小组赛到决赛 · 柱状图为场次，折线为进球</p>
                </div>
                <p class="chart-status small text-body-secondary mt-2" id="stages-status" role="status" aria-live="polite"></p>
                <div class="stats-chart" id="stages-chart" aria-label="各比赛阶段有效场次、完赛场次及进球图"></div>
                <details><summary>查看阶段数据表</summary>
                    <div class="table-responsive"><table class="table table-sm">
                        <caption>有效场次包含未完赛赛程，进球仅统计已完赛比赛。</caption>
                        <thead><tr><th scope="col">阶段</th><th scope="col">有效场次</th><th scope="col">已完赛</th><th scope="col">进球</th></tr></thead>
                        <tbody id="stages-table">
                            <?php foreach ($stages as $stage): ?>
                                <tr><td><?= Html::encode($stageLabels[$stage['stage_code']] ?? $stage['stage_code']) ?></td><td><?= (int) $stage['match_count'] ?></td><td><?= (int) $stage['finished_match_count'] ?></td><td><?= $stage['total_goals'] === null ? '暂无' : (int) $stage['total_goals'] ?></td></tr>
                            <?php endforeach; ?>
                            <?php if ($stages === []): ?><tr><td colspan="4">暂无有效赛程数据。</td></tr><?php endif; ?>
                        </tbody>
                    </table></div>
                </details>
            </section>
    </div>
    <details class="insight-method mt-4"><summary>统计口径</summary>
        <p class="small text-body-secondary mt-2">数据取自当前数据库中的 2026 届已完赛记录。120 分钟比分为累计值，点球大战不计入进球。阶段场次排除取消比赛，无完赛记录的阶段进球显示“暂无”。</p>
    </details>
</div>
<script type="application/json" id="insight-data"><?= Json::htmlEncode($seed) ?></script>
