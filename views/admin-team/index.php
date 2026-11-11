<?php
/**
 * WorldCup Journey - 球队后台列表
 * 模块：C；负责人：C，B 协助实现；依赖：NationalTeamSearch、Bootstrap LinkPager
 * 最近修改：2026-11-11
 */
declare(strict_types=1);
use yii\helpers\Html;
use yii\bootstrap5\LinkPager;
$this->title = '球队管理';
$this->params['breadcrumbs'][] = $this->title;
?>
<header class="admin-page-heading"><h1>球队管理</h1><?= Html::a('新增球队', ['create'], ['class' => 'btn btn-primary']) ?></header>
<?= Html::beginForm(['index'], 'get', ['class' => 'row g-3 mb-4']) ?>
<?php foreach (['name_zh', 'name_en'] as $attribute): ?>
<div class="col-md-4"><?= Html::activeLabel($searchModel, $attribute, ['class' => 'form-label']) ?><?= Html::activeTextInput($searchModel, $attribute, ['class' => 'form-control']) ?></div>
<?php endforeach; ?>
<div class="col-md-2"><?= Html::activeLabel($searchModel, 'is_active', ['class' => 'form-label']) ?><?= Html::activeDropDownList($searchModel, 'is_active', ['1' => '启用', '0' => '停用'], ['class' => 'form-select', 'prompt' => '全部']) ?></div>
<div class="col-md-2 align-self-end"><?= Html::submitButton('搜索', ['class' => 'btn btn-primary']) ?></div>
<?= Html::endForm() ?>
<p>共 <?= (int) $dataProvider->getTotalCount() ?> 支球队</p>
<div class="table-responsive"><table class="table align-middle">
<thead><tr><th>FIFA 代码</th><th>中文名</th><th>英文名</th><th>状态</th><th>操作</th></tr></thead>
<tbody><?php foreach ($dataProvider->getModels() as $model): ?>
<tr><td><?= Html::encode($model->fifa_code) ?></td><td><?= Html::encode($model->name_zh) ?></td><td><?= Html::encode($model->name_en) ?></td><td><?= (int) $model->is_active === 1 ? '启用' : '停用' ?></td><td><?= Html::a('详情及参赛信息', ['view', 'id' => $model->id]) ?> <?= Html::a('修改', ['update', 'id' => $model->id]) ?></td></tr>
<?php endforeach; ?>
<?php if ($dataProvider->getTotalCount() === 0): ?><tr><td colspan="5">暂无符合条件的球队。</td></tr><?php endif; ?></tbody>
</table></div>
<?= LinkPager::widget(['pagination' => $dataProvider->getPagination()]) ?>
