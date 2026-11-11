<?php
/**
 * WorldCup Journey - 比赛及历程页面的球队标识
 * 模块：B 赛事与比赛后端
 * 负责人：B
 * 依赖：Service 返回的球队资料数组、Yii Html / Url
 * 最近修改：2026-11-11
 */

declare(strict_types=1);

use yii\helpers\Html;
use yii\helpers\Url;

/** @var array|null $team */
/** @var int|string $teamId */

$name = $team['name_zh'] ?? $team['name_en'] ?? ('球队 ID ' . $teamId);
$path = str_replace('\\', '/', trim((string) ($team['flag_path'] ?? '')));
$segments = explode('/', $path);
$flagUrl = null;
if ($path !== '' && !str_starts_with($path, '/') && !preg_match('/[\x00-\x1F\x7F%:?#]/', $path)
    && !in_array('..', $segments, true) && !in_array('.', $segments, true)) {
    $flagUrl = Url::to('@web/' . implode('/', array_map('rawurlencode', $segments)));
}
?>
<span class="d-inline-flex flex-column align-items-center gap-1 mw-100">
    <?php if ($flagUrl !== null): ?>
        <?= Html::img($flagUrl, ['alt' => $name . '国旗', 'loading' => 'lazy',
            'class' => 'rounded border', 'style' => 'width:48px;height:32px;object-fit:contain;']) ?>
    <?php endif ?>
    <?= Html::a(Html::encode($name), ['/national-team/view', 'id' => $teamId], [
        'class' => 'fw-bold text-reset text-decoration-none',
    ]) ?>
    <?php if (($team['fifa_code'] ?? '') !== ''): ?>
        <span class="small fw-normal opacity-75"><?= Html::encode($team['fifa_code']) ?></span>
    <?php endif ?>
</span>
