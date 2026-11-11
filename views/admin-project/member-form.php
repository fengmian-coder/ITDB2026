<?php
/**
 * WorldCup Journey - 成员新增与编辑表单。
 * 模块：A 团队资料；负责人：A。
 * 依赖：ProjectMember、ProjectTeam、Yii ActiveForm。
 * 最近修改：2026-11-10。
 */
declare(strict_types=1);
use yii\helpers\Html;
use yii\widgets\ActiveForm;

$this->title = $model->isNewRecord ? '新增成员' : '编辑成员';
$this->params['breadcrumbs'][] = ['label' => '团队与成员', 'url' => ['index']];
$this->params['breadcrumbs'][] = ['label' => $team->name, 'url' => ['view', 'id' => $team->id]];
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="project-admin">
    <h1><?= Html::encode($this->title) ?></h1>
    <p class="text-body-secondary">所属团队：<?= Html::encode($team->name) ?></p>
    <div class="project-admin-panel">
        <?php $form = ActiveForm::begin(['enableClientValidation' => false]); ?>
        <?= $form->errorSummary($model) ?>
        <?= $form->field($model, 'display_name')->textInput(['maxlength' => 80, 'required' => true]) ?>
        <?= $form->field($model, 'role_title')->textInput(['maxlength' => 100, 'required' => true]) ?>
        <?= $form->field($model, 'bio')->textarea(['rows' => 6, 'maxlength' => 10000])->hint('填写实际分工与个人介绍，支持换行') ?>
        <?= $form->field($model, 'sort_order')->input('number', ['step' => 1, 'min' => -2147483648, 'max' => 2147483647])->hint('数字越小越靠前；相同时按创建顺序排列') ?>
        <div class="d-flex gap-2 mt-4">
            <?= Html::submitButton('保存成员资料', ['class' => 'btn btn-success']) ?>
            <?= Html::a('取消', ['view', 'id' => $team->id], ['class' => 'btn btn-outline-secondary']) ?>
        </div>
        <?php ActiveForm::end(); ?>
    </div>
</div>
