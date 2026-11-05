<?php
/**
 * WorldCup Journey - 后台场馆编辑表单
 * 模块：B；负责人：B；依赖：Stadium、Yii Html、现有 Bootstrap
 * 最近修改：2026-11-05
 */
declare(strict_types=1);
use yii\helpers\Html;
/** @var yii\web\View $this */
/** @var app\models\Stadium $model */
$action = $model->isNewRecord ? ['/admin-stadium/create'] : ['/admin-stadium/update', 'id' => $model->id];
?>
<div class="card border-0 shadow-sm rounded-4">
    <div class="card-body p-4">
        <?= Html::beginForm($action, 'post') ?>
        <?= Html::errorSummary($model, ['class' => 'alert alert-danger', 'header' => '请检查以下字段：']) ?>
        <div class="row g-3">
            <div class="col-12 col-md-6">
                <?= Html::activeLabel($model, 'name', ['class' => 'form-label']) ?>
                <?= Html::activeTextInput($model, 'name', ['class' => 'form-control', 'maxlength' => 120, 'required' => true]) ?>
            </div>
            <div class="col-12 col-md-6">
                <?= Html::activeLabel($model, 'city', ['class' => 'form-label']) ?>
                <?= Html::activeTextInput($model, 'city', ['class' => 'form-control', 'maxlength' => 100, 'required' => true]) ?>
            </div>
            <div class="col-12 col-md-4">
                <?= Html::activeLabel($model, 'country_code', ['class' => 'form-label']) ?>
                <?= Html::activeDropDownList($model, 'country_code', ['CA' => '加拿大（CA）', 'MX' => '墨西哥（MX）', 'US' => '美国（US）'], [
                    'class' => 'form-select', 'prompt' => '请选择国家', 'required' => true,
                ]) ?>
            </div>
            <div class="col-12 col-md-8">
                <?= Html::activeLabel($model, 'timezone_name', ['class' => 'form-label']) ?>
                <?= Html::activeTextInput($model, 'timezone_name', ['class' => 'form-control', 'maxlength' => 64,
                    'placeholder' => '例如 America/Toronto', 'aria-describedby' => 'stadium-timezone-hint']) ?>
                <p id="stadium-timezone-hint" class="small text-body-secondary mt-2 mb-0">可留空；填写场馆对应的时区名称，示例不是默认值。</p>
            </div>
        </div>
        <div class="d-flex flex-wrap gap-2 mt-4">
            <?= Html::submitButton('保存场馆', ['class' => 'btn btn-primary']) ?>
            <?= Html::a('取消', $model->isNewRecord ? ['/admin-stadium/index'] : ['/admin-stadium/view', 'id' => $model->id], ['class' => 'btn btn-outline-secondary']) ?>
        </div>
        <?= Html::endForm() ?>
    </div>
</div>
