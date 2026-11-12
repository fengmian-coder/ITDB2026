<?php
/**
 * WorldCup Journey - 球队详情与参赛信息维护
 * 模块：C；负责人：C，B 协助实现；依赖：NationalTeam、TournamentTeam、Tournament
 * 最近修改：2026-11-11
 */
declare(strict_types=1);
use yii\helpers\Html;
use app\models\TournamentTeam;
$this->title = '球队详情';
$this->params['breadcrumbs'][] = ['label' => '球队管理', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>
<header class="admin-page-heading"><h1><?= Html::encode($model->name_zh) ?></h1><?= Html::a('修改球队', ['update', 'id' => $model->id], ['class' => 'btn btn-primary']) ?></header>
<p><?= Html::a('维护 2026 球队档案', ['profile', 'id' => $model->id], ['class' => 'btn btn-outline-primary']) ?></p>
<dl class="row">
<?php foreach (['id', 'fifa_code', 'name_zh', 'name_en', 'flag_path', 'description'] as $attribute): ?>
<dt class="col-md-3"><?= Html::encode($model->getAttributeLabel($attribute)) ?></dt><dd class="col-md-9 text-break"><?= nl2br(Html::encode((string) $model->$attribute)) ?></dd>
<?php endforeach; ?></dl>
<h2 class="h4">参赛信息</h2>
<?php $participations = []; foreach ($model->tournamentTeams as $row) { $participations[$row->tournament_id] = $row; } ?>
<?php foreach ($tournaments as $tournament): $participation = $participations[$tournament->id] ?? null; ?>
<section class="card mb-3"><div class="card-body">
<h3 class="h5"><?= Html::encode($tournament->name) ?></h3>
<?= Html::beginForm(['participation', 'id' => $model->id], 'post', ['class' => 'row g-3']) ?>
<?= Html::hiddenInput('TournamentTeam[tournament_id]', $tournament->id) ?>
<div class="col-md-4"><?= Html::label('小组', 'group-' . $tournament->id, ['class' => 'form-label']) ?><?= Html::dropDownList('TournamentTeam[group_code]', $participation?->group_code, array_combine(range('A', 'L'), range('A', 'L')), ['id' => 'group-' . $tournament->id, 'class' => 'form-select', 'prompt' => '选择小组', 'required' => true]) ?></div>
<div class="col-md-4"><?= Html::label('最终成绩', 'result-' . $tournament->id, ['class' => 'form-label']) ?><?= Html::dropDownList('TournamentTeam[final_result_code]', $participation?->final_result_code, TournamentTeam::FINAL_RESULT_LABELS, ['id' => 'result-' . $tournament->id, 'class' => 'form-select', 'prompt' => '暂无']) ?></div>
<div class="col-md-4 align-self-end"><?= Html::submitButton($participation ? '保存参赛信息' : '登记参赛', ['class' => 'btn btn-outline-primary']) ?></div>
<?= Html::endForm() ?></div></section>
<?php endforeach; ?>
<section class="card mt-4"><div class="card-body"><h2 class="h4">删除球队</h2><p>已有参赛、比赛或事件引用时会阻止删除，可在修改页面停用球队。</p>
<?= Html::beginForm(['delete', 'id' => $model->id], 'post') ?>
<?= Html::checkbox('confirm_delete', false, ['value' => '1', 'required' => true, 'label' => '确认删除球队']) ?>
<?= Html::submitButton('删除球队', ['class' => 'btn btn-outline-danger']) ?><?= Html::endForm() ?>
</div></section>
