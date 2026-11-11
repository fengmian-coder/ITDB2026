<?php
/**
 * WorldCup Journey - 所有已交付后台共用的侧栏、顶部工具栏和内容布局。
 * 模块：A 后台公共界面；负责人：A。
 * 依赖：AdminAsset、AdminNavigation、D 的管理员会话及 POST 退出路由。
 * 最近修改：2026-11-10。
 */
declare(strict_types=1);
use app\assets\AdminAsset;
use app\components\AdminNavigation;
use app\widgets\Alert;
use yii\bootstrap5\Breadcrumbs;
use yii\helpers\Html;

$this->render('_head');
AdminAsset::register($this);
$current = AdminNavigation::current($this->context->id);
$username = Yii::$app->user->identity?->username ?? '管理员';
$breadcrumbs = $this->params['breadcrumbs'] ?? [];
if ($breadcrumbs === [] && $this->context->id !== 'admin-dashboard') $breadcrumbs[] = $current['label'];
?>
<?php $this->beginPage() ?>
<!DOCTYPE html>
<html lang="zh-CN" data-bs-theme="light">
<head><?php $this->head() ?><title><?= Html::encode($this->title) ?> · WorldCup Journey 后台</title></head>
<body class="admin-body">
<?php $this->beginBody() ?>
<a href="#admin-content" class="admin-skip-link">跳到页面内容</a>
<aside class="admin-sidebar" aria-label="后台主导航">
    <?= Html::a('<span class="admin-brand-name">WorldCup<br>Journey<span class="admin-brand-dot">.</span></span><span class="admin-brand-caption">网站内容管理</span>', ['/admin-dashboard/index'], ['class' => 'admin-brand', 'aria-label' => 'WorldCup Journey 管理概览']) ?>
    <nav class="admin-navigation">
        <?php foreach (AdminNavigation::groups() as $group => $items): ?>
            <div class="admin-nav-group">
                <p class="admin-nav-label"><?= Html::encode($group) ?></p>
                <?php foreach ($items as $item): $active = $this->context->id === $item['id']; ?>
                    <?= Html::a('<span class="admin-nav-marker" aria-hidden="true"></span>' . Html::encode($item['label']), ['/' . $item['id'] . '/index'], [
                        'class' => 'admin-nav-item' . ($active ? ' is-active' : ''), 'aria-current' => $active ? 'page' : null,
                    ]) ?>
                <?php endforeach ?>
            </div>
        <?php endforeach ?>
    </nav>
    <div class="admin-sidebar-footer"><?= Html::a('返回网站前台', ['/site/index']) ?><span>WORLD CUP JOURNEY · 2026</span></div>
</aside>
<div class="admin-workspace">
    <header class="admin-topbar">
        <div><span class="admin-topbar-caption">内容管理 /</span> <strong><?= Html::encode($current['label']) ?></strong></div>
        <div class="admin-account-tools">
            <span class="admin-account"><span class="admin-account-dot" aria-hidden="true"></span><?= Html::encode($username) ?></span>
            <?= Html::button('夜间模式', ['id' => 'theme-toggle', 'class' => 'admin-theme-toggle', 'aria-label' => '切换到夜间模式', 'title' => '切换到夜间模式']) ?>
            <?= Html::beginForm(['/auth/logout'], 'post', ['class' => 'admin-logout-form']) ?>
            <?= Html::submitButton('退出登录', ['class' => 'btn btn-sm btn-outline-secondary']) ?>
            <?= Html::endForm() ?>
        </div>
    </header>
    <main id="main" class="admin-main">
        <div id="admin-content" class="admin-page-content" tabindex="-1">
            <?= Breadcrumbs::widget(['homeLink' => ['label' => '管理概览', 'url' => ['/admin-dashboard/index']], 'links' => $breadcrumbs]) ?>
            <div class="admin-flashes"><?= Alert::widget() ?></div>
            <?= $content ?>
        </div>
    </main>
    <footer class="admin-footer"><span>WorldCup Journey · 管理工作台</span><?= Html::a('查看网站前台', ['/site/index']) ?></footer>
</div>
<?php $this->endBody() ?>
</body>
</html>
<?php $this->endPage() ?>
