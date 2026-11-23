<?php
/** 本地人物照片；出处、拍摄年份及完整署名分别就近和在资料区提供。 */
declare(strict_types=1);
use yii\helpers\Html;

$crop = $photo['display_crop'] ?? '';
$imageStyle = match ($crop) {
    'contain' => 'object-fit: contain;',
    'upper' => 'object-position: center 20%;',
    'head-and-shoulders' => 'object-position: center 10%;',
    'upper-body-action' => 'transform: scale(1.55); transform-origin: 35% 30%;',
    default => null,
};
?>
<figure class="team-person-photo <?= !empty($coachPhoto) ? 'team-coach-photo' : '' ?><?= !empty($coachPhoto) && in_array($crop, ['portrait', 'contain'], true) ? ' team-coach-photo-portrait' : '' ?><?= $crop === 'framed-portrait' ? ' team-player-photo-framed' : '' ?><?= $crop === 'upper-body-action' ? ' overflow-hidden' : '' ?>">
    <?= Html::img('@web/' . $photo['path'], ['alt' => ($displayName ?? $photo['name']) . '（' . ($photo['caption'] ?? '资料照') . '）', 'width' => (int) $photo['width'], 'height' => (int) $photo['height'], 'loading' => 'lazy', 'decoding' => 'async', 'style' => $imageStyle]) ?>
    <figcaption><?= Html::a(Html::encode($photo['caption'] ?? ('资料照 · ' . substr($photo['date'], 0, 4))), $photo['source_url'], ['target' => '_blank', 'rel' => 'noopener noreferrer', 'title' => $photo['author'] . ' · ' . $photo['license']]) ?></figcaption>
</figure>
