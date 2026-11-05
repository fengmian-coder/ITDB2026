<?php
/**
 * WorldCup Journey - 精选比赛事件编辑与删除
 * 模块：B；负责人：B；依赖：MatchStory、MatchEvent、Html、现有 Bootstrap
 * 最近修改：2026-11-05
 */
declare(strict_types=1);
use yii\helpers\Html;
/** @var yii\web\View $this */
/** @var app\models\MatchStory $story */
/** @var app\models\MatchEvent $event */
$this->title = $event->isNewRecord ? '新增比赛事件' : '编辑比赛事件';
$this->params['breadcrumbs'][] = ['label' => '精选故事管理', 'url' => ['/admin-story/index']];
$this->params['breadcrumbs'][] = ['label' => '事件时间轴', 'url' => ['/admin-story/events', 'id' => $story->id]];
$this->params['breadcrumbs'][] = $this->title;
$action = $event->isNewRecord ? ['/admin-story/event-create', 'id' => $story->id]
    : ['/admin-story/event-update', 'id' => $story->id, 'event_id' => $event->id];
?>
<div class="admin-story-event-form pb-4">
    <h1 class="h3 fw-bold mb-3"><?= Html::encode($this->title) ?></h1>
    <p class="text-body-secondary"><?= Html::encode($story->title) ?> · 比赛 ID <?= Html::encode((string) $story->match_id) ?>（不可在此更改归属）</p>
    <div class="card border-0 shadow-sm rounded-4 mb-4"><div class="card-body p-4">
        <?= Html::beginForm($action, 'post') ?>
        <?= Html::errorSummary($event, ['class' => 'alert alert-danger']) ?>
        <div class="row g-3">
        <?php foreach (['team_id' => [null, false], 'player_name' => [100, false], 'minute' => [null, false],
            'added_minute' => [null, false], 'period_code' => [12, true], 'event_type' => [20, true],
            'sort_order' => [null, true]] as $attribute => [$maxLength, $required]): ?>
            <div class="col-12 col-md-6">
                <?= Html::activeLabel($event, $attribute, ['class' => 'form-label']) ?>
                <?= Html::activeTextInput($event, $attribute, ['class' => 'form-control', 'maxlength' => $maxLength, 'required' => $required]) ?>
            </div>
        <?php endforeach ?>
            <div class="col-12"><?= Html::activeLabel($event, 'description', ['class' => 'form-label']) ?>
                <?= Html::activeTextarea($event, 'description', ['class' => 'form-control', 'rows' => 4, 'maxlength' => 500]) ?></div>
        </div>
        <p class="small text-body-secondary mt-3">分钟与补时分开填写，例如 45+2 填为 45 和 2；没有值时留空，不用虚构球员或球队。时段建议 H1/H2/ET1/ET2/SHOOTOUT；类型建议 GOAL/OWN_GOAL/YELLOW/RED/PENALTY_GOAL/PENALTY_MISS/OTHER。建议代码尚不作为冻结枚举，正式规则待 B/C 对齐。</p>
        <?php if ($story->isPublished()): ?>
            <div class="form-check mb-3">
                <?= Html::checkbox('confirm_publish', false, ['id' => 'event-confirm-publish', 'value' => '1', 'required' => true, 'class' => 'form-check-input']) ?>
                <?= Html::label('已核对事件事实与素材许可，确认本次修改对已发布时间轴的影响。', 'event-confirm-publish', ['class' => 'form-check-label']) ?>
            </div>
        <?php endif ?>
        <div class="d-flex gap-2">
            <?= Html::submitButton('保存事件', ['class' => 'btn btn-primary']) ?>
            <?= Html::a('返回时间轴', ['/admin-story/events', 'id' => $story->id], ['class' => 'btn btn-outline-secondary']) ?>
        </div>
        <?= Html::endForm() ?>
    </div></div>
    <?php if (!$event->isNewRecord): ?>
        <section class="card border-danger-subtle"><div class="card-body p-4">
            <h2 class="h5">删除此事件</h2><p>只删除本条时间轴事件，不修改比赛比分。</p>
            <?= Html::beginForm(['/admin-story/event-delete', 'id' => $story->id, 'event_id' => $event->id], 'post') ?>
            <div class="form-check mb-2">
                <?= Html::checkbox('confirm_delete', false, ['id' => 'event-confirm-delete', 'value' => '1', 'required' => true, 'class' => 'form-check-input']) ?>
                <?= Html::label('确认删除该事件，此操作不可撤销。', 'event-confirm-delete', ['class' => 'form-check-label']) ?>
            </div>
            <?php if ($story->isPublished()): ?>
                <div class="form-check mb-3">
                    <?= Html::checkbox('confirm_publish', false, ['id' => 'event-delete-publish', 'value' => '1', 'required' => true, 'class' => 'form-check-input']) ?>
                    <?= Html::label('确认删除对已发布故事时间轴的影响。', 'event-delete-publish', ['class' => 'form-check-label']) ?>
                </div>
            <?php endif ?>
            <?= Html::submitButton('删除事件', ['class' => 'btn btn-outline-danger']) ?>
            <?= Html::endForm() ?>
        </div></section>
    <?php endif ?>
</div>
