<?php
/**
 * WorldCup Journey - 球队赛程与晋级历程页面
 * 模块：B 赛事与比赛后端
 * 负责人：B
 * 依赖：JourneyController、JourneyService 数组接口、AppAsset / Bootstrap 5
 * 球队资料与最终成绩读取 Service 提供的 C 模块字段
 * 最近修改：2026-11-11
 */

declare(strict_types=1);

use yii\helpers\Html;
use yii\helpers\Url;

/** @var yii\web\View $this */
/** @var int $teamId Controller 校验后的球队 ID */
/** @var array $journey Service 已排序的完整历程；View 不查询、不重排、不重算赛果 */
/** @var array $team 本队资料与赛事参赛信息 */
/** @var array $teams 本届球队目录，含历史关联中的停用球队 */

$teamOptions = [];
foreach ($teams as $option) {
    $teamOptions[$option['id']] = $option['name_zh'] . ' · ' . $option['fifa_code'];
}

$this->title = $team['name_zh'] . '的世界杯之旅 | WorldCup Journey';
$this->params['breadcrumbs'][] = ['label' => '比赛赛程', 'url' => ['/wc-match/index']];
$this->params['breadcrumbs'][] = '球队比赛历程';
$this->params['meta_description'] = '按时间浏览球队的 2026 世界杯赛程、比分、晋级结果和精选比赛故事';

$stageLabels = [
    'GROUP' => '小组赛', 'R32' => '32 强赛', 'R16' => '16 强赛',
    'QF' => '四分之一决赛', 'SF' => '半决赛', 'THIRD' => '季军赛', 'FINAL' => '决赛',
];
$statusLabels = [
    'SCHEDULED' => '未开始', 'IN_PROGRESS' => '进行中', 'FINISHED' => '已结束',
    'POSTPONED' => '已延期', 'CANCELLED' => '已取消',
];
$statusClasses = [
    'SCHEDULED' => 'text-bg-light', 'IN_PROGRESS' => 'text-bg-success', 'FINISHED' => 'text-bg-dark',
    'POSTPONED' => 'text-bg-warning', 'CANCELLED' => 'text-bg-secondary',
];
$advancementLabels = ['ADVANCED' => '晋级下一轮', 'ELIMINATED' => '本轮出局'];
$finalResultLabels = [
    'GROUP' => '小组赛', 'R32' => '32 强', 'R16' => '16 强', 'QF' => '八强',
    'FOURTH' => '第四名', 'THIRD' => '季军', 'RUNNER_UP' => '亚军', 'CHAMPION' => '冠军',
];

// JourneyService 使用 UTC ISO 8601；只转换展示时区，不推算时间或改写 Service。
$beijingTime = static function (?string $value): ?DateTimeImmutable {
    if ($value === null || $value === '') {
        return null;
    }
    $time = DateTimeImmutable::createFromFormat('!Y-m-d\TH:i:s\Z', $value, new DateTimeZone('UTC'));
    return $time !== false && $time->format('Y-m-d\TH:i:s\Z') === $value
        ? $time->setTimezone(new DateTimeZone('Asia/Shanghai')) : null;
};

