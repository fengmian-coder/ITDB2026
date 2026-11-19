<?php
/** WorldCup Journey | D | Submission form and published messages. */

use yii\helpers\Html;
use yii\widgets\LinkPager;
use yii\web\View;

$this->title = '球迷留言';
$this->registerCssFile('@web/css/guestbook.css', ['depends' => [app\assets\AppAsset::class]]);
$this->registerJsFile('@web/js/guestbook-match.js', ['position' => View::POS_END, 'depends' => [app\assets\AppAsset::class]]);
$selectedMatch = is_scalar($model->related_match_id) ? ($matchOptions[$model->related_match_id] ?? '') : '';
?>
<div class="guestbook-page">
<h1><?= Html::encode($this->title) ?></h1>
<?= Html::beginForm(['create'], 'post', ['id' => 'guestbook-form']) ?>
<?= Html::errorSummary($model) ?>
<div class="guestbook-field">
    <?= Html::label('昵称', 'nickname', ['class' => 'guestbook-label']) ?>
    <?= Html::textInput('nickname', $model->nickname, ['id' => 'nickname', 'required' => true, 'maxlength' => 50, 'class' => 'form-control']) ?>
</div>
<div class="guestbook-field">
    <?= Html::label('留言', 'content', ['class' => 'guestbook-label']) ?>
    <?= Html::textarea('content', $model->content, ['id' => 'content', 'required' => true, 'maxlength' => 2000, 'rows' => 4, 'class' => 'form-control']) ?>
</div>
<div class="guestbook-field">
    <div class="guestbook-match-heading">
        <?= Html::label('关联比赛（可选）', 'related-match-id', ['class' => 'guestbook-label']) ?>
        <span id="guestbook-timezone" class="guestbook-note">北京时间</span>
    </div>
    <div class="guestbook-match-fields">
        <div id="guestbook-date-filter" class="d-none">
            <?= Html::label('按日期筛选比赛', 'guestbook-match-date', ['class' => 'visually-hidden']) ?>
            <div class="d-flex gap-2">
                <input type="date" id="guestbook-match-date" class="form-control" title="按日期筛选比赛" aria-describedby="guestbook-timezone">
                <button type="button" id="guestbook-all-dates" class="btn btn-outline-secondary text-nowrap" aria-label="清除日期筛选" hidden>清除</button>
            </div>
        </div>
        <div class="guestbook-match-select">
            <?= Html::dropDownList('related_match_id', is_scalar($model->related_match_id) ? $model->related_match_id : '', $matchOptions,
                ['id' => 'related-match-id', 'class' => 'form-select', 'prompt' => '不关联比赛',
                    'options' => $matchOptionAttributes, 'title' => $selectedMatch,
                    'aria-describedby' => 'guestbook-match-summary guestbook-timezone']) ?>
        </div>
    </div>
    <p id="guestbook-match-summary" class="visually-hidden" role="status"><?= Html::encode($selectedMatch !== '' ? '已关联：' . $selectedMatch . '（北京时间）' : '未关联比赛') ?></p>
</div>
<div class="guestbook-submit-row">
    <?= Html::submitButton('提交留言', ['class' => 'btn btn-primary']) ?>
    <p class="guestbook-note">审核通过后公开</p>
</div>
<?= Html::endForm() ?>

<section class="guestbook-messages" aria-labelledby="guestbook-messages-heading">
<h2 id="guestbook-messages-heading">公开留言</h2>
<?php $messages = $provider->getModels(); ?>
<?php if (!$messages): ?><p class="guestbook-note">暂无留言</p><?php endif; ?>
<?php foreach ($messages as $message): ?>
    <article class="border rounded p-3 mb-3">
        <h3 class="h5"><?= Html::encode($message->nickname) ?></h3>
        <p><?= nl2br(Html::encode($message->content)) ?></p>
        <?php if ($message->related_match_id): ?>
            <p><?= Html::a(Html::encode($matchOptions[$message->related_match_id] ?? '查看关联比赛'), ['/wc-match/view', 'id' => $message->related_match_id]) ?></p>
        <?php endif; ?>
        <?php if ($message->reply): ?>
            <p><strong>管理员回复</strong><br><?= nl2br(Html::encode($message->reply->content)) ?></p>
        <?php endif; ?>
    </article>
<?php endforeach; ?>
<?= LinkPager::widget(['pagination' => $provider->pagination]) ?>
</section>
</div>
