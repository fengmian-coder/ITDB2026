<?php
/**
 * WorldCup Journey - 后台赛事搜索与列表
 * 模块：B；负责人：B；依赖：TournamentSearch、ActiveDataProvider、Bootstrap LinkPager
 * 最近修改：2026-11-05
 */
declare(strict_types=1);
use yii\bootstrap5\LinkPager;
use yii\helpers\Html;
/** @var yii\web\View $this */
/** @var app\models\TournamentSearch $searchModel */
/** @var yii\data\ActiveDataProvider $dataProvider */
$this->title = '赛事管理';
$this->params['breadcrumbs'][] = $this->title;
$models = $dataProvider->getModels();
?>
<div class="admin-tournament-index pb-4">
    <header class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
        <div><h1 class="h3 fw-bold mb-1">赛事管理</h1><p class="text-body-secondary mb-0">维护赛事届次、名称、日期与简介。</p></div>
        <?= Html::a('新增赛事', ['/admin-tournament/create'], ['class' => 'btn btn-primary']) ?>
    </header>
    <div class="card border-0 shadow-sm rounded-4 mb-4">
        <div class="card-body p-4">
            <?= Html::beginForm(['/admin-tournament/index'], 'get', ['class' => 'row g-3 align-items-end']) ?>
                <div class="col-12 col-md-3">
                    <?= Html::activeLabel($searchModel, 'year', ['class' => 'form-label']) ?>
                    <?= Html::activeInput('number', $searchModel, 'year', ['class' => 'form-control', 'min' => 0, 'max' => 65535, 'step' => 1]) ?>
                </div>
                <div class="col-12 col-md-4">
                    <?= Html::activeLabel($searchModel, 'name', ['class' => 'form-label']) ?>
                    <?= Html::activeTextInput($searchModel, 'name', ['class' => 'form-control', 'maxlength' => 120]) ?>
                </div>
                <div class="col-12 col-md-3">
                    <?= Html::label('排序', 'tournament-sort', ['class' => 'form-label']) ?>
                    <?= Html::dropDownList('sort', Yii::$app->request->get('sort', '-year'), [
                        '-year' => '年份从新到旧', 'year' => '年份从旧到新', 'name' => '名称升序', '-name' => '名称降序',
                        'id' => 'ID 升序', '-id' => 'ID 降序',
                    ], ['id' => 'tournament-sort', 'class' => 'form-select']) ?>
                </div>
                <div class="col-12 col-md-2 d-flex gap-2">
                    <?= Html::submitButton('搜索', ['class' => 'btn btn-primary']) ?>
                    <?= Html::a('重置', ['/admin-tournament/index'], ['class' => 'btn btn-outline-secondary']) ?>
                </div>
            <?= Html::endForm() ?>
        </div>
    </div>
    <p class="small text-body-secondary">共 <?= Html::encode((string) $dataProvider->getTotalCount()) ?> 条赛事记录</p>
    <?php if ($models === []): ?>
        <div class="rounded-4 border p-5 text-center text-body-secondary">暂无符合条件的赛事。</div>
    <?php else: ?>
        <div class="table-responsive border rounded-4 mb-3">
            <table class="table align-middle mb-0">
                <caption class="visually-hidden">赛事管理列表</caption>
                <thead class="table-light"><tr><th scope="col">年份</th><th scope="col">赛事名称</th><th scope="col">开始日期</th><th scope="col">结束日期</th><th scope="col">操作</th></tr></thead>
                <tbody>
                    <?php foreach ($models as $model): ?>
                        <tr>
                            <td><?= Html::encode((string) $model->year) ?></td>
                            <td class="text-break"><?= Html::encode($model->name) ?></td>
                            <td class="text-nowrap"><?= Html::encode($model->start_date) ?></td>
                            <td class="text-nowrap"><?= Html::encode($model->end_date) ?></td>
                            <td><div class="d-flex gap-2 text-nowrap">
                                <?= Html::a('详情', ['/admin-tournament/view', 'id' => $model->id]) ?>
                                <?= Html::a('修改', ['/admin-tournament/update', 'id' => $model->id]) ?>
                            </div></td>
                        </tr>
                    <?php endforeach ?>
                </tbody>
            </table>
        </div>
    <?php endif ?>
    <?= LinkPager::widget(['pagination' => $dataProvider->getPagination(), 'options' => ['class' => 'd-flex justify-content-center']]) ?>
</div>
