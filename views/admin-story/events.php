<?php
/**
 * WorldCup Journey - 精选比赛事件管理列表
 * 模块：B；负责人：B；依赖：MatchStory、MatchEvent、ActiveDataProvider、Bootstrap LinkPager
 * 最近修改：2026-11-05
 */
declare(strict_types=1);
use yii\helpers\Html;
use yii\bootstrap5\LinkPager;
/** @var yii\web\View $this */
/** @var app\models\MatchStory $story */
/** @var yii\data\ActiveDataProvider $dataProvider */
$this->title = '比赛事件时间轴';
$this->params['breadcrumbs'][] = ['label' => '精选故事管理', 'url' => ['/admin-story/index']];
$this->params['breadcrumbs'][] = ['label' => $story->title, 'url' => ['/admin-story/view', 'id' => $story->id]];
$this->params['breadcrumbs'][] = $this->title;
$events = $dataProvider->getModels();
?>
<div class="admin-story-events pb-4">
    <header class="d-flex flex-wrap justify-content-between gap-3 mb-4">
        <div><h1 class="h3 fw-bold"><?= Html::encode($this->title) ?></h1>
            <p class="text-body-secondary mb-0"><?= Html::encode($story->title) ?> · 比赛 ID <?= Html::encode((string) $story->match_id) ?></p></div>
        <div class="d-flex gap-2 align-items-start">
            <?= Html::a('新增事件', ['/admin-story/event-create', 'id' => $story->id], ['class' => 'btn btn-primary']) ?>
            <?= Html::a('返回故事', ['/admin-story/view', 'id' => $story->id], ['class' => 'btn btn-outline-secondary']) ?>
        </div>
    </header>
    <p class="alert alert-info">按排序值升序、事件 ID 升序展示；排序值可在编辑页修改。事件不参与进球统计。<?= $story->isPublished() ? '此故事已发布，事件修改会影响前台时间轴。' : '此故事尚未发布，前台不展示事件。' ?></p>
    <div class="card border-0 shadow-sm rounded-4"><div class="table-responsive">
        <table class="table align-middle mb-0">
            <thead><tr><th>排序 / ID</th><th>时段 / 时间</th><th>类型 / 球队</th><th>球员 / 描述</th><th>操作</th></tr></thead>
            <tbody>
            <?php foreach ($events as $event): ?>
                <tr>
                    <td><?= Html::encode((string) $event->sort_order) ?> / <?= Html::encode((string) $event->id) ?></td>
                    <td><?= Html::encode($event->period_code) ?><br><?= Html::encode($event->minute === null ? '—' : (string) $event->minute . ($event->added_minute === null ? '' : '+' . $event->added_minute)) ?></td>
                    <td><?= Html::encode($event->event_type) ?><br>球队 ID：<?= Html::encode($event->team_id === null ? '—' : (string) $event->team_id) ?></td>
                    <td class="text-break"><?= Html::encode($event->player_name ?? '—') ?><br><?= Html::encode($event->description ?? '—') ?></td>
                    <td><?= Html::a('编辑 / 删除', ['/admin-story/event-update', 'id' => $story->id, 'event_id' => $event->id], ['class' => 'btn btn-sm btn-outline-primary text-nowrap']) ?></td>
                </tr>
            <?php endforeach ?>
            <?php if (!$events): ?><tr><td colspan="5" class="text-center text-body-secondary py-5">尚未录入比赛事件。</td></tr><?php endif ?>
            </tbody>
        </table>
    </div></div>
    <div class="mt-4"><?= LinkPager::widget(['pagination' => $dataProvider->getPagination()]) ?></div>
</div>
