<?php
/**
 * WorldCup Journey - 团队新增与编辑表单。
 * 模块：A 团队资料；负责人：A。
 * 依赖：ProjectTeam、Yii ActiveForm。
 * 最近修改：2026-11-10。
 */
declare(strict_types=1);
use yii\helpers\Html;
use yii\widgets\ActiveForm;

$this->title = $model->isNewRecord ? '新增团队' : '编辑团队';
$this->params['breadcrumbs'][] = ['label' => '团队与成员', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="project-admin">
    <h1 class="mb-4"><?= Html::encode($this->title) ?></h1>
    <div class="project-admin-panel">
        <?php $form = ActiveForm::begin(['enableClientValidation' => false]); ?>
        <?= $form->errorSummary($model) ?>
        <?= $form->field($model, 'name')->textInput(['maxlength' => 100, 'required' => true]) ?>
        <?= $form->field($model, 'description')->textarea(['rows' => 7, 'maxlength' => 10000])->hint('支持换行，内容将作为纯文本展示') ?>
        <?= $form->field($model, 'logo_path')->textInput(['maxlength' => 255, 'placeholder' => 'images/team/logo.png'])->hint('可留空。填写已放入 web/images 的 PNG、JPG、WebP 或 GIF 图片路径') ?>
        <div class="d-flex gap-2 mt-4">
            <?= Html::submitButton('保存团队资料', ['class' => 'btn btn-success']) ?>
            <?= Html::a('取消', $model->isNewRecord ? ['index'] : ['view', 'id' => $model->id], ['class' => 'btn btn-outline-secondary']) ?>
        </div>
        <?php ActiveForm::end(); ?>
    </div>
</div>
