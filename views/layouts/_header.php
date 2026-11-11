<?php

/**
 * WorldCup Journey - 公共导航，接入团队介绍并保留 D 的登录与留言入口。
 * 模块：A 公共界面；负责人：A；依赖：Bootstrap Nav、D 的管理员会话。
 * 最近修改：2026-11-10。
 */

declare(strict_types=1);

/** @var yii\web\View $this */

use yii\bootstrap5\Nav;
use yii\bootstrap5\NavBar;
use yii\helpers\Html;

$items = [
    [
        'label' => '首页',
        'url' => ['/site/index'],
    ],
    [
        'label' => '探索球队',
        'url' => ['/site/explore'],
    ],
    [
        'label' => '探索比赛',
        'url' => ['/wc-match/index'],
    ],
    [
        'label' => '纪念卡片',
        'url' => ['/memory/card'],
    ],
    [
        'label' => '球迷留言',
        'url' => ['/guestbook/index'],
    ],
    [
        'label' => '关于我们',
        'url' => ['/project-team/index'],
    ],
    [
        'label' => '作业下载',
        'url' => ['/document/index'],
    ],
    [
        'label' => '后台管理',
        'url' => ['/admin-dashboard/index'],
        'visible' => !Yii::$app->user->isGuest,
    ],
    [
        'label' => '管理员登录',
        'url' => ['/auth/login'],
        'visible' => Yii::$app->user->isGuest,
    ],
    [
        'label' => '退出',
        'url' => ['/auth/logout'],
        'linkOptions' => [
            'data-method' => 'post',
            'class' => 'nav-link logout',
            'title' => '退出当前管理员账号',
        ],
        'visible' => !Yii::$app->user->isGuest,
    ],
];

?>
<header id="header">
    <?php NavBar::begin(
        [
            'brandLabel' => Yii::$app->name,
            'brandUrl' => Yii::$app->homeUrl,
            'options' => ['class' => 'navbar-expand-xl navbar-dark wc-navbar fixed-top']
        ],
    ) ?>
    <?= Nav::widget(
        [
            'options' => ['class' => 'navbar-nav me-auto'],
            'encodeLabels' => false,
            'items' => $items,
        ],
    ) ?>
    <?= Html::button(
        '夜间模式',
        [
            'id' => 'theme-toggle',
            'class' => 'btn nav-link wc-theme-toggle',
            'aria-label' => '切换到夜间模式',
        ],
    ) ?>
    <?php NavBar::end() ?>
</header>
