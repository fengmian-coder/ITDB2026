<?php
/** WorldCup Journey | D | Review and reply to guestbook messages. */

use app\models\GuestbookMessage;
use yii\helpers\Html;
use yii\widgets\LinkPager;

$this->title = '留言审核';
$labels = [
    GuestbookMessage::PENDING => '待审核',
    GuestbookMessage::APPROVED => '已通过',
    GuestbookMessage::REJECTED => '已拒绝',
];
?>
<h1><?= Html::encode($this->title) ?></h1>
<p><?= Html::a('返回后台', ['/admin-dashboard/index']) ?></p>
<?php $messages = $provider->getModels(); ?>
<?php if (!$messages): ?><p>暂无留言。</p><?php endif; ?>
<?php foreach ($messages as $message): ?>
    <article class="border rounded p-3 mb-3">
        <h2 class="h5"><?= Html::encode($message->nickname) ?> · <?= Html::encode($labels[$message->status]) ?></h2>
        <p><?= nl2br(Html::encode($message->content)) ?></p>
        <?= Html::beginForm(['moderate', 'id' => $message->id], 'post', ['class' => 'mb-2']) ?>
        <?= Html::submitButton('通过', ['name' => 'status', 'value' => GuestbookMessage::APPROVED, 'class' => 'btn btn-success']) ?>
        <?= Html::submitButton('拒绝', ['name' => 'status', 'value' => GuestbookMessage::REJECTED, 'class' => 'btn btn-outline-danger']) ?>
        <?= Html::endForm() ?>

        <?php if ($message->reply): ?>
            <p><strong>管理员回复</strong><br><?= nl2br(Html::encode($message->reply->content)) ?></p>
        <?php elseif ($message->status === GuestbookMessage::APPROVED): ?>
            <?= Html::beginForm(['reply', 'id' => $message->id], 'post') ?>
            <?= Html::label('管理员回复', 'reply-' . $message->id) ?>
            <?= Html::textarea('content', '', ['id' => 'reply-' . $message->id, 'required' => true, 'maxlength' => 2000, 'class' => 'form-control mb-2']) ?>
            <?= Html::submitButton('发布回复', ['class' => 'btn btn-primary']) ?>
            <?= Html::endForm() ?>
        <?php endif; ?>
    </article>
<?php endforeach; ?>
<?= LinkPager::widget(['pagination' => $provider->pagination]) ?>