$this->registerCss(<<<'CSS'
.journey-view { flex: 1; --journey-accent: #ceef77; }
.journey-view .journey-hero { background: linear-gradient(115deg, #102d39, #126a65); color: #fff; }
.journey-view .journey-eyebrow { color: var(--journey-accent); letter-spacing: .16em; font-size: .75rem; }
.journey-view .journey-timeline { list-style: none; padding: 0; margin: 0; }
.journey-view .journey-step { position: relative; margin-left: .5rem; padding: 0 0 1.5rem 1.5rem; border-left: 2px solid var(--bs-border-color); }
.journey-view .journey-step::before { content: ''; position: absolute; left: -.45rem; top: .45rem; width: .8rem; height: .8rem; border-radius: 50%; background: #167d72; border: 2px solid var(--bs-body-bg); }
.journey-view .journey-step:last-child { padding-bottom: 0; border-left-color: transparent; }
.journey-view .journey-fixture { display: grid; grid-template-columns: minmax(0, 1fr) auto minmax(0, 1fr); gap: .75rem; align-items: center; }
.journey-view .journey-team { min-width: 0; overflow-wrap: anywhere; }
.journey-view .journey-score { font-size: clamp(1.7rem, 5vw, 2.6rem); font-weight: 750; font-variant-numeric: tabular-nums; white-space: nowrap; }
.journey-view .journey-empty { border: 1px dashed var(--bs-border-color); }
.journey-view .badge { max-width: 100%; white-space: normal; overflow-wrap: anywhere; }
@media (max-width: 575.98px) {
    .journey-view .journey-step { padding-left: 1rem; }
    .journey-view .journey-card .card-body { padding: 1rem; }
    .journey-view .journey-fixture { gap: .35rem; }
}
CSS, [], 'journey-view');
?>
<div class="journey-view pb-4">
    <header class="journey-hero rounded-4 p-4 p-md-5 mb-4">
        <p class="journey-eyebrow fw-semibold mb-2">WORLD CUP JOURNEY / 2026</p>
        <div class="mb-3"><?= \app\components\TeamFlag::render($team['fifa_code'], $team['name_zh'], $team['flag_path'] ?? null, 'lg') ?></div>
        <h1 class="display-6 fw-bold mb-3"><?= Html::encode($team['name_zh']) ?>的世界杯之旅</h1>
        <p class="mb-3"><?= Html::encode($team['fifa_code'] . ' · ' . $team['group_code'] . ' 组') ?>
            · 最终成绩 <?= Html::encode($finalResultLabels[$team['final_result_code']] ?? '暂无记录') ?></p>
        <p class="mb-0 opacity-75">从开场到终场，沿着时间回看每一场比赛。所有时间均为北京时间</p>
    </header>

    <section class="card border-0 shadow-sm rounded-4 mb-4" aria-labelledby="journey-search-title">
        <div class="card-body p-3 p-md-4">
            <h2 id="journey-search-title" class="h6 fw-bold mb-3">查看球队历程</h2>
            <?= Html::beginForm(Url::to(['/journey/view']), 'get', ['class' => 'row g-3 align-items-end']) ?>
                <div class="col-12 col-md-8">
                    <?= Html::label('球队', 'journey-team-id', ['class' => 'form-label small']) ?>
                    <?= Html::dropDownList('team_id', $teamId, $teamOptions, [
                        'id' => 'journey-team-id', 'class' => 'form-select',
                        'required' => true, 'aria-describedby' => 'journey-team-hint',
                    ]) ?>
                </div>
                <div class="col-12 col-md-4 d-flex flex-wrap gap-2">
                    <?= Html::submitButton('查看历程', ['class' => 'btn btn-primary flex-grow-1']) ?>
                    <?= Html::a('全部比赛', ['/wc-match/index'], ['class' => 'btn btn-outline-secondary']) ?>
                </div>
            <?= Html::endForm() ?>
            <p id="journey-team-hint" class="small text-body-secondary mt-3 mb-0">下方比分按本队 : 对手排列，点球大战单独展示</p>
            <?= Html::a('选择其他球队', ['/national-team/index'], ['class' => 'small d-inline-block mt-2']) ?>
        </div>
    </section>

    <section aria-labelledby="journey-timeline-title">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-4">
            <h2 id="journey-timeline-title" class="h5 fw-bold mb-0">赛程与晋级历程</h2>
            <span class="small text-body-secondary"><?= Html::encode((string) count($journey)) ?> 条比赛记录 · 按开赛时间排列</span>
        </div>
        <?php if ($journey === []): ?>
            <div class="journey-empty rounded-4 text-center py-5 px-3">
                <h3 class="h5 mb-2">暂无匹配的比赛记录</h3>
                <p class="text-body-secondary mb-3">该参赛球队暂无赛程，稍后再来查看</p>
                <?= Html::a('浏览全部比赛', ['/wc-match/index'], ['class' => 'btn btn-outline-primary']) ?>
            </div>
        <?php else: ?>
            <ol class="journey-timeline">
                <?php foreach ($journey as $item): ?>
                    <?php
                    $kickoff = $beijingTime($item['kickoff_utc']);
                    $opponent = $item['opponent'];
                    $hasScore = $item['goals_for'] !== null && $item['goals_against'] !== null;
                    $detailUrl = Url::to(['/wc-match/view', 'id' => $item['match_id']]);
                    ?>
                    <li class="journey-step">
                        <div class="row g-3">
                            <div class="col-12 col-lg-3 pt-lg-2">
                                <?php if ($kickoff !== null): ?>
                                    <time datetime="<?= Html::encode($kickoff->format(DateTimeInterface::ATOM)) ?>" class="fw-semibold">
                                        <?= Html::encode($kickoff->format('Y-m-d H:i')) ?>
                                    </time>
                                    <div class="small text-body-secondary mt-1">北京时间</div>
                                <?php else: ?>
                                    <span class="small text-body-secondary">开赛时间暂不可用</span>
                                <?php endif ?>
                            </div>
                            <div class="col-12 col-lg-9">
                                <article class="journey-card card border-0 shadow-sm rounded-4">
                                    <div class="card-body p-4">
                                        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
                                            <h3 class="h6 fw-semibold mb-0">比赛 #<?= Html::encode((string) $item['match_no']) ?>
                                                · <?= Html::encode($stageLabels[$item['stage_code']] ?? $item['stage_code']) ?></h3>
                                            <span class="badge <?= Html::encode($statusClasses[$item['status']] ?? 'text-bg-secondary') ?>">
                                                <?= Html::encode($statusLabels[$item['status']] ?? $item['status']) ?>
                                            </span>
                                        </div>
                                        <div class="journey-fixture text-center mb-3">
                                            <div class="journey-team">
                                                <div class="small text-body-secondary mb-1">本队 · <?= $item['is_home'] ? '主队位' : '客队位' ?></div>
                                                <?= $this->render('/wc-match/_team', ['team' => $team, 'teamId' => $teamId]) ?>
                                            </div>
                                            <div>
                                                <div class="journey-score"><?= $hasScore
                                                    ? Html::encode($item['goals_for'] . ' : ' . $item['goals_against']) : 'VS' ?></div>
                                                <div class="small text-body-secondary"><?= $hasScore ? '最终场上比分' : '暂无最终比分' ?></div>
                                            </div>
                                            <div class="journey-team">
                                                <div class="small text-body-secondary mb-1">对手</div>
                                                <?= $this->render('/wc-match/_team', ['team' => $opponent, 'teamId' => $opponent['team_id']]) ?>
                                            </div>
                                        </div>
                                        <?php if ($item['penalties_for'] !== null && $item['penalties_against'] !== null): ?>
                                            <p class="small text-center text-body-secondary mb-3">点球大战（本队 : 对手）
                                                <?= Html::encode($item['penalties_for'] . ' : ' . $item['penalties_against']) ?>
                                            </p>
                                        <?php endif ?>
                                        <div class="border-top pt-3 d-flex flex-wrap align-items-center justify-content-between gap-3">
                                            <div class="d-flex flex-wrap gap-2">
                                                <?php if ($item['advancement_result'] !== null): ?>
                                                    <span class="badge rounded-pill <?= $item['advancement_result'] === 'ADVANCED' ? 'text-bg-success' : 'text-bg-secondary' ?>">
                                                        <?= Html::encode($advancementLabels[$item['advancement_result']] ?? $item['advancement_result']) ?>
                                                    </span>
                                                <?php endif ?>
                                                <?php if ($item['is_featured']): ?>
                                                    <span class="badge rounded-pill text-bg-light border">精选比赛</span>
                                                <?php endif ?>
                                            </div>
                                            <div class="d-flex flex-wrap gap-3">
                                                <?= Html::a('比赛详情 →', $detailUrl, ['class' => 'small fw-semibold',
                                                    'aria-label' => '查看比赛 #' . $item['match_no'] . ' 详情']) ?>
                                                <?php if ($item['is_featured']): ?>
                                                    <?= Html::a('阅读精选故事', ['/wc-match/view', 'id' => $item['match_id'], '#' => 'match-story-title'], ['class' => 'small']) ?>
                                                <?php endif ?>
                                            </div>
                                        </div>
                                    </div>
                                </article>
                            </div>
                        </div>
                    </li>
                <?php endforeach ?>
            </ol>
        <?php endif ?>
    </section>
</div>
