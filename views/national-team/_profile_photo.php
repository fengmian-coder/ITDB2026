<?php
/** 本地人物照片；出处、拍摄年份及完整署名分别就近和在资料区提供。 */
declare(strict_types=1);
use yii\helpers\Html;
?>
<figure class="team-person-photo <?= !empty($coachPhoto) ? 'team-coach-photo' : '' ?><?= !empty($coachPhoto) && in_array($photo['display_crop'] ?? '', ['portrait', 'contain'], true) ? ' team-coach-photo-portrait' : '' ?>">
    <?= Html::img('@web/' . $photo['path'], ['alt' => ($displayName ?? $photo['name']) . '（' . ($photo['caption'] ?? '资料照') . '）', 'width' => (int) $photo['width'], 'height' => (int) $photo['height'], 'loading' => 'lazy', 'decoding' => 'async', 'style' => ($photo['display_crop'] ?? '') === 'contain' ? 'object-fit: contain;' : null]) ?>
    <figcaption><?= Html::a(Html::encode($photo['caption'] ?? ('资料照 · ' . substr($photo['date'], 0, 4))), $photo['source_url'], ['target' => '_blank', 'rel' => 'noopener noreferrer', 'title' => $photo['author'] . ' · ' . $photo['license']]) ?></figcaption>
</figure>
