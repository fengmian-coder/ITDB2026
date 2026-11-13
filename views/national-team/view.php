<?php
/**
 * WorldCup Journey - 球队档案与本届赛事表现
 * 模块：C 球队与统计
 * 负责人：C
 * 依赖：NationalTeamController、TeamStatsService 返回数组
 * 最近修改：2026-11-11，B 协助增加预选本队的 PK 入口
 */

declare(strict_types=1);

use yii\helpers\Html;
use yii\helpers\Url;
use app\models\TournamentTeam;

/** @var yii\web\View $this */
/** @var app\models\NationalTeam $team */
/** @var array $stats TeamStatsService 的单队统计 */
/** @var array{group_code: string, final_result_code: string|null}|null $participation */

$this->title = $team->name_zh . ' | WorldCup Journey';
$this->params['breadcrumbs'][] = ['label' => '球队列表', 'url' => ['/national-team/index']];
$this->params['breadcrumbs'][] = $team->name_zh;

$finalResult = $participation['final_result_code'] ?? $stats['final_result_code'] ?? null;
$finalResultLabel = TournamentTeam::FINAL_RESULT_LABELS[$finalResult] ?? $finalResult;
$groupCode = $participation['group_code'] ?? null;
$hasMatches = (int) $stats['matches_played'] > 0;
$formatDecimal = static function ($value): string {
    return $value === null ? '暂无' : number_format((float) $value, 2);
};
$formatRate = static function ($value): string {
    return $value === null ? '暂无' : number_format((float) $value * 100, 1) . '%';
};
?>
<div class="national-team-view">
    <div class="d-flex flex-column flex-md-row align-items-md-center gap-3 mb-4">
        <?= $this->render('_flag', ['team' => $team, 'size' => 'lg']) ?>
        <div class="flex-grow-1">
            <h1 class="h2 mb-1"><?= Html::encode($team->name_zh) ?></h1>
            <p class="text-body-secondary mb-1"><?= Html::encode($team->name_en) ?></p>
            <div class="d-flex flex-wrap gap-2">
                <span class="badge text-bg-dark"><?= Html::encode($team->fifa_code) ?></span>
                <?php if ($groupCode !== null): ?>
                    <span class="badge text-bg-secondary"><?= Html::encode($groupCode) ?> 组</span>
                <?php endif; ?>
                <?php if ($finalResult !== null): ?>
                    <span class="badge text-bg-success"><?= Html::encode($finalResultLabel) ?></span>
                <?php endif; ?>
                <?php if (!(int) $team->is_active): ?>
                    <span class="badge text-bg-warning">已停用</span>
                <?php endif; ?>
            </div>
        </div>
        <div class="d-flex flex-wrap gap-2">
            <?= Html::a('查看比赛历程', ['/journey/view', 'team_id' => $team->id], ['class' => 'btn btn-primary']) ?>
            <?= Html::a('与其他球队比较', ['/national-team/compare', 'left_id' => $team->id], ['class' => 'btn btn-outline-primary']) ?>
            <?= Html::a('本届比赛', ['/wc-match/index', 'team_id' => $team->id], ['class' => 'btn btn-outline-secondary']) ?>
        </div>
    </div>

    <?php if ($profile !== null): ?>
        <?= $this->render('_profile', ['team' => $team, 'profile' => $profile]) ?>
    <?php elseif ($team->description): ?>
        <p class="text-body-secondary"><?= nl2br(Html::encode($team->description)) ?></p>
    <?php endif; ?>

    <?php if (!$hasMatches): ?>
        <div class="alert alert-info">该球队暂无完赛记录，统计显示为“暂无”</div>
    <?php endif; ?>

    <h2 class="h4 mb-3">本届赛事表现</h2>
    <div class="row g-3">
        <div class="col-6 col-md-3">
            <div class="card h-100 border-0 shadow-sm">
                <div class="card-body">
                    <div class="text-body-secondary small">已完赛场次</div>
                    <div class="fs-3 fw-bold"><?= (int) $stats['matches_played'] ?></div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card h-100 border-0 shadow-sm">
                <div class="card-body">
                    <div class="text-body-secondary small">胜 / 平 / 负</div>
                    <div class="fs-3 fw-bold">
                        <?= (int) $stats['wins'] ?> / <?= (int) $stats['draws'] ?> / <?= (int) $stats['losses'] ?>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card h-100 border-0 shadow-sm">
                <div class="card-body">
                    <div class="text-body-secondary small">进球 / 失球</div>
                    <div class="fs-3 fw-bold">
                        <?= (int) $stats['goals_for'] ?> / <?= (int) $stats['goals_against'] ?>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card h-100 border-0 shadow-sm">
                <div class="card-body">
                    <div class="text-body-secondary small">胜率</div>
                    <div class="fs-3 fw-bold"><?= Html::encode($formatRate($stats['win_rate'])) ?></div>
                </div>
            </div>
        </div>

        <div class="col-6 col-md-3">
            <div class="card h-100 border-0 shadow-sm">
                <div class="card-body">
                    <div class="text-body-secondary small">场均进球</div>
                    <div class="fs-4 fw-bold"><?= Html::encode($formatDecimal($stats['goals_per_match'])) ?></div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card h-100 border-0 shadow-sm">
                <div class="card-body">
                    <div class="text-body-secondary small">场均失球</div>
                    <div class="fs-4 fw-bold"><?= Html::encode($formatDecimal($stats['goals_against_per_match'])) ?></div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card h-100 border-0 shadow-sm">
                <div class="card-body">
                    <div class="text-body-secondary small">点球大战获胜</div>
                    <div class="fs-4 fw-bold"><?= (int) $stats['penalty_wins'] ?></div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card h-100 border-0 shadow-sm">
                <div class="card-body">
                    <div class="text-body-secondary small">最终成绩</div>
                    <div class="fs-4 fw-bold"><?= Html::encode($finalResultLabel ?? '暂无') ?></div>
                </div>
            </div>
        </div>
    </div>

    <p class="small text-body-secondary mt-3 mb-0">
        统计口径：仅计算 2026 届已结束比赛；最终场上比分优先 120 分钟累计值，点球大战不计入总进球
    </p>
</div>
