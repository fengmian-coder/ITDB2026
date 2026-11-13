<?php

/**
 * WorldCup Journey - 公共导航，统一球队、比赛、数据总览的入口和模块高亮。
 * 模块：A 公共界面；负责人：A；依赖：Bootstrap Nav、D 的管理员会话。
 * 最近修改：2026-11-12，A 整合前台信息结构，保留既有业务路由。
 */

declare(strict_types=1);

/** @var yii\web\View $this */

use yii\bootstrap5\Nav;
use yii\bootstrap5\NavBar;
use yii\helpers\Html;

$controllerId = Yii::$app->controller->id;
$teamsActive = in_array($controllerId, ['national-team', 'journey'], true)
    || Yii::$app->controller->route === 'site/explore';
$items = [
    [
        'label' => '首页',
        'url' => ['/site/index'],
    ],
    [
        'label' => '球队',
        'active' => $teamsActive,
        'url' => ['/site/explore'],
    ],
    [
        'label' => '比赛',
        'active' => $controllerId === 'wc-match',
        'url' => ['/wc-match/index'],
    ],
    [
        'label' => '数据总览',
        'active' => $controllerId === 'insight',
        'url' => ['/insight/index'],
    ],
    [
        'label' => '纪念卡片',
        'url' => ['/memory/card'],
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
            'options' => ['class' => 'navbar-expand-xxl navbar-dark wc-navbar fixed-top']
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
