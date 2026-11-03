<?php
/**
 * WorldCup Journey - 比赛详情、公开故事与事件时间轴页面
 * 模块：B 赛事与比赛后端
 * 负责人：B
 * 依赖：WcMatchController、MatchQueryService 公开详情数组、AppAsset / Bootstrap 5
 * 最近修改：2026-11-03
 */

declare(strict_types=1);

use yii\helpers\Html;
use yii\helpers\Url;

/** @var yii\web\View $this */
/** @var array $match Service 的完整详情；发布控制及事件排序均由 Service 完成 */

$this->title = '比赛 #' . $match['match_no'] . ' | WorldCup Journey';
$this->params['breadcrumbs'][] = ['label' => '比赛赛程', 'url' => ['/wc-match/index']];
$this->params['breadcrumbs'][] = '比赛 #' . $match['match_no'];
$this->params['meta_description'] = '查看比赛基本信息、各时段比分以及已发布的精选故事与事件时间轴。';
$stageLabels = [
    'GROUP' => '小组赛', 'R32' => '32 强赛', 'R16' => '16 强赛',
    'QF' => '四分之一决赛', 'SF' => '半决赛', 'THIRD' => '季军赛', 'FINAL' => '决赛',
];
$statusLabels = [
    'SCHEDULED' => '未开始', 'IN_PROGRESS' => '进行中', 'FINISHED' => '已结束',
    'POSTPONED' => '已延期', 'CANCELLED' => '已取消',
];
$countryLabels = ['CA' => '加拿大', 'MX' => '墨西哥', 'US' => '美国'];
$tournament = $match['tournament'];
$stadium = $match['stadium'];
$finalScore = $match['final_score'];
$hasFinalScore = $finalScore['home'] !== null && $finalScore['away'] !== null;
$story = $match['story'];
$events = $match['events'];
$tags = $match['tags'];

// 仅做展示时区转换；不推算比赛结束时间，也不依赖服务器默认时区。
$kickoff = null;
if ($match['kickoff_utc'] !== null && $match['kickoff_utc'] !== '') {
    $utcTime = DateTimeImmutable::createFromFormat('!Y-m-d H:i:s', $match['kickoff_utc'], new DateTimeZone('UTC'));
    if ($utcTime !== false && $utcTime->format('Y-m-d H:i:s') === $match['kickoff_utc']) {
        $kickoff = $utcTime->setTimezone(new DateTimeZone('Asia/Shanghai'));
    }
}

// 仅输出站内封面 URL；相对路径暂按 web/ 根解析，不假定素材已存在或补造封面。
// 素材根目录仍待联调确认。拒绝协议、跳转、控制字符和已编码路径，避免二次解码。
$coverUrl = null;
$sourceUrl = null;
if ($story !== null) {
    $path = str_replace('\\', '/', trim((string) ($story['cover_path'] ?? '')));
    $segments = explode('/', $path);
    if ($path !== '' && !str_starts_with($path, '/') && !preg_match('/[\x00-\x1F\x7F%:?#]/', $path)
        && !in_array('..', $segments, true) && !in_array('.', $segments, true)) {
        $coverUrl = Url::to('@web/' . implode('/', array_map('rawurlencode', $segments)));
    }
    $source = (string) ($story['source_url'] ?? '');
    if (filter_var($source, FILTER_VALIDATE_URL) !== false
        && in_array(strtolower((string) parse_url($source, PHP_URL_SCHEME)), ['http', 'https'], true)) {
        $sourceUrl = $source;
    }
}

