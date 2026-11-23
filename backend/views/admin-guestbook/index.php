<?php
/**
 * WorldCup Journey - 留言审核、状态与管理员回复界面。
 * 模块：D 互动 / A 视觉统一；负责人：D、A。
 * 依赖：GuestbookMessage、D 的 POST 审核与回复路由；最近修改：2026-11-10。
 */
declare(strict_types=1);
use app\models\GuestbookMessage;
use yii\helpers\Html;
use yii\bootstrap5\LinkPager;
$this->title = '留言审核';
$this->params['breadcrumbs'][] = $this->title;
$labels = [GuestbookMessage::PENDING => '待审核', GuestbookMessage::APPROVED => '已通过', GuestbookMessage::REJECTED => '已拒绝'];
$statusClasses = [GuestbookMessage::PENDING => 'pending', GuestbookMessage::APPROVED => 'approved', GuestbookMessage::REJECTED => 'rejected'];
$messages = $provider->getModels();
?>
<div class="admin-guestbook">
    <header class="admin-page-heading"><div><h1>留言审核</h1><p>审核球迷的观赛感想，通过后可添加管理员回复</p></div><?= Html::a('查看公开留言', ['/guestbook/index'], ['class' => 'btn btn-outline-primary']) ?></header>
    <div class="admin-list-summary"><span>共 <?= (int) $provider->getTotalCount() ?> 条留言</span><span>仅已通过的留言和回复会在前台公开</span></div>
    <?php if ($messages === []): ?><div class="admin-empty"><span class="admin-empty-mark" aria-hidden="true">—</span><h2>暂无球迷留言</h2><p>访客提交留言后，可在这里完成审核与回复</p></div><?php endif ?>
    <?php foreach ($messages as $message): ?>
        <article class="admin-message-card">
            <header class="admin-message-header"><div><h2><?= Html::encode($message->nickname) ?></h2><p>提交时间（UTC）：<?= Html::encode($message->created_at) ?><?php if ($message->related_match_id): ?> · <?= Html::a('查看关联比赛', ['/wc-match/view', 'id' => $message->related_match_id]) ?><?php endif ?></p></div><span class="admin-status <?= Html::encode($statusClasses[$message->status]) ?>"><?= Html::encode($labels[$message->status]) ?></span></header>
            <p class="admin-message-content"><?= nl2br(Html::encode($message->content)) ?></p>
            <div class="admin-moderation-actions">
                <?= Html::beginForm(['moderate', 'id' => $message->id], 'post', ['class' => 'd-flex flex-wrap gap-2']) ?>
                <?= Html::submitButton('通过审核', ['name' => 'status', 'value' => GuestbookMessage::APPROVED, 'class' => 'btn btn-success']) ?>
                <?= Html::submitButton('拒绝显示', ['name' => 'status', 'value' => GuestbookMessage::REJECTED, 'class' => 'btn btn-outline-danger']) ?>
                <?= Html::endForm() ?>
            </div>
            <?php if ($message->reply): ?>
                <section class="admin-message-reply"><h3>管理员回复</h3><p><?= nl2br(Html::encode($message->reply->content)) ?></p></section>
            <?php elseif ($message->status === GuestbookMessage::APPROVED): ?>
                <div class="admin-message-reply"><?= Html::beginForm(['reply', 'id' => $message->id], 'post') ?>
                <?= Html::label('回复这条留言', 'reply-' . $message->id, ['class' => 'form-label']) ?>
                <?= Html::textarea('content', '', ['id' => 'reply-' . $message->id, 'required' => true, 'maxlength' => 2000, 'rows' => 3, 'placeholder' => '填写管理员回复，发布后将在前台显示', 'class' => 'form-control mb-3']) ?>
                <?= Html::submitButton('发布回复', ['class' => 'btn btn-primary']) ?><?= Html::endForm() ?></div>
            <?php else: ?>
                <p class="admin-message-note">留言通过审核后，可以添加回复</p>
            <?php endif ?>
        </article>
    <?php endforeach ?>
    <?= LinkPager::widget(['pagination' => $provider->pagination]) ?>
</div>
