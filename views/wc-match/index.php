<?php
/**
 * WorldCup Journey - 比赛列表与筛选页面
 * 模块：B 赛事与比赛后端
 * 负责人：B
 * 依赖：WcMatchController、MatchQueryService 数组接口、AppAsset / Bootstrap 5
 * 最近修改：2026-11-11
 */

declare(strict_types=1);

use app\models\WcMatch;
use yii\helpers\Html;
use yii\helpers\Url;

/** @var yii\web\View $this */
/** @var array $matches Service 的 items；不在页面查询或补造数据 */
/** @var array $teams 本届全部参赛球队目录 */
/** @var array{page: int, page_size: int, total_count: int, page_count: int} $pagination */
/** @var array{stage: ?string, team_id: int|string|null, page: int, page_size: int} $filters */

$this->title = '比赛赛程与赛果 | WorldCup Journey';
$this->params['breadcrumbs'][] = '比赛赛程';
$this->params['meta_description'] = '探索 2026 世界杯赛程与赛果，按比赛阶段或球队筛选比赛';
$teamOptions = [];
foreach ($teams as $team) {
    $teamOptions[$team['id']] = $team['name_zh'] . ' · ' . $team['fifa_code'];
}
$stageLabels = [
    'GROUP' => '小组赛', 'R32' => '32 强赛', 'R16' => '16 强赛',
    'QF' => '四分之一决赛', 'SF' => '半决赛', 'THIRD' => '季军赛', 'FINAL' => '决赛',
];
$stageOptions = [];
foreach (WcMatch::STAGE_CODES as $code) {
    $stageOptions[$code] = $stageLabels[$code] ?? $code;
}
$statusLabels = [
    'SCHEDULED' => '未开始', 'IN_PROGRESS' => '进行中', 'FINISHED' => '已结束',
    'POSTPONED' => '已延期', 'CANCELLED' => '已取消',
];
$statusClasses = [
    'SCHEDULED' => 'text-bg-light', 'IN_PROGRESS' => 'text-bg-success',
    'FINISHED' => 'text-bg-dark', 'POSTPONED' => 'text-bg-warning', 'CANCELLED' => 'text-bg-secondary',
];
// DATETIME 不带时区：明确按 UTC 读取，转换为北京时间，不依赖 PHP 默认时区。
$beijingTime = static function (?string $value): ?DateTimeImmutable {
    if ($value === null || $value === '') {
        return null;
    }
    $time = DateTimeImmutable::createFromFormat('!Y-m-d H:i:s', $value, new DateTimeZone('UTC'));
    return $time !== false && $time->format('Y-m-d H:i:s') === $value
        ? $time->setTimezone(new DateTimeZone('Asia/Shanghai')) : null;
};
$page = $pagination['page'];
$pageCount = $pagination['page_count'];
$pageUrl = static function (int $target) use ($filters, $pagination): string {
    $params = ['/wc-match/index', 'page' => $target, 'page_size' => $pagination['page_size']];
    foreach (['stage', 'team_id'] as $key) {
        if (($filters[$key] ?? null) !== null && $filters[$key] !== '') {
            $params[$key] = $filters[$key];
        }
    }
    return Url::to($params);
};
$this->registerCss(<<<'CSS'
.wc-match-index { flex: 1; --wc-accent: #e4d59c; }
.wc-match-index .wc-hero { background: #174f35; color: #fff; text-align: left; }
.wc-match-index .wc-hero .wc-eyebrow { color: var(--wc-accent); letter-spacing: .12em; font-size: 14px; line-height: 1.5; max-width: none; margin: 0 0 12px; }
.wc-match-index .wc-total { color: var(--wc-accent); font-variant-numeric: tabular-nums; }
.wc-match-index .wc-card { border-top: 3px solid #48785a !important; }
.wc-match-index .wc-team { min-width: 0; overflow-wrap: anywhere; }
.wc-match-index .wc-score { font-size: clamp(1.6rem, 5vw, 2.4rem); font-weight: 750; font-variant-numeric: tabular-nums; white-space: nowrap; }
.wc-match-index .wc-fixture { display: grid; grid-template-columns: minmax(0, 1fr) auto minmax(0, 1fr); gap: .75rem; align-items: center; }
.wc-match-index .wc-detail-link { text-decoration: none; }
.wc-match-index .wc-detail-link:hover { text-decoration: underline; }
.wc-match-index .wc-empty { border: 1px dashed var(--bs-border-color); }
.wc-match-index .pagination { gap: .25rem; }
.wc-match-index .page-link { border-radius: .5rem; }
@media (max-width: 575.98px) {
    .wc-match-index .wc-fixture { gap: .4rem; }
    .wc-match-index .wc-card .card-body { padding: 1rem; }
    .wc-match-index .pagination { font-size: .85rem; }
    .wc-match-index .page-link { padding: .4rem .55rem; }
}
CSS, [], 'wc-match-index');
?>
<div class="wc-match-index pb-4">
    <header class="wc-hero rounded-4 p-4 p-md-5 mb-4">
        <div class="d-flex flex-wrap align-items-end justify-content-between gap-3">
            <div>
                <p class="wc-eyebrow fw-semibold">WORLD CUP JOURNEY / 2026</p>
                <h1 class="display-6 fw-bold mb-2">每一场，都是旅程</h1>
                <p class="mb-0 opacity-75">2026 世界杯赛程与赛果 · 所有开赛时间均为北京时间</p>
            </div>
            <div class="text-md-end">
                <div class="wc-total fs-2 fw-bold"><?= Html::encode((string) $pagination['total_count']) ?></div>
                <div class="small opacity-75">场符合当前条件的比赛</div>
            </div>
        </div>
    </header>
    <section class="card border-0 shadow-sm rounded-4 mb-4" aria-labelledby="match-filter-title">
        <div class="card-body p-3 p-md-4">
            <h2 id="match-filter-title" class="h6 fw-bold mb-3">找到你关注的比赛</h2>
            <?= Html::beginForm(Url::to(['/wc-match/index']), 'get', ['class' => 'row g-3 align-items-end']) ?>
                <?= Html::hiddenInput('page', '1') ?>
                <?= Html::hiddenInput('page_size', (string) $pagination['page_size']) ?>
                <div class="col-12 col-md-5">
                    <?= Html::label('比赛阶段', 'match-stage', ['class' => 'form-label small']) ?>
                    <?= Html::dropDownList('stage', $filters['stage'] ?? '', $stageOptions, [
                        'id' => 'match-stage', 'class' => 'form-select', 'prompt' => '全部阶段',
                    ]) ?>
                </div>
                <div class="col-12 col-md-4">
                    <?= Html::label('球队', 'match-team-id', ['class' => 'form-label small']) ?>
                    <?= Html::dropDownList('team_id', $filters['team_id'] ?? '', $teamOptions, [
                        'id' => 'match-team-id', 'class' => 'form-select', 'prompt' => '全部球队',
                        'aria-describedby' => 'match-team-hint',
                    ]) ?>
                </div>
                <div class="col-12 col-md-3 d-flex gap-2">
                    <?= Html::submitButton('筛选比赛', ['class' => 'btn btn-primary flex-grow-1']) ?>
                    <?= Html::a('重置', ['/wc-match/index'], ['class' => 'btn btn-outline-secondary']) ?>
                </div>
            <?= Html::endForm() ?>
            <p id="match-team-hint" class="small text-body-secondary mt-3 mb-0">筛选同时匹配主队和客队</p>
        </div>
    </section>
    <section aria-labelledby="match-list-title">
        <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
            <h2 id="match-list-title" class="h5 fw-bold mb-0">比赛一览</h2>
            <span class="small text-body-secondary">按开赛时间排列 · 北京时间</span>
        </div>
        <?php if ($matches === []): ?>
            <div class="wc-empty rounded-4 text-center py-5 px-3">
                <h3 class="h5 mb-2">暂无符合条件的比赛</h3>
                <p class="text-body-secondary mb-3">可以调整筛选条件，或稍后再来查看赛程</p>
                <?= Html::a('查看全部比赛', ['/wc-match/index'], ['class' => 'btn btn-outline-primary']) ?>
            </div>
        <?php else: ?>
            <div class="row g-3">
                <?php foreach ($matches as $item): ?>
                    <?php
                    $kickoff = $beijingTime($item['kickoff_utc']);
                    $finalScore = $item['final_score'];
                    $hasFinalScore = $finalScore['home'] !== null && $finalScore['away'] !== null;
                    $detailUrl = Url::to(['/wc-match/view', 'id' => $item['id']]);
                    ?>
                    <div class="col-12 col-xl-6">
                        <article class="wc-card card h-100 border-0 shadow-sm rounded-4">
                            <div class="card-body p-4">
                                <div class="d-flex flex-wrap justify-content-between gap-2 mb-3">
                                    <span class="small fw-semibold">比赛 #<?= Html::encode((string) $item['match_no']) ?>
                                        · <?= Html::encode($stageLabels[$item['stage_code']] ?? $item['stage_code']) ?>
                                        <?php if ($item['group_code'] !== null): ?>
                                            · <?= Html::encode($item['group_code']) ?> 组
                                        <?php endif ?>
                                    </span>
                                    <span class="badge <?= Html::encode($statusClasses[$item['status']] ?? 'text-bg-secondary') ?>">
                                        <?= Html::encode($statusLabels[$item['status']] ?? $item['status']) ?>
                                    </span>
                                </div>
                                <div class="wc-fixture text-center mb-3">
                                    <div class="wc-team">
                                        <div class="small text-body-secondary mb-1">主队</div>
                                        <?= $this->render('_team', ['team' => $item['home_team'], 'teamId' => $item['home_team_id']]) ?>
                                    </div>
                                    <div>
                                        <div class="wc-score"><?= $hasFinalScore ? Html::encode($finalScore['home'] . ' : ' . $finalScore['away']) : 'VS' ?></div>
                                        <div class="small text-body-secondary"><?= $hasFinalScore ? '最终场上比分' : '暂无最终比分' ?></div>
                                    </div>
                                    <div class="wc-team">
                                        <div class="small text-body-secondary mb-1">客队</div>
                                        <?= $this->render('_team', ['team' => $item['away_team'], 'teamId' => $item['away_team_id']]) ?>
                                    </div>
                                </div>
                                <div class="small text-body-secondary d-flex flex-wrap justify-content-center gap-2 mb-3">
                                    <?php foreach ([['90 分钟', 'home_score_90', 'away_score_90'],
                                        ['120 分钟累计', 'home_score_120', 'away_score_120'],
                                        ['点球大战', 'home_penalties', 'away_penalties']] as [$label, $home, $away]): ?>
                                        <?php if ($item[$home] !== null && $item[$away] !== null): ?>
                                            <span class="border rounded-pill px-2 py-1"><?= Html::encode($label . ' ' . $item[$home] . ' : ' . $item[$away]) ?></span>
                                        <?php endif ?>
                                    <?php endforeach ?>
                                </div>
                                <div class="border-top pt-3 d-flex flex-wrap justify-content-between align-items-center gap-2">
                                    <div class="small">
                                        <?php if ($kickoff !== null): ?>
                                            <time datetime="<?= Html::encode($kickoff->format(DateTimeInterface::ATOM)) ?>"><?= Html::encode($kickoff->format('Y-m-d H:i')) ?></time>
                                            <span class="text-body-secondary">北京时间</span>
                                        <?php else: ?>
                                            <span class="text-body-secondary">开赛时间暂不可用</span>
                                        <?php endif ?>
                                    </div>
                                    <?= Html::a('查看比赛 →', $detailUrl, [
                                        'class' => 'wc-detail-link fw-semibold', 'aria-label' => '查看比赛 #' . $item['match_no'] . ' 详情',
                                    ]) ?>
                                </div>
                            </div>
                        </article>
                    </div>
                <?php endforeach ?>
            </div>
        <?php endif ?>
    </section>
    <?php if ($pageCount > 0): ?>
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-center gap-3 mt-4">
            <p class="small text-body-secondary mb-0">第 <?= Html::encode((string) $page) ?> / <?= Html::encode((string) $pageCount) ?> 页
                · 每页 <?= Html::encode((string) $pagination['page_size']) ?> 场</p>
            <nav aria-label="比赛列表分页">
                <ul class="pagination flex-wrap justify-content-center mb-0">
                    <li class="page-item<?= $page <= 1 ? ' disabled' : '' ?>">
                        <?= $page > 1 ? Html::a('上一页', $pageUrl($page - 1), ['class' => 'page-link', 'rel' => 'prev'])
                            : Html::tag('span', '上一页', ['class' => 'page-link', 'aria-disabled' => 'true']) ?>
                    </li>
                    <?php
                    // 仅计算导航窗口，不把分页数组当作 yii\data\Pagination 对象。
                    $visiblePages = array_unique(array_merge([1, $pageCount], range(max(1, $page - 2), min($pageCount, $page + 2))));
                    sort($visiblePages, SORT_NUMERIC);
                    $previousPage = 0;
                    ?>
                    <?php foreach ($visiblePages as $number): ?>
                        <?php if ($number > $previousPage + 1): ?>
                            <li class="page-item disabled"><span class="page-link" aria-hidden="true">…</span></li>
                        <?php endif ?>
                        <li class="page-item<?= $number === $page ? ' active' : '' ?>">
                            <?= $number === $page
                                ? Html::tag('span', Html::encode((string) $number), ['class' => 'page-link', 'aria-current' => 'page'])
                                : Html::a(Html::encode((string) $number), $pageUrl($number), ['class' => 'page-link', 'aria-label' => '第 ' . $number . ' 页']) ?>
                        </li>
                        <?php $previousPage = $number ?>
                    <?php endforeach ?>
                    <li class="page-item<?= $page >= $pageCount ? ' disabled' : '' ?>">
                        <?= $page < $pageCount ? Html::a('下一页', $pageUrl($page + 1), ['class' => 'page-link', 'rel' => 'next'])
                            : Html::tag('span', '下一页', ['class' => 'page-link', 'aria-disabled' => 'true']) ?>
                    </li>
                </ul>
            </nav>
        </div>
    <?php endif ?>
</div>
