<?php
/**
 * WorldCup Journey - 后台精选故事详情
 * 模块：B；负责人：B；依赖：MatchStory、Html、现有 Bootstrap
 * 最近修改：2026-11-05
 */
declare(strict_types=1);
use yii\helpers\Html;
/** @var yii\web\View $this */
/** @var app\models\MatchStory $model */
$this->title = '精选故事详情：' . $model->title;
$this->params['breadcrumbs'][] = ['label' => '精选故事管理', 'url' => ['/admin-story/index']];
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="admin-story-view pb-4">
    <header class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
        <div class="text-break"><h1 class="h3 fw-bold mb-2"><?= Html::encode($model->title) ?></h1>
            <span class="badge <?= $model->isPublished() ? 'text-bg-success' : 'text-bg-secondary' ?>"><?= $model->isPublished() ? '已发布' : '未发布' ?></span>
        </div>
        <div class="d-flex flex-wrap gap-2">
            <?= Html::a('修改故事', ['/admin-story/update', 'id' => $model->id], ['class' => 'btn btn-primary']) ?>
            <?= Html::a('管理事件时间轴', ['/admin-story/events', 'id' => $model->id], ['class' => 'btn btn-outline-primary']) ?>
            <?= Html::a('管理标签', ['/admin-story/tags', 'id' => $model->id], ['class' => 'btn btn-outline-primary']) ?>
            <?= Html::a('返回列表', ['/admin-story/index'], ['class' => 'btn btn-outline-secondary']) ?>
            <?php if ($model->isPublished()): ?>
                <?= Html::a('查看前台比赛', ['/wc-match/view', 'id' => $model->match_id], ['class' => 'btn btn-outline-success']) ?>
            <?php endif ?>
        </div>
    </header>
    <article class="card border-0 shadow-sm rounded-4 mb-4">
        <div class="card-body p-4">
            <dl class="row mb-4">
                <?php foreach (['id', 'match_id', 'cover_path', 'source_url', 'created_at', 'updated_at'] as $attribute): ?>
                    <dt class="col-12 col-md-3 text-body-secondary fw-normal"><?= Html::encode($model->getAttributeLabel($attribute)) ?></dt>
                    <dd class="col-12 col-md-9 text-break"><?= Html::encode($model->$attribute === null || $model->$attribute === '' ? '—' : (string) $model->$attribute) ?></dd>
                <?php endforeach ?>
            </dl>
            <h2 class="h6 fw-bold">摘要</h2>
            <p class="text-body-secondary text-break" style="white-space: pre-wrap"><?= Html::encode($model->summary) ?></p>
            <h2 class="h6 fw-bold mt-4">正文</h2>
            <div class="text-break" style="white-space: pre-wrap; line-height: 1.9"><?= Html::encode($model->body) ?></div>
            <p class="small text-body-secondary border-top pt-3 mt-4 mb-0">创建与更新时间按 UTC 存储；无时间记录时显示“—”</p>
        </div>
    </article>
    <section class="card border-danger-subtle rounded-4" aria-labelledby="delete-story-title">
        <div class="card-body p-4">
            <h2 id="delete-story-title" class="h5">删除故事</h2>
            <p class="small text-body-secondary">删除将清理本故事与标签的关联，保留比赛、事件和标签字典；故事删除后前台不再展示该场精选内容</p>
            <?= Html::beginForm(['/admin-story/delete', 'id' => $model->id], 'post') ?>
                <div class="form-check mb-3">
                    <?= Html::checkbox('confirm_delete', false, ['id' => 'confirm-delete-story', 'value' => '1', 'required' => true, 'class' => 'form-check-input']) ?>
                    <?= Html::label('确认删除该故事，此操作不可撤销', 'confirm-delete-story', ['class' => 'form-check-label']) ?>
                </div>
                <?= Html::submitButton('删除故事', ['class' => 'btn btn-outline-danger']) ?>
            <?= Html::endForm() ?>
        </div>
    </section>
</div>
