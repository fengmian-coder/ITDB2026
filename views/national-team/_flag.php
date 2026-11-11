<?php
/**
 * WorldCup Journey - 球队队徽/占位图
 * 模块：C 球队与统计
 * 负责人：C
 * 依赖：NationalTeam::flag_path
 */

declare(strict_types=1);

use yii\helpers\Html;
use yii\helpers\Url;

/** @var app\models\NationalTeam $team */
/** @var string $size sm|lg */

$size = $size ?? 'sm';
$width = $size === 'lg' ? 96 : 48;
$height = $size === 'lg' ? 64 : 32;

$path = str_replace('\\', '/', trim((string) ($team->flag_path ?? '')));
$url = null;
if ($path !== '' && !str_starts_with($path, '/')
    && !preg_match('/[\x00-\x1F\x7F%:?#]/', $path)) {
    $segments = explode('/', $path);
    if (!in_array('..', $segments, true) && !in_array('.', $segments, true)) {
        $url = Url::to('@web/' . implode('/', array_map('rawurlencode', $segments)));
    }
}
?>
<?php if ($url !== null): ?>
    <?= Html::img($url, [
        'alt' => $team->name_zh,
        'class' => 'rounded border bg-body-tertiary',
        'style' => "width:{$width}px;height:{$height}px;object-fit:cover;",
        'loading' => 'lazy',
    ]) ?>
<?php else: ?>
    <span class="d-inline-flex align-items-center justify-content-center rounded border bg-body-tertiary text-body-secondary fw-bold"
          style="width:<?= $width ?>px;height:<?= $height ?>px;font-size:<?= $size === 'lg' ? '2rem' : '1.1rem' ?>;"
          aria-hidden="true"><?= Html::encode(mb_substr((string) $team->name_zh, 0, 1)) ?></span>
<?php endif; ?>
