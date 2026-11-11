<?php
/**
 * WorldCup Journey - 后台赛事详情及删除确认
 * 模块：B；负责人：B；依赖：Tournament、AdminTournamentController、现有 Bootstrap
 * 最近修改：2026-11-05
 */
declare(strict_types=1);
use yii\helpers\Html;
/** @var yii\web\View $this */
/** @var app\models\Tournament $model */
$this->title = '赛事详情：' . $model->name;
$this->params['breadcrumbs'][] = ['label' => '赛事管理', 'url' => ['/admin-tournament/index']];
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="admin-tournament-view pb-4">
    <div class="d-flex flex-wrap justify-content-between gap-3 align-items-center mb-4">
        <h1 class="h3 fw-bold mb-0 text-break"><?= Html::encode($model->name) ?></h1>
        <div class="d-flex gap-2">
            <?= Html::a('修改赛事', ['/admin-tournament/update', 'id' => $model->id], ['class' => 'btn btn-primary']) ?>
            <?= Html::a('返回列表', ['/admin-tournament/index'], ['class' => 'btn btn-outline-secondary']) ?>
        </div>
    </div>
    <div class="card border-0 shadow-sm rounded-4 mb-4">
        <div class="card-body p-4">
            <dl class="row mb-0">
                <?php foreach (['id', 'year', 'name', 'start_date', 'end_date', 'description'] as $attribute): ?>
                    <dt class="col-12 col-md-3 text-body-secondary fw-normal"><?= Html::encode($model->getAttributeLabel($attribute)) ?></dt>
                    <dd class="col-12 col-md-9 text-break" style="white-space: pre-wrap"><?= Html::encode($model->$attribute === null || $model->$attribute === '' ? '—' : (string) $model->$attribute) ?></dd>
                <?php endforeach ?>
            </dl>
        </div>
    </div>
    <section class="card border-danger-subtle rounded-4" aria-labelledby="delete-tournament-title">
        <div class="card-body p-4">
            <h2 id="delete-tournament-title" class="h5">删除赛事</h2>
            <p class="small text-body-secondary">已被比赛或参赛记录引用的赛事不能删除，以保留赛事历史</p>
            <?= Html::beginForm(['/admin-tournament/delete', 'id' => $model->id], 'post') ?>
                <div class="form-check mb-3">
                    <?= Html::checkbox('confirm_delete', false, ['id' => 'confirm-delete-tournament', 'value' => '1', 'required' => true, 'class' => 'form-check-input']) ?>
                    <?= Html::label('确认删除该赛事，此操作不可撤销', 'confirm-delete-tournament', ['class' => 'form-check-label']) ?>
                </div>
                <?= Html::submitButton('删除赛事', ['class' => 'btn btn-outline-danger']) ?>
            <?= Html::endForm() ?>
        </div>
    </section>
</div>
