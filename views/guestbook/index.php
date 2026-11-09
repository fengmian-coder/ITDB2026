<?php
/** WorldCup Journey | D | Submission form and published messages. */

use yii\helpers\Html;
use yii\widgets\LinkPager;

$this->title = '球迷留言';
?>
<h1><?= Html::encode($this->title) ?></h1>
<p>留言审核通过后会在这里公开显示。</p>
<?= Html::beginForm(['create'], 'post', ['class' => 'mb-4']) ?>
<?= Html::errorSummary($model) ?>
<?= Html::label('昵称', 'nickname') ?>
<?= Html::textInput('nickname', $model->nickname, ['id' => 'nickname', 'required' => true, 'maxlength' => 50, 'class' => 'form-control mb-2']) ?>
<?= Html::label('留言内容', 'content') ?>
<?= Html::textarea('content', $model->content, ['id' => 'content', 'required' => true, 'maxlength' => 2000, 'class' => 'form-control mb-2']) ?>
<?= Html::label('关联比赛编号（可选）', 'related-match-id') ?>
<?= Html::input('number', 'related_match_id', is_scalar($model->related_match_id) ? $model->related_match_id : '', ['id' => 'related-match-id', 'min' => 1, 'class' => 'form-control mb-2']) ?>
<?= Html::submitButton('提交留言', ['class' => 'btn btn-primary']) ?>
<?= Html::endForm() ?>

<h2>已公开的留言</h2>
<?php $messages = $provider->getModels(); ?>
<?php if (!$messages): ?><p>暂无公开留言。</p><?php endif; ?>
<?php foreach ($messages as $message): ?>
    <article class="border rounded p-3 mb-3">
        <h3 class="h5"><?= Html::encode($message->nickname) ?></h3>
        <p><?= nl2br(Html::encode($message->content)) ?></p>
        <?php if ($message->related_match_id): ?>
            <p><?= Html::a('查看关联比赛', ['/wc-match/view', 'id' => $message->related_match_id]) ?></p>
        <?php endif; ?>
        <?php if ($message->reply): ?>
            <p><strong>管理员回复</strong><br><?= nl2br(Html::encode($message->reply->content)) ?></p>
        <?php endif; ?>
    </article>
<?php endforeach; ?>
<?= LinkPager::widget(['pagination' => $provider->pagination]) ?>
