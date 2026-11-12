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
?>
<span class="d-inline-flex flex-column align-items-center gap-1 mw-100">
    <?= \app\components\TeamFlag::render($team['fifa_code'] ?? null, $name, $team['flag_path'] ?? null) ?>
    <?= Html::a(Html::encode($name), ['/national-team/view', 'id' => $teamId], [
        'class' => 'fw-bold text-reset text-decoration-none',
    ]) ?>
    <?php if (($team['fifa_code'] ?? '') !== ''): ?>
        <span class="small fw-normal opacity-75"><?= Html::encode($team['fifa_code']) ?></span>
    <?php endif ?>
</span>
