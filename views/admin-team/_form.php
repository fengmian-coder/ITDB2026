<?php
/**
 * WorldCup Journey - 球队资料表单
 * 模块：C；负责人：C，B 协助实现；依赖：NationalTeam、Yii Html
 * 最近修改：2026-11-11
 */
declare(strict_types=1);
use yii\helpers\Html;
/** @var app\models\NationalTeam $model */
?>
<div class="card"><div class="card-body p-4">
<?= Html::beginForm($model->isNewRecord ? ['create'] : ['update', 'id' => $model->id], 'post') ?>
<?= Html::errorSummary($model, ['class' => 'alert alert-danger']) ?>
<div class="row g-3">
<?php foreach (['fifa_code' => 3, 'name_zh' => 80, 'name_en' => 100, 'flag_path' => 255] as $attribute => $length): ?>
    <div class="col-md-6"><?= Html::activeLabel($model, $attribute, ['class' => 'form-label']) ?>
    <?= Html::activeTextInput($model, $attribute, ['class' => 'form-control', 'maxlength' => $length, 'required' => $attribute !== 'flag_path']) ?></div>
<?php endforeach; ?>
<div class="col-12"><?= Html::activeLabel($model, 'description', ['class' => 'form-label']) ?>
<?= Html::activeTextarea($model, 'description', ['class' => 'form-control', 'rows' => 5]) ?></div>
<div class="col-12"><?= Html::activeCheckbox($model, 'is_active', ['label' => '启用球队', 'value' => '1', 'uncheck' => '0']) ?></div>
</div>
<p class="small text-body-secondary mt-3">国旗填写 web 目录内素材的相对路径。历史球队可停用，已有比赛仍保留球队资料。</p>
<?= Html::submitButton('保存球队', ['class' => 'btn btn-primary']) ?>
<?= Html::a('返回列表', ['index'], ['class' => 'btn btn-outline-secondary']) ?>
<?= Html::endForm() ?>
</div></div>