$this->registerCss(<<<'CSS'
.wc-match-view { flex: 1; --wc-accent: #ceef77; }
.wc-match-view .wc-scoreboard { background: linear-gradient(115deg, #102d39, #126a65); color: #fff; }
.wc-match-view .wc-eyebrow { letter-spacing: .16em; color: var(--wc-accent); font-size: .75rem; }
.wc-match-view .wc-fixture { display: grid; grid-template-columns: minmax(0, 1fr) auto minmax(0, 1fr); align-items: center; gap: 1rem; }
.wc-match-view .wc-team { min-width: 0; overflow-wrap: anywhere; }
.wc-match-view .wc-final { font-size: clamp(2rem, 7vw, 4rem); font-weight: 750; font-variant-numeric: tabular-nums; color: var(--wc-accent); white-space: nowrap; }
.wc-match-view .wc-score-value { font-size: 1.6rem; font-weight: 700; font-variant-numeric: tabular-nums; }
.wc-match-view .wc-story-body { white-space: pre-wrap; overflow-wrap: anywhere; line-height: 1.9; }
.wc-match-view .wc-copy { white-space: pre-wrap; overflow-wrap: anywhere; }
.wc-match-view #match-story-title { overflow-wrap: anywhere; }
.wc-match-view .badge { max-width: 100%; white-space: normal; overflow-wrap: anywhere; }
.wc-match-view .wc-cover { max-height: 440px; width: 100%; object-fit: contain; background: var(--bs-tertiary-bg); }
.wc-match-view .wc-timeline { list-style: none; padding-left: 0; }
.wc-match-view .wc-event { position: relative; border-left: 2px solid var(--bs-border-color); margin-left: .5rem; padding: 0 0 1.5rem 1.5rem; }
.wc-match-view .wc-event::before { content: ''; position: absolute; left: -.4rem; top: .35rem; width: .7rem; height: .7rem; border-radius: 50%; background: #167d72; }
.wc-match-view .wc-event:last-child { padding-bottom: 0; }
.wc-match-view .wc-info dd { overflow-wrap: anywhere; }
@media (max-width: 575.98px) {
    .wc-match-view .wc-fixture { gap: .5rem; }
    .wc-match-view .wc-fixture .wc-team { font-size: 1rem !important; }
}
CSS, [], 'wc-match-view');
?>
<div class="wc-match-view pb-4">
    <div class="mb-3"><?= Html::a('← 返回比赛列表', ['/wc-match/index'], ['class' => 'text-decoration-none']) ?></div>
    <header class="wc-scoreboard rounded-4 p-4 p-md-5 mb-4">
        <p class="wc-eyebrow fw-semibold mb-2">WORLD CUP JOURNEY / MATCH <?= Html::encode((string) $match['match_no']) ?></p>
        <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-4">
            <h1 class="h4 fw-bold mb-0">比赛 #<?= Html::encode((string) $match['match_no']) ?>
                · <?= Html::encode($stageLabels[$match['stage_code']] ?? $match['stage_code']) ?>
                <?php if ($match['group_code'] !== null): ?>
                    · <?= Html::encode($match['group_code']) ?> 组
                <?php endif ?>
            </h1>
            <span class="badge bg-white text-dark rounded-pill px-3 py-2"><?= Html::encode($statusLabels[$match['status']] ?? $match['status']) ?></span>
        </div>
        <div class="wc-fixture text-center">
            <div class="wc-team fs-4 fw-bold"><div class="small fw-normal opacity-75 mb-2">主队</div>球队 ID <?= Html::encode((string) $match['home_team_id']) ?></div>
            <div>
                <div class="wc-final"><?= $hasFinalScore ? Html::encode($finalScore['home'] . ' : ' . $finalScore['away']) : 'VS' ?></div>
                <div class="small opacity-75"><?= $hasFinalScore ? '最终场上比分' : '暂无最终比分' ?></div>
            </div>
            <div class="wc-team fs-4 fw-bold"><div class="small fw-normal opacity-75 mb-2">客队</div>球队 ID <?= Html::encode((string) $match['away_team_id']) ?></div>
        </div>
        <div class="text-center small mt-4 opacity-75">
            <?php if ($kickoff !== null): ?>
                <time datetime="<?= Html::encode($kickoff->format(DateTimeInterface::ATOM)) ?>"><?= Html::encode($kickoff->format('Y-m-d H:i')) ?></time> 北京时间
            <?php else: ?>
                开赛时间暂不可用
            <?php endif ?>
        </div>
    </header>

    <section class="mb-4" aria-labelledby="match-scores-title">
        <h2 id="match-scores-title" class="h5 fw-bold mb-3">比分记录</h2>
        <div class="row g-3">
            <?php foreach ([
                ['90 分钟', $match['home_score_90'], $match['away_score_90']],
                ['120 分钟累计', $match['home_score_120'], $match['away_score_120']],
                ['点球大战', $match['home_penalties'], $match['away_penalties']],
                ['最终场上比分', $finalScore['home'], $finalScore['away']],
            ] as [$label, $homeScore, $awayScore]): ?>
                <div class="col-6 col-lg-3">
                    <div class="card border-0 shadow-sm rounded-4 h-100 text-center">
                        <div class="card-body">
                            <h3 class="small text-body-secondary mb-2"><?= Html::encode($label) ?></h3>
                            <div class="wc-score-value"><?= $homeScore !== null && $awayScore !== null
                                ? Html::encode($homeScore . ' : ' . $awayScore) : '—' ?></div>
                            <?php if ($homeScore === null || $awayScore === null): ?>
                                <span class="small text-body-secondary">暂无记录</span>
                            <?php endif ?>
                        </div>
                    </div>
                </div>
            <?php endforeach ?>
        </div>
        <p class="small text-body-secondary mt-3 mb-0">比分均按主队 : 客队排列。120 分钟为累计比分，点球大战单独记录，不计入场上进球。</p>
    </section>
    <section class="card border-0 shadow-sm rounded-4 mb-4" aria-labelledby="match-info-title">
        <div class="card-body p-4">
            <h2 id="match-info-title" class="h5 fw-bold mb-3">比赛信息</h2>
            <dl class="wc-info row mb-0">
                <dt class="col-sm-3 text-body-secondary fw-normal">所属赛事</dt>
                <dd class="col-sm-9"><?= Html::encode($tournament['name'] ?? '赛事信息待补充') ?></dd>
                <dt class="col-sm-3 text-body-secondary fw-normal">比赛场馆</dt>
                <dd class="col-sm-9"><?= Html::encode($stadium['name'] ?? '场馆信息待补充') ?></dd>
                <dt class="col-sm-3 text-body-secondary fw-normal">所在城市</dt>
                <dd class="col-sm-9"><?= Html::encode($stadium['city'] ?? '城市信息待补充') ?></dd>
                <dt class="col-sm-3 text-body-secondary fw-normal">所在国家</dt>
                <dd class="col-sm-9"><?= Html::encode($countryLabels[$stadium['country_code'] ?? ''] ?? $stadium['country_code'] ?? '国家信息待补充') ?></dd>
            </dl>
            <?php if ($match['summary'] !== null && $match['summary'] !== ''): ?>
                <div class="wc-copy border-top pt-3 mt-2 text-body-secondary"><?= Html::encode($match['summary']) ?></div>
            <?php endif ?>
        </div>
    </section>

    <?php if ($story === null): ?>
        <section class="rounded-4 bg-body-tertiary p-4 text-center" aria-labelledby="match-story-empty-title">
            <h2 id="match-story-empty-title" class="h5 mb-2">精选内容尚未发布</h2>
            <p class="text-body-secondary mb-0">当前展示比赛基本信息。精选故事与事件时间轴将在发布后呈现。</p>
        </section>
    <?php else: ?>
        <div class="row g-4 align-items-start">
            <div class="col-12 col-lg-8">
                <article class="card border-0 shadow-sm rounded-4 overflow-hidden" aria-labelledby="match-story-title">
                    <?php if ($coverUrl !== null): ?>
                        <?= Html::img($coverUrl, ['class' => 'wc-cover', 'alt' => $story['title'] . ' 封面', 'loading' => 'lazy']) ?>
                    <?php endif ?>
                    <div class="card-body p-4 p-md-5">
                        <p class="small text-success fw-semibold mb-2">精选比赛故事</p>
                        <h2 id="match-story-title" class="h3 fw-bold mb-3"><?= Html::encode($story['title']) ?></h2>
                        <?php if ($tags !== []): ?>
                            <ul class="list-inline d-flex flex-wrap gap-2 mb-3" aria-label="故事标签">
                                <?php foreach ($tags as $tag): ?>
                                    <li><?= Html::tag('span', Html::encode($tag['name']), ['class' => 'badge text-bg-light border rounded-pill px-3 py-2']) ?></li>
                                <?php endforeach ?>
                            </ul>
                        <?php endif ?>
                        <p class="wc-copy lead text-body-secondary border-start border-3 ps-3 mb-4"><?= Html::encode($story['summary']) ?></p>
                        <!-- 正文按纯文本编码并保留换行；未确认富文本契约前不直接输出 HTML。 -->
                        <div class="wc-story-body"><?= Html::encode($story['body']) ?></div>
                        <?php if ($sourceUrl !== null): ?>
                            <div class="border-top mt-4 pt-3 small"><?= Html::a('查看内容来源 ↗', $sourceUrl, ['target' => '_blank', 'rel' => 'noopener noreferrer']) ?></div>
                        <?php endif ?>
                    </div>
                </article>
            </div>
            <div class="col-12 col-lg-4">
                <section class="card border-0 shadow-sm rounded-4" aria-labelledby="match-events-title">
                    <div class="card-body p-4">
                        <h2 id="match-events-title" class="h5 fw-bold mb-4">比赛时间轴</h2>
                        <?php if ($events === []): ?>
                            <p class="text-body-secondary mb-0">暂无已整理的比赛事件。</p>
                        <?php else: ?>
                            <ol class="wc-timeline mb-0">
                                <?php foreach ($events as $event): ?>
                                    <li class="wc-event">
                                        <div class="d-flex flex-wrap align-items-center gap-2 mb-2">
                                            <span class="fw-bold">
                                                <?php if ($event['minute'] !== null): ?>
                                                    <?= Html::encode((string) $event['minute']) ?><?php if ($event['added_minute'] !== null && $event['added_minute'] > 0): ?>+<?= Html::encode((string) $event['added_minute']) ?><?php endif ?>′
                                                <?php else: ?>
                                                    时间未标注
                                                <?php endif ?>
                                            </span>
                                            <!-- 未冻结的事件/时段枚举忠实显示代码，不自行过滤未知值。 -->
                                            <span class="badge text-bg-light border text-break"><?= Html::encode($event['period_code']) ?></span>
                                        </div>
                                        <div class="fw-semibold text-break mb-1"><?= Html::encode($event['event_type']) ?></div>
                                        <?php if ($event['player_name'] !== null && $event['player_name'] !== ''): ?>
                                            <div class="text-break small mb-1"><?= Html::encode($event['player_name']) ?></div>
                                        <?php endif ?>
                                        <?php if ($event['team_id'] !== null): ?>
                                            <div class="small text-body-secondary mb-1">球队 ID <?= Html::encode((string) $event['team_id']) ?></div>
                                        <?php endif ?>
                                        <?php if ($event['description'] !== null && $event['description'] !== ''): ?>
                                            <p class="wc-copy small text-body-secondary mb-0"><?= Html::encode($event['description']) ?></p>
                                        <?php endif ?>
                                    </li>
                                <?php endforeach ?>
                            </ol>
                        <?php endif ?>
                    </div>
                </section>
            </div>
        </div>
    <?php endif ?>
</div>
