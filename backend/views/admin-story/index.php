<?php
/**
 * WorldCup Journey - 后台精选故事列表
 * 模块：B；负责人：B；依赖：MatchStorySearch、ActiveDataProvider、Bootstrap LinkPager
 * 最近修改：2026-11-05
 */
declare(strict_types=1);
use yii\bootstrap5\LinkPager;
use yii\helpers\Html;
/** @var yii\web\View $this */
/** @var app\models\MatchStorySearch $searchModel */
/** @var yii\data\ActiveDataProvider $dataProvider */
$this->title = '精选故事管理';
$this->params['breadcrumbs'][] = $this->title;
$models = $dataProvider->getModels();
?>
<div class="admin-story-index pb-4">
    <header class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
        <div><h1 class="h3 fw-bold mb-1">精选故事管理</h1><p class="text-body-secondary mb-0">整理故事内容，审核后发布到比赛详情</p></div>
        <?= Html::a('新增故事', ['/admin-story/create'], ['class' => 'btn btn-primary']) ?>
    </header>
    <div class="card border-0 shadow-sm rounded-4 mb-4"><div class="card-body p-4">
        <?= Html::beginForm(['/admin-story/index'], 'get', ['class' => 'row g-3 align-items-end']) ?>
            <div class="col-12 col-md-4">
                <?= Html::activeLabel($searchModel, 'title', ['class' => 'form-label']) ?>
                <?= Html::activeTextInput($searchModel, 'title', ['class' => 'form-control', 'maxlength' => 150]) ?>
            </div>
            <div class="col-12 col-md-2">
                <?= Html::activeLabel($searchModel, 'match_id', ['class' => 'form-label']) ?>
                <?= Html::activeTextInput($searchModel, 'match_id', ['class' => 'form-control', 'inputmode' => 'numeric', 'pattern' => '[0-9]+']) ?>
            </div>
            <div class="col-12 col-md-2">
                <?= Html::activeLabel($searchModel, 'is_published', ['class' => 'form-label']) ?>
                <?= Html::activeDropDownList($searchModel, 'is_published', ['0' => '未发布', '1' => '已发布'], ['class' => 'form-select', 'prompt' => '全部状态']) ?>
            </div>
            <div class="col-12 col-md-2">
                <?= Html::label('排序', 'story-sort', ['class' => 'form-label']) ?>
                <?= Html::dropDownList('sort', Yii::$app->request->get('sort', '-id'), [
                    '-id' => 'ID 降序', 'id' => 'ID 升序', 'title' => '标题升序', '-title' => '标题降序',
                    'match_id' => '比赛 ID 升序', '-match_id' => '比赛 ID 降序', 'is_published' => '未发布优先', '-is_published' => '已发布优先',
                ], ['id' => 'story-sort', 'class' => 'form-select']) ?>
            </div>
            <div class="col-12 col-md-2 d-flex gap-2">
                <?= Html::submitButton('搜索', ['class' => 'btn btn-primary']) ?>
                <?= Html::a('重置', ['/admin-story/index'], ['class' => 'btn btn-outline-secondary']) ?>
            </div>
        <?= Html::endForm() ?>
    </div></div>
    <p class="small text-body-secondary">共 <?= Html::encode((string) $dataProvider->getTotalCount()) ?> 篇故事</p>
    <?php if ($models === []): ?>
        <div class="rounded-4 border p-5 text-center text-body-secondary">暂无符合条件的故事</div>
    <?php else: ?>
        <div class="table-responsive border rounded-4 mb-3"><table class="table align-middle mb-0">
            <caption class="visually-hidden">精选故事管理列表</caption>
            <thead class="table-light"><tr><th scope="col">故事 ID</th><th scope="col">比赛 ID</th><th scope="col">标题</th><th scope="col">发布状态</th><th scope="col">操作</th></tr></thead>
            <tbody><?php foreach ($models as $model): ?>
                <tr>
                    <td><?= Html::encode((string) $model->id) ?></td>
                    <td><?= Html::encode((string) $model->match_id) ?></td>
                    <td class="text-break"><?= Html::encode($model->title) ?></td>
                    <td><span class="badge <?= $model->isPublished() ? 'text-bg-success' : 'text-bg-secondary' ?>"><?= $model->isPublished() ? '已发布' : '未发布' ?></span></td>
                    <td><div class="d-flex gap-2 text-nowrap">
                        <?= Html::a('详情', ['/admin-story/view', 'id' => $model->id]) ?>
                        <?= Html::a('修改', ['/admin-story/update', 'id' => $model->id]) ?>
                    </div></td>
                </tr>
            <?php endforeach ?></tbody>
        </table></div>
    <?php endif ?>
    <?= LinkPager::widget(['pagination' => $dataProvider->getPagination(), 'options' => ['class' => 'd-flex justify-content-center']]) ?>
</div>
