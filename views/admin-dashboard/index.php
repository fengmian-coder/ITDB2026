<?php
/** WorldCup Journey | D | Links to integrated administration screens. */

use yii\helpers\Html;

$this->title = '后台管理';
$sections = [
    '赛事届次' => 'admin-tournament',
    '比赛' => 'admin-match',
    '场馆' => 'admin-stadium',
    '故事、事件与标签' => 'admin-story',
    '留言审核与回复' => 'admin-guestbook',
];
?>
<h1><?= Html::encode($this->title) ?></h1>
<ul>
    <?php foreach ($sections as $label => $controller): ?>
        <li><?= Html::a(Html::encode($label), ["/$controller/index"]) ?></li>
    <?php endforeach; ?>
</ul>
<?= Html::beginForm(['/auth/logout'], 'post') ?>
<?= Html::submitButton('退出登录', ['class' => 'btn btn-secondary']) ?>
<?= Html::endForm() ?>
