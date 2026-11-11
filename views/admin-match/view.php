<?php
/**
 * WorldCup Journey - 后台比赛详情与删除确认
 * 模块：B；负责人：B；依赖：WcMatch、Html、现有 Bootstrap
 * 最近修改：2026-11-05
 */
declare(strict_types=1);
use yii\helpers\Html;
/** @var yii\web\View $this */
/** @var app\models\WcMatch $model */
$this->title = '比赛详情 #' . $model->match_no;
$this->params['breadcrumbs'][] = ['label' => '比赛管理', 'url' => ['/admin-match/index']];
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="admin-match-view pb-4">
    <header class="d-flex flex-wrap justify-content-between gap-3 mb-4"><h1 class="h3 fw-bold"><?= Html::encode($this->title) ?></h1>
        <div class="d-flex gap-2">
            <?= Html::a('编辑比赛', ['/admin-match/update', 'id' => $model->id], ['class' => 'btn btn-primary']) ?>
            <?= Html::a('查看前台', ['/wc-match/view', 'id' => $model->id], ['class' => 'btn btn-outline-primary']) ?>
            <?= Html::a('返回列表', ['/admin-match/index'], ['class' => 'btn btn-outline-secondary']) ?>
        </div></header>
    <div class="card border-0 shadow-sm rounded-4 mb-4"><div class="card-body p-4"><dl class="row mb-0">
        <?php foreach (['id', 'tournament_id', 'match_no', 'home_team_id', 'away_team_id', 'stadium_id', 'kickoff_utc',
            'stage_code', 'group_code', 'status', 'home_score_90', 'away_score_90', 'home_score_120', 'away_score_120',
            'home_penalties', 'away_penalties', 'winner_team_id', 'summary'] as $attribute): ?>
            <dt class="col-12 col-md-3 text-body-secondary fw-normal"><?= Html::encode($model->getAttributeLabel($attribute)) ?></dt>
            <dd class="col-12 col-md-9 text-break" style="white-space: pre-wrap"><?= Html::encode($model->$attribute === null || $model->$attribute === '' ? '—' : (string) $model->$attribute) ?></dd>
        <?php endforeach ?>
    </dl></div></div>
    <section class="card border-danger-subtle"><div class="card-body p-4"><h2 class="h5">删除比赛</h2>
        <p>已有故事、事件或其他正式外键引用时不能直接删除，不会级联清空历史数据。取消比赛可通过编辑状态处理</p>
        <?= Html::beginForm(['/admin-match/delete', 'id' => $model->id], 'post') ?>
        <div class="form-check mb-3">
            <?= Html::checkbox('confirm_delete', false, ['id' => 'match-confirm-delete', 'value' => '1', 'required' => true, 'class' => 'form-check-input']) ?>
            <?= Html::label('确认删除该比赛，此操作不可撤销', 'match-confirm-delete', ['class' => 'form-check-label']) ?></div>
        <?= Html::submitButton('删除比赛', ['class' => 'btn btn-outline-danger']) ?>
        <?= Html::endForm() ?>
    </div></section>
</div>
