<?php
/**
 * WorldCup Journey - 比赛赛程与比分编辑表单
 * 模块：B；负责人：B；依赖：WcMatch、Html、现有 Bootstrap
 * 最近修改：2026-11-05
 */
declare(strict_types=1);
use app\models\WcMatch;
use yii\helpers\Html;
/** @var yii\web\View $this */
/** @var WcMatch $model */
$action = $model->isNewRecord ? ['/admin-match/create'] : ['/admin-match/update', 'id' => $model->id];
?>
<div class="card border-0 shadow-sm rounded-4"><div class="card-body p-4">
    <?= Html::beginForm($action, 'post') ?>
    <?= Html::errorSummary($model, ['class' => 'alert alert-danger']) ?>
    <div class="row g-3">
        <?php foreach (['tournament_id', 'match_no', 'home_team_id', 'away_team_id', 'stadium_id', 'kickoff_utc'] as $attribute): ?>
            <div class="col-12 col-md-6 col-xl-4"><?= Html::activeLabel($model, $attribute, ['class' => 'form-label']) ?>
                <?= Html::activeTextInput($model, $attribute, ['class' => 'form-control', 'required' => true, 'placeholder' => $attribute === 'kickoff_utc' ? 'YYYY-MM-DD HH:MM:SS（UTC）' : null]) ?></div>
        <?php endforeach ?>
        <?php foreach (['stage_code' => WcMatch::STAGE_CODES, 'status' => WcMatch::STATUS_CODES] as $attribute => $codes): ?>
            <div class="col-12 col-md-4"><?= Html::activeLabel($model, $attribute, ['class' => 'form-label']) ?>
                <?= Html::activeDropDownList($model, $attribute, array_combine($codes, $codes), ['class' => 'form-select', 'prompt' => '请选择', 'required' => true]) ?></div>
        <?php endforeach ?>
        <div class="col-12 col-md-4"><?= Html::activeLabel($model, 'group_code', ['class' => 'form-label']) ?>
            <?= Html::activeDropDownList($model, 'group_code', array_combine(range('A', 'L'), range('A', 'L')), ['class' => 'form-select', 'prompt' => '不填写']) ?></div>
    </div>
    <p class="small text-body-secondary mt-3">球队与场馆填写正式数据库 ID，不是 FIFA 代码或比赛编号；球队名称与参赛资格待 C 联调。开赛时间统一填写 UTC。非小组赛不填小组代码</p>
    <h2 class="h5 mt-4">比分与赛果</h2>
    <p class="small text-body-secondary">90 分钟、120 分钟和点球各自成对填写；120 分钟为累计比分，不与 90 分钟相加，点球不计入总进球。未开始、延期或取消比赛不填赛果；已完赛须填 90 分钟比分。小组赛不填获胜方，已结束淘汰赛填写本场获胜球队 ID</p>
    <div class="row g-3">
        <?php foreach (['home_score_90', 'away_score_90', 'home_score_120', 'away_score_120', 'home_penalties', 'away_penalties', 'winner_team_id'] as $attribute): ?>
            <div class="col-12 col-md-6 col-xl-4"><?= Html::activeLabel($model, $attribute, ['class' => 'form-label']) ?>
                <?= Html::activeTextInput($model, $attribute, ['class' => 'form-control', 'inputmode' => 'numeric']) ?></div>
        <?php endforeach ?>
        <div class="col-12"><?= Html::activeLabel($model, 'summary', ['class' => 'form-label']) ?>
            <?= Html::activeTextarea($model, 'summary', ['class' => 'form-control', 'maxlength' => 300, 'rows' => 4]) ?></div>
    </div>
    <div class="d-flex gap-2 mt-4">
        <?= Html::submitButton('保存比赛', ['class' => 'btn btn-primary']) ?>
        <?= Html::a('取消', $model->isNewRecord ? ['/admin-match/index'] : ['/admin-match/view', 'id' => $model->id], ['class' => 'btn btn-outline-secondary']) ?>
    </div>
    <?= Html::endForm() ?>
</div></div>
