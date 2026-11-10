<?php
/**
 * WorldCup Journey - 作业上传、归属及替换表单。
 * 模块：A 作业下载；负责人：A。
 * 依赖：ProjectDocument、ActiveForm。
 * 最近修改：2026-11-10。
 */
declare(strict_types=1);
use app\models\ProjectDocument;
use yii\helpers\Html;
use yii\widgets\ActiveForm;
$this->title = $model->isNewRecord ? '上传作业' : '编辑作业';
$this->params['breadcrumbs'][] = ['label' => '作业管理', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="project-admin"><h1 class="mb-4"><?= Html::encode($this->title) ?></h1>
    <div class="project-admin-panel">
        <?php $form = ActiveForm::begin(['options' => ['enctype' => 'multipart/form-data'], 'enableClientValidation' => false]); ?>
        <?= $form->errorSummary($model) ?>
        <?= $form->field($model, 'title')->textInput(['maxlength' => 160, 'required' => true]) ?>
        <?= $form->field($model, 'document_type')->dropDownList(ProjectDocument::TYPES) ?>
        <?= $form->field($model, 'project_team_id')->dropDownList($teams, ['prompt' => '请选择团队']) ?>
        <?= $form->field($model, 'member_id')->dropDownList($members, ['prompt' => '团队作业请留空'])->hint('个人作业必须选择所属团队的成员') ?>
        <?php if (!$model->isNewRecord): ?><p>当前文件：<?= Html::encode($model->original_name) ?>。不选择新文件则保留原文件</p><?php endif ?>
        <?= $form->field($model, 'uploadFile')->fileInput(['accept' => '.' . implode(',.', ProjectDocument::EXTENSIONS)])->hint('单文件不超过 20 MB。支持 PDF、Office、ZIP、Axure RP、图片与文本资料') ?>
        <div class="d-flex gap-2 mt-4"><?= Html::submitButton('保存作业', ['class' => 'btn btn-success']) ?><?= Html::a('取消', ['index'], ['class' => 'btn btn-outline-secondary']) ?></div>
        <?php ActiveForm::end(); ?>
    </div>
</div>
