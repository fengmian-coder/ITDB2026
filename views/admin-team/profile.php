<?php
/**
 * WorldCup Journey - 2026球队简介、教练与代表球员维护
 * 模块：A/C 后台联调；负责人：A；依赖：TeamProfileForm、AdminTeamController
 * 最近修改：2026-11-12
 */
declare(strict_types=1);
use yii\helpers\Html;
use app\models\TeamProfileForm;
$this->title = '维护球队档案';
$this->params['breadcrumbs'][] = ['label' => '球队管理', 'url' => ['index']];
$this->params['breadcrumbs'][] = ['label' => $team->name_zh, 'url' => ['view', 'id' => $team->id]];
$this->params['breadcrumbs'][] = $this->title;
?>
<header class="admin-page-heading"><div><h1><?= Html::encode($team->name_zh) ?> · 2026 球队档案</h1><p>填写本届参赛时的资料，人物介绍须与来源一致</p></div></header>
<?php if (!$schemaReady): ?><div class="alert alert-warning">请先在项目终端运行 php yii team-profile/prepare，再保存档案</div><?php endif; ?>
<?= Html::beginForm(['profile', 'id' => $team->id], 'post') ?>
<?= Html::errorSummary($form, ['class' => 'alert alert-danger']) ?>
<section class="card mb-4"><div class="card-body p-4">
<h2 class="h4">球队与教练</h2>
<label class="form-label" for="profile-summary">球队简介</label>
<?= Html::activeTextarea($form, 'summary', ['id' => 'profile-summary', 'class' => 'form-control mb-3', 'rows' => 5, 'maxlength' => 2000, 'required' => true]) ?>
<label class="form-label" for="profile-coach">本届主教练</label>
<?= Html::activeTextInput($form, 'coach_name', ['id' => 'profile-coach', 'class' => 'form-control mb-3', 'maxlength' => 160, 'required' => true]) ?>
<label class="form-label" for="profile-coach-description">教练简短介绍</label>
<?= Html::activeTextarea($form, 'coach_description', ['id' => 'profile-coach-description', 'class' => 'form-control', 'rows' => 2, 'maxlength' => 500, 'required' => true]) ?>
</div></section>
<section class="card mb-4"><div class="card-body p-4"><h2 class="h4">三位代表球员</h2>
<?php for ($i = 0; $i < 3; ++$i): $player = is_array($form->players) && is_array($form->players[$i] ?? null) ? $form->players[$i] : []; ?>
<fieldset class="border rounded p-3 mb-3"><legend class="float-none w-auto px-2 fs-6">球员 <?= $i + 1 ?></legend><div class="row g-3">
<?php foreach (['name' => '姓名', 'shirt_number' => '球衣号码'] as $key => $label): ?>
<div class="col-md-4"><label class="form-label" for="player-<?= $i ?>-<?= $key ?>"><?= $label ?></label>
<?= Html::textInput("TeamProfileForm[players][$i][$key]", is_scalar($player[$key] ?? '') ? ($player[$key] ?? '') : '', ['id' => "player-$i-$key", 'class' => 'form-control', 'maxlength' => $key === 'name' ? 160 : 2, 'required' => true]) ?></div>
<?php endforeach; ?>
<div class="col-md-4"><label class="form-label" for="player-<?= $i ?>-position">位置</label>
<?= Html::dropDownList("TeamProfileForm[players][$i][position]", is_string($player['position'] ?? '') ? ($player['position'] ?? '') : '', TeamProfileForm::POSITIONS, ['id' => "player-$i-position", 'class' => 'form-select', 'prompt' => '选择位置', 'required' => true]) ?></div>
<div class="col-12"><label class="form-label" for="player-<?= $i ?>-description">简短介绍</label>
<?= Html::textarea("TeamProfileForm[players][$i][description]", is_string($player['description'] ?? '') ? ($player['description'] ?? '') : '', ['id' => "player-$i-description", 'class' => 'form-control', 'rows' => 2, 'maxlength' => 500, 'required' => true]) ?></div>
</div></fieldset>
<?php endfor; ?>
</div></section>
<section class="card mb-4"><div class="card-body p-4"><h2 class="h4">资料来源</h2>
<?php foreach (['source_url' => '来源链接', 'source_label' => '来源名称', 'edition' => '资料时点／版本'] as $attribute => $label): ?>
<label class="form-label" for="profile-<?= $attribute ?>"><?= $label ?></label>
<?= Html::activeTextInput($form, $attribute, ['id' => 'profile-' . $attribute, 'class' => 'form-control mb-3', 'maxlength' => $attribute === 'source_url' ? 500 : 160, 'required' => true]) ?>
<?php endforeach; ?></div></section>
<?= Html::submitButton('保存档案', ['class' => 'btn btn-primary', 'disabled' => !$schemaReady]) ?>
<?= Html::a('返回球队详情', ['view', 'id' => $team->id], ['class' => 'btn btn-outline-secondary']) ?>
<?= Html::endForm() ?>
