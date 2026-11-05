<?php
/**
 * WorldCup Journey - 后台赛事编辑表单
 * 模块：B；负责人：B；依赖：Tournament、Yii Html、现有 Bootstrap
 * 最近修改：2026-11-05
 */
declare(strict_types=1);
use yii\helpers\Html;
/** @var yii\web\View $this */
/** @var app\models\Tournament $model */
$action = $model->isNewRecord ? ['/admin-tournament/create'] : ['/admin-tournament/update', 'id' => $model->id];
?>
<div class="card border-0 shadow-sm rounded-4">
    <div class="card-body p-4">
        <?= Html::beginForm($action, 'post') ?>
        <?= Html::errorSummary($model, ['class' => 'alert alert-danger', 'header' => '请检查以下字段：']) ?>
        <div class="row g-3">
            <div class="col-12 col-md-4">
                <?= Html::activeLabel($model, 'year', ['class' => 'form-label']) ?>
                <?= Html::activeInput('number', $model, 'year', ['class' => 'form-control', 'required' => true, 'min' => 0, 'max' => 65535, 'step' => 1]) ?>
            </div>
            <div class="col-12 col-md-8">
                <?= Html::activeLabel($model, 'name', ['class' => 'form-label']) ?>
                <?= Html::activeTextInput($model, 'name', ['class' => 'form-control', 'required' => true, 'maxlength' => 120]) ?>
            </div>
            <div class="col-12 col-md-6">
                <?= Html::activeLabel($model, 'start_date', ['class' => 'form-label']) ?>
                <?= Html::activeInput('date', $model, 'start_date', ['class' => 'form-control', 'required' => true]) ?>
            </div>
            <div class="col-12 col-md-6">
                <?= Html::activeLabel($model, 'end_date', ['class' => 'form-label']) ?>
                <?= Html::activeInput('date', $model, 'end_date', ['class' => 'form-control', 'required' => true]) ?>
            </div>
            <div class="col-12">
                <?= Html::activeLabel($model, 'description', ['class' => 'form-label']) ?>
                <?= Html::activeTextarea($model, 'description', ['class' => 'form-control', 'rows' => 6]) ?>
            </div>
        </div>
        <div class="d-flex flex-wrap gap-2 mt-4">
            <?= Html::submitButton('保存赛事', ['class' => 'btn btn-primary']) ?>
            <?= Html::a('取消', $model->isNewRecord ? ['/admin-tournament/index'] : ['/admin-tournament/view', 'id' => $model->id], ['class' => 'btn btn-outline-secondary']) ?>
        </div>
        <?= Html::endForm() ?>
    </div>
</div>
