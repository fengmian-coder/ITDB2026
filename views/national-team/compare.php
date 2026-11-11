<?php
/**
 * WorldCup Journey - 两队统计 PK
 * 模块：C 球队与统计；负责人：C，B 协助实现
 * 依赖：NationalTeamController、TeamStatsService、TeamStatsAsset
 * 最近修改：2026-11-11
 */
declare(strict_types=1);

use app\assets\TeamStatsAsset;
use yii\helpers\Html;

/** @var yii\web\View $this */
/** @var array $teams */
/** @var int|null $leftId */
/** @var int|null $rightId */
/** @var array|null $comparison */
TeamStatsAsset::register($this);
$this->title = '球队 PK | WorldCup Journey';
$this->params['breadcrumbs'][] = ['label' => '球队列表', 'url' => ['/national-team/index']];
$this->params['breadcrumbs'][] = '球队 PK';
$options = [];
foreach ($teams as $team) {
    $options[$team['id']] = ($team['name_zh'] ?: $team['name_en']) . ' · ' . $team['fifa_code'];
}
$metrics = [
    'matches_played' => '已完赛场次', 'wins' => '胜', 'draws' => '平', 'losses' => '负',
    'goals_for' => '进球', 'goals_against' => '失球', 'goals_per_match' => '场均进球',
    'goals_against_per_match' => '场均失球', 'win_rate' => '胜率',
    'penalty_wins' => '点球大战获胜', 'final_result_code' => '最终成绩',
];
$format = static function (string $key, mixed $value): string {
    if ($value === null) {
        return '暂无';
    }
    return match ($key) {
        'win_rate' => number_format((float) $value * 100, 1) . '%',
        'goals_per_match', 'goals_against_per_match' => number_format((float) $value, 2),
        default => (string) $value,
    };
};
?>
<div class="team-compare">
    <header class="stats-hero mb-4">
        <h1>球队 PK</h1>
        <p class="mb-0">选择两支参赛球队，比较它们在 2026 世界杯的场上表现。</p>
    </header>
    <?= Html::beginForm(['/national-team/compare'], 'get', ['class' => 'stats-panel mb-4']) ?>
        <div class="row g-3 align-items-end">
            <div class="col-md-5">
                <?= Html::label('第一支球队', 'compare-left', ['class' => 'form-label']) ?>
                <?= Html::dropDownList('left_id', $leftId, $options, ['id' => 'compare-left', 'class' => 'form-select', 'prompt' => '选择球队', 'required' => true]) ?>
            </div>
            <div class="col-md-5">
                <?= Html::label('第二支球队', 'compare-right', ['class' => 'form-label']) ?>
                <?= Html::dropDownList('right_id', $rightId, $options, ['id' => 'compare-right', 'class' => 'form-select', 'prompt' => '选择球队', 'required' => true]) ?>
            </div>
            <div class="col-md-2 d-grid">
                <?= Html::submitButton('比较球队', ['class' => 'btn btn-primary']) ?>
            </div>
        </div>
    <?= Html::endForm() ?>
    <?php if ($comparison === null): ?>
        <div class="stats-panel text-center py-5">
            <h2 class="h4">选好球队，开始比较</h2>
            <p class="text-body-secondary mb-0">两支球队的比赛场次、进失球和胜率将在这里并排展示。</p>
        </div>
    <?php else: ?>
        <section class="stats-panel" aria-label="两队统计对比">
            <div class="d-flex justify-content-between align-items-center gap-3 mb-3">
                <h2 class="h4 mb-0">本届赛事表现</h2>
                <?= Html::a('交换球队', ['/national-team/compare', 'left_id' => $rightId, 'right_id' => $leftId], ['class' => 'btn btn-sm btn-outline-secondary']) ?>
            </div>
            <div class="table-responsive">
                <table class="table stats-comparison align-middle mb-0">
                    <caption>2026 届已完赛统计。场均数据保留两位小数，胜率保留一位小数。</caption>
                    <thead><tr>
                        <th scope="col">统计项</th>
                        <?php foreach (['left', 'right'] as $side): ?>
                            <th scope="col">
                                <?= Html::a(Html::encode($comparison[$side]['team_name']), ['/national-team/view', 'id' => $comparison[$side]['team_id']]) ?>
                                <span class="d-block small text-body-secondary mt-1"><?= Html::encode($comparison[$side]['fifa_code']) ?></span>
                            </th>
                        <?php endforeach; ?>
                    </tr></thead>
                    <tbody>
                        <?php foreach ($metrics as $key => $label): ?>
                            <tr>
                                <th scope="row"><?= Html::encode($label) ?></th>
                                <td><?= Html::encode($format($key, $comparison['left'][$key])) ?></td>
                                <td><?= Html::encode($format($key, $comparison['right'][$key])) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </section>
    <?php endif; ?>
    <p class="small text-body-secondary mt-3">统计仅含本届已结束比赛。优先采用 120 分钟累计比分，点球大战单列。场上战平计为平局，暂无完赛记录时场均值和胜率显示“暂无”。</p>
</div>
