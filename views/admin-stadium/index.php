<?php
/**
 * WorldCup Journey - 后台场馆搜索与列表
 * 模块：B；负责人：B；依赖：StadiumSearch、ActiveDataProvider、Bootstrap LinkPager
 * 最近修改：2026-11-05
 */
declare(strict_types=1);
use yii\bootstrap5\LinkPager;
use yii\helpers\Html;
/** @var yii\web\View $this */
/** @var app\models\StadiumSearch $searchModel */
/** @var yii\data\ActiveDataProvider $dataProvider */
$this->title = '场馆管理';
$this->params['breadcrumbs'][] = $this->title;
$models = $dataProvider->getModels();
$countryLabels = ['CA' => '加拿大（CA）', 'MX' => '墨西哥（MX）', 'US' => '美国（US）'];
?>
<div class="admin-stadium-index pb-4">
    <header class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
        <div><h1 class="h3 fw-bold mb-1">场馆管理</h1><p class="text-body-secondary mb-0">维护比赛场馆、城市、国家与时区</p></div>
        <?= Html::a('新增场馆', ['/admin-stadium/create'], ['class' => 'btn btn-primary']) ?>
    </header>
    <div class="card border-0 shadow-sm rounded-4 mb-4">
        <div class="card-body p-4">
            <?= Html::beginForm(['/admin-stadium/index'], 'get', ['class' => 'row g-3 align-items-end']) ?>
                <div class="col-12 col-md-3">
                    <?= Html::activeLabel($searchModel, 'name', ['class' => 'form-label']) ?>
                    <?= Html::activeTextInput($searchModel, 'name', ['class' => 'form-control', 'maxlength' => 120]) ?>
                </div>
                <div class="col-12 col-md-3">
                    <?= Html::activeLabel($searchModel, 'city', ['class' => 'form-label']) ?>
                    <?= Html::activeTextInput($searchModel, 'city', ['class' => 'form-control', 'maxlength' => 100]) ?>
                </div>
                <div class="col-12 col-md-2">
                    <?= Html::activeLabel($searchModel, 'country_code', ['class' => 'form-label']) ?>
                    <?= Html::activeDropDownList($searchModel, 'country_code', $countryLabels, ['class' => 'form-select', 'prompt' => '全部国家']) ?>
                </div>
                <div class="col-12 col-md-2">
                    <?= Html::label('排序', 'stadium-sort', ['class' => 'form-label']) ?>
                    <?= Html::dropDownList('sort', Yii::$app->request->get('sort', 'name'), [
                        'name' => '名称升序', '-name' => '名称降序', 'city' => '城市升序', '-city' => '城市降序',
                        'country_code' => '国家升序', '-country_code' => '国家降序', 'id' => 'ID 升序', '-id' => 'ID 降序',
                    ], ['id' => 'stadium-sort', 'class' => 'form-select']) ?>
                </div>
                <div class="col-12 col-md-2 d-flex gap-2">
                    <?= Html::submitButton('搜索', ['class' => 'btn btn-primary']) ?>
                    <?= Html::a('重置', ['/admin-stadium/index'], ['class' => 'btn btn-outline-secondary']) ?>
                </div>
            <?= Html::endForm() ?>
        </div>
    </div>
    <p class="small text-body-secondary">共 <?= Html::encode((string) $dataProvider->getTotalCount()) ?> 条场馆记录</p>
    <?php if ($models === []): ?>
        <div class="rounded-4 border p-5 text-center text-body-secondary">暂无符合条件的场馆</div>
    <?php else: ?>
        <div class="table-responsive border rounded-4 mb-3">
            <table class="table align-middle mb-0">
                <caption class="visually-hidden">场馆管理列表</caption>
                <thead class="table-light"><tr><th scope="col">场馆名称</th><th scope="col">城市</th><th scope="col">国家</th><th scope="col">时区</th><th scope="col">操作</th></tr></thead>
                <tbody>
                    <?php foreach ($models as $model): ?>
                        <tr>
                            <td class="text-break"><?= Html::encode($model->name) ?></td>
                            <td class="text-break"><?= Html::encode($model->city) ?></td>
                            <td><?= Html::encode($countryLabels[$model->country_code] ?? $model->country_code) ?></td>
                            <td class="text-break"><?= Html::encode($model->timezone_name === null || $model->timezone_name === '' ? '—' : $model->timezone_name) ?></td>
                            <td><div class="d-flex gap-2 text-nowrap">
                                <?= Html::a('详情', ['/admin-stadium/view', 'id' => $model->id]) ?>
                                <?= Html::a('修改', ['/admin-stadium/update', 'id' => $model->id]) ?>
                            </div></td>
                        </tr>
                    <?php endforeach ?>
                </tbody>
            </table>
        </div>
    <?php endif ?>
    <?= LinkPager::widget(['pagination' => $dataProvider->getPagination(), 'options' => ['class' => 'd-flex justify-content-center']]) ?>
</div>
