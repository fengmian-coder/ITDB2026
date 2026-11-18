<?php
/**
 * WorldCup Journey - 后台比赛列表
 * 模块：B；负责人：B；依赖：WcMatchSearch、ActiveDataProvider、Bootstrap LinkPager
 * 最近修改：2026-11-05
 */
declare(strict_types=1);
use app\models\WcMatch;
use yii\helpers\Html;
use yii\bootstrap5\LinkPager;
/** @var yii\web\View $this */
/** @var app\models\WcMatchSearch $searchModel */
/** @var yii\data\ActiveDataProvider $dataProvider */
$this->title = '比赛管理';
$this->params['breadcrumbs'][] = $this->title;
$models = $dataProvider->getModels();
?>
<div class="admin-match-index pb-4">
    <header class="d-flex flex-wrap justify-content-between gap-3 mb-4">
        <div><h1 class="h3 fw-bold mb-1">比赛管理</h1><p class="text-body-secondary mb-0">2026 世界杯</p></div>
        <?= Html::a('新增比赛', ['/admin-match/create'], ['class' => 'btn btn-primary']) ?></header>
    <div class="card border-0 shadow-sm rounded-4 mb-4"><div class="card-body p-4">
        <?= Html::beginForm(['/admin-match/index'], 'get', ['class' => 'row g-3 align-items-end']) ?>
        <div class="col-12 col-md-3"><?= Html::activeLabel($searchModel, 'team_id', ['class' => 'form-label']) ?>
            <?= Html::activeTextInput($searchModel, 'team_id', ['class' => 'form-control']) ?></div>
        <?php foreach (['stage_code' => WcMatch::STAGE_CODES, 'status' => WcMatch::STATUS_CODES] as $attribute => $codes): ?>
            <div class="col-12 col-md-3"><?= Html::activeLabel($searchModel, $attribute, ['class' => 'form-label']) ?>
                <?= Html::activeDropDownList($searchModel, $attribute, array_combine($codes, $codes), ['class' => 'form-select', 'prompt' => '全部']) ?></div>
        <?php endforeach ?>
        <div class="col-12 d-flex gap-2"><?= Html::submitButton('筛选比赛', ['class' => 'btn btn-primary']) ?>
            <?= Html::a('重置', ['/admin-match/index'], ['class' => 'btn btn-outline-secondary']) ?></div>
        <?= Html::endForm() ?>
    </div></div>
    <div class="card border-0 shadow-sm rounded-4 table-responsive"><table class="table align-middle mb-0">
        <thead><tr><th>比赛编号</th><th>开赛时间（UTC）</th><th>主队 / 客队 ID</th><th>阶段 / 状态</th><th>90 分钟比分</th><th>操作</th></tr></thead>
        <tbody><?php foreach ($models as $model): ?>
            <tr><td><?= Html::encode((string) $model->match_no) ?></td>
                <td><?= Html::encode($model->kickoff_utc) ?></td><td><?= Html::encode((string) $model->home_team_id) ?> / <?= Html::encode((string) $model->away_team_id) ?></td>
                <td><?= Html::encode($model->stage_code) ?><br><?= Html::encode($model->status) ?></td>
                <td><?= Html::encode($model->home_score_90 === null || $model->away_score_90 === null ? '—' : $model->home_score_90 . ' : ' . $model->away_score_90) ?></td>
                <td><?= Html::a('详情 / 编辑', ['/admin-match/view', 'id' => $model->id], ['class' => 'btn btn-sm btn-outline-primary text-nowrap']) ?></td></tr>
        <?php endforeach ?>
        <?php if (!$models): ?><tr><td colspan="6" class="text-center text-body-secondary py-5">暂无符合条件的比赛</td></tr><?php endif ?>
        </tbody></table></div>
    <div class="mt-4"><?= LinkPager::widget(['pagination' => $dataProvider->getPagination()]) ?></div>
</div>
