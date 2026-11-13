<?php
/**
 * WorldCup Journey - 按北京时间展示比赛日历
 * 模块：B/C；负责人：B；依赖：MatchQueryService、match-calendar.js
 * 最近修改：2026-11-13
 */
declare(strict_types=1);
use yii\helpers\Html;
/** @var array $calendar */
$months = [];
foreach ($calendar['days'] as $day) {
    $months[substr($day['date'], 0, 7)][] = $day;
}
?>
<section class="stats-panel match-calendar-panel" aria-labelledby="calendar-title">
    <div class="d-flex justify-content-between flex-wrap gap-2 align-items-center">
        <h2 id="calendar-title" class="h4 mb-0">比赛日历</h2>
        <span class="small text-body-secondary">北京时间 · 点击日期查看比赛</span>
    </div>
    <p id="calendar-status" role="status" aria-live="polite" class="small text-body-secondary mt-2">颜色越深，当天比赛越多。</p>
    <div class="calendar-explorer"><div>
    <div id="match-calendar" class="calendar-months">
    <?php foreach ($months as $month => $days): ?>
        <div class="calendar-month">
            <h3 class="h6"><?= Html::encode($month) ?></h3>
            <div class="calendar-month-body">
                <div class="calendar-weekdays" aria-hidden="true"><?php foreach (['一', '二', '三', '四', '五', '六', '日'] as $weekday): ?><span><?= $weekday ?></span><?php endforeach ?></div>
                <div class="calendar-cells">
                <?php $offset = (int) (new DateTimeImmutable($days[0]['date']))->format('N') - 1; ?>
                <?php for ($i = 0; $i < $offset; ++$i): ?><span class="calendar-padding" aria-hidden="true"></span><?php endfor ?>
                <?php foreach ($days as $day):
                    $description = $day['date'] . '，' . $day['match_count'] . ' 场比赛，已完赛 ' . $day['finished_match_count'] . ' 场，进球 ' . ($day['total_goals'] ?? '暂无'); ?>
                    <button type="button" class="calendar-day calendar-level-<?= min(4, $day['match_count']) ?>" data-date="<?= Html::encode($day['date']) ?>" title="<?= Html::encode($description) ?>" aria-label="<?= Html::encode($description) ?>" aria-pressed="false"><?= (int) substr($day['date'], 8) ?></button>
                <?php endforeach ?>
                </div>
            </div>
        </div>
    <?php endforeach ?>
    <?php if ($months === []): ?><p>暂无有效赛程。</p><?php endif ?>
    </div>
    <div class="calendar-legend small text-body-secondary" aria-label="颜色图例">场次少 <?php for ($level = 0; $level <= 4; ++$level): ?><span class="calendar-swatch calendar-level-<?= $level ?>" title="<?= $level === 4 ? '4 场及以上' : $level . ' 场' ?>"></span><?php endfor ?> 场次多</div>
    </div>
    <div id="calendar-details" class="calendar-details" aria-live="polite"><p class="text-body-secondary mb-0">选择一个日期查看当天比赛。</p></div>
    </div>
    <noscript><details><summary>按日期查看全部比赛</summary>
        <?php foreach ($calendar['days'] as $day): if ($day['match_count'] === 0) continue; ?>
            <h3 class="h6 mt-3"><?= Html::encode($day['date']) ?></h3>
            <?php foreach ($day['matches'] as $match): ?>
                <p><?= Html::a(Html::encode($match['time'] . ' ' . $match['home'] . ' 对阵 ' . $match['away']), ['/wc-match/view', 'id' => $match['id']]) ?></p>
            <?php endforeach ?>
        <?php endforeach ?>
    </details></noscript>
</section>
