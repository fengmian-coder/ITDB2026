<?php
/**
 * WorldCup Journey - 2026 世界杯赛事管理入口
 * 模块：B；负责人：B；依赖：Tournament、Yii Html
 */
declare(strict_types=1);

use yii\helpers\Html;

/** @var yii\web\View $this */
/** @var app\models\Tournament|null $model */
$this->title = '赛事管理';
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="admin-tournament-index pb-4">
    <header class="mb-4">
        <h1 class="h3 fw-bold mb-1">赛事管理</h1>
        <p class="text-body-secondary mb-0">维护 2026 世界杯的名称、日期与简介</p>
    </header>
    <div class="card border-0 shadow-sm rounded-4">
        <div class="card-body p-4">
            <?php if ($model === null): ?>
                <p>尚未建立 2026 世界杯赛事资料。</p>
                <?= Html::a('建立 2026 赛事资料', ['create'], ['class' => 'btn btn-primary']) ?>
            <?php else: ?>
                <h2 class="h5 fw-bold text-break"><?= Html::encode($model->name) ?></h2>
                <p class="text-body-secondary"><?= Html::encode($model->start_date . ' 至 ' . $model->end_date) ?></p>
                <div class="d-flex flex-wrap gap-2">
                    <?= Html::a('查看详情', ['view', 'id' => $model->id], ['class' => 'btn btn-outline-secondary']) ?>
                    <?= Html::a('修改赛事资料', ['update', 'id' => $model->id], ['class' => 'btn btn-primary']) ?>
                </div>
            <?php endif ?>
        </div>
    </div>
</div>
