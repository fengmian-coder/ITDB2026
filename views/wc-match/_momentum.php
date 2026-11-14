<?php
/**
 * WorldCup Journey - 精选比赛比分走势
 * 模块：B；负责人：B；依赖：MatchMomentumService
 * 最近修改：2026-11-14
 */

declare(strict_types=1);

use yii\helpers\Html;
use yii\helpers\Json;
/** @var array $momentum */
/** @var array $match */
?>
<section class="card border-0 shadow-sm rounded-4 mb-4" aria-labelledby="momentum-title">
    <div class="card-body p-4">
        <h2 id="momentum-title" class="h5 fw-bold">比赛悬念 · 比分走势</h2>
        <?php if (!$momentum['available']): ?>
            <p class="text-body-secondary mb-0"><?= Html::encode($momentum['message']) ?></p>
        <?php else: \app\assets\MatchMomentumAsset::register($this); ?>
            <p class="small text-body-secondary">上方表示<?= Html::encode($match['home_team']['name_zh']) ?>领先，下方表示<?= Html::encode($match['away_team']['name_zh']) ?>领先。横轴按关键时刻排列。</p>
            <div id="match-momentum-chart" style="width:100%;height:300px" aria-label="主队净胜球变化阶梯图"></div>
            <label for="momentum-position" class="form-label small">拖动回看关键时刻</label>
            <input type="range" class="form-range" id="momentum-position" min="0" max="<?= count($momentum['points']) - 1 ?>" value="0">
            <div id="momentum-detail" class="rounded-3 bg-body-tertiary p-3 mt-2" role="status" aria-live="polite"></div>
            <div id="momentum-nodes" class="d-flex flex-wrap gap-2 mt-3">
                <?php foreach ($momentum['points'] as $index => $point): ?>
                    <button type="button" class="btn btn-sm btn-outline-secondary" data-point="<?= $index ?>"><?= Html::encode($point['label'] . ' · ' . $point['home'] . '–' . $point['away']) ?></button>
                <?php endforeach ?>
            </div>
            <p class="small text-body-secondary mt-3 mb-0">走势核对90分钟及加时累计比分，点球大战单独展示。</p>
            <?php if ($match['home_penalties'] !== null): ?>
                <p class="small mt-2 mb-0">点球大战 <?= (int) $match['home_penalties'] ?>–<?= (int) $match['away_penalties'] ?></p>
            <?php endif ?>
            <script id="match-momentum-data" type="application/json"><?= Json::htmlEncode($momentum) ?></script>
        <?php endif ?>
    </div>
</section>
