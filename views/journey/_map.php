<?php
/**
 * WorldCup Journey - 球队旅程地图与场馆比赛列表
 * 模块：B；负责人：B；依赖：JourneyMapService、JourneyMapAsset
 * 最近修改：2026-11-13
 */
declare(strict_types=1);
use app\assets\JourneyMapAsset;
use yii\helpers\Html;
use yii\helpers\Json;
use yii\helpers\Url;
/** @var array $map */
/** @var array $team */
JourneyMapAsset::register($this);
?>
<section class="journey-map-panel mb-4" aria-labelledby="journey-map-title">
    <div class="d-flex justify-content-between align-items-start flex-wrap gap-3 mb-3">
        <div>
            <h2 id="journey-map-title" class="h4 fw-bold"><?= $team['final_result_code'] === 'CHAMPION' ? '夺冠之路地图' : '世界杯旅程地图' ?></h2>
            <p class="small text-body-secondary mb-0">按比赛时间连接场馆，点击地点查看该站比赛。</p></div>
        <button type="button" id="journey-map-reset" class="btn btn-sm btn-outline-secondary">重置地图</button>
    </div>
    <div class="journey-map-layout">
        <div class="journey-map-surface"><div id="journey-map" role="img" aria-label="<?= Html::encode($team['name_zh'] . '的世界杯比赛地点地图') ?>"></div>
            <div class="journey-map-legend small"><span>● 已比赛地点</span><span>○ 待比赛地点</span><span>实线为已完成顺序，虚线为待进行顺序</span></div>
        </div>
        <aside class="journey-map-detail" aria-label="所选场馆的比赛" id="journey-map-detail" aria-live="polite">
            <p>选择一个场馆查看比赛。</p>
        </aside>
    </div>
    <nav id="journey-map-stops" class="journey-map-stops" aria-label="按首次比赛时间排列的场馆">
        <?php foreach ($map['stops'] as $index => $stop): ?>
            <button class="btn btn-sm btn-outline-secondary" type="button" data-stop="<?= Html::encode($stop['id']) ?>"><?= Html::encode(($index + 1) . ' · ' . $stop['city']) ?></button>
        <?php endforeach ?>
    </nav>
    <p id="journey-map-status" class="small text-body-secondary mt-3 mb-0" role="status">正在加载地图…</p>
    <?php if ($map['unmapped_match_count'] > 0): ?><p class="small text-body-secondary mt-2 mb-0"><?= (int) $map['unmapped_match_count'] ?> 场比赛缺少已核对的场馆坐标，可在下方时间线查看。</p><?php endif ?>
    <details class="small mt-3"><summary>地图说明与数据来源</summary>
        <p class="mt-2 mb-0">连线表达比赛地点的先后顺序，同场馆的多场比赛合并展示。坐标来自 <?= Html::a('OpenFootball', 'https://github.com/openfootball/worldcup.json/blob/master/2026/worldcup.stadiums.json', ['target' => '_blank', 'rel' => 'noopener noreferrer']) ?>，底图来自 <?= Html::a('Natural Earth', 'https://www.naturalearthdata.com/about/terms-of-use/', ['target' => '_blank', 'rel' => 'noopener noreferrer']) ?>。</p>
    </details>
    <noscript><p class="small mt-2">启用 JavaScript 后可操作地图，下方时间线提供完整比赛记录。</p></noscript>
</section>
<script id="journey-map-data" type="application/json"><?= Json::htmlEncode(['map' => $map, 'team_id' => $team['id'], 'team_name' => $team['name_zh'],
    'geoUrl' => Url::to('@web/maps/north-america.geo.json'), 'matchUrl' => Url::to(['/wc-match/view'])]) ?></script>
