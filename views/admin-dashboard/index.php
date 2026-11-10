<?php
/**
 * WorldCup Journey - 管理概览与模块入口。
 * 模块：D 工作台 / A 视觉统一；负责人：D、A。
 * 依赖：AdminDashboardController、AdminNavigation；最近修改：2026-11-10。
 */
declare(strict_types=1);
use app\components\AdminNavigation;
use yii\helpers\Html;
$this->title = '管理概览';
$metrics = [
    ['key' => 'pending', 'label' => '待审核留言', 'route' => '/admin-guestbook/index', 'suffix' => '条', 'note' => '审核通过后公开'],
    ['key' => 'matches', 'label' => '比赛档案', 'route' => '/admin-match/index', 'suffix' => '场', 'note' => '查看和编辑比赛'],
    ['key' => 'members', 'label' => '团队成员', 'route' => '/admin-project/index', 'suffix' => '位', 'note' => '编辑姓名与分工'],
    ['key' => 'documents', 'label' => '课程资料', 'route' => '/admin-document/index', 'suffix' => '份', 'note' => '上传和下载作业'],
];
?>
<div class="admin-dashboard">
    <header class="admin-page-heading"><div><h1>管理概览</h1><p>查看内容数量，进入各模块管理页面</p></div><?= Html::a('查看网站前台', ['/site/index'], ['class' => 'btn btn-outline-primary']) ?></header>
    <div class="admin-metrics">
        <?php foreach ($metrics as $metric): ?>
            <?= Html::a('<span class="admin-metric-label">' . Html::encode($metric['label']) . '</span><span class="admin-metric-value">' . (int) $overview[$metric['key']] . '<small>' . $metric['suffix'] . '</small></span><span class="admin-metric-note">' . Html::encode($metric['note']) . '<span aria-hidden="true"></span></span>', [$metric['route']], ['class' => 'admin-metric' . ($metric['key'] === 'pending' && $overview['pending'] > 0 ? ' has-pending' : '')]) ?>
        <?php endforeach ?>
    </div>
    <div class="admin-module-groups">
        <?php foreach (AdminNavigation::groups() as $group => $items): if ($group === '工作台') continue; ?>
            <section class="admin-module-section"><div class="admin-section-heading"><h2><?= Html::encode($group) ?></h2><span><?= count($items) ?> 个管理入口</span></div>
                <div class="admin-module-list">
                    <?php foreach ($items as $item): ?>
                        <?= Html::a('<div><h3>' . Html::encode($item['label']) . '</h3><p>' . Html::encode($item['description']) . '</p></div><span class="admin-module-arrow" aria-hidden="true">→</span>', ['/' . $item['id'] . '/index'], ['class' => 'admin-module-link']) ?>
                    <?php endforeach ?>
                </div>
            </section>
        <?php endforeach ?>
    </div>
</div>
