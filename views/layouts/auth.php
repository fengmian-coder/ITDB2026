<?php
/**
 * WorldCup Journey - 管理员登录的品牌与表单布局。
 * 模块：A 后台公共界面；负责人：A。
 * 依赖：AdminAsset、D 的登录 View；最近修改：2026-11-10。
 */
declare(strict_types=1);
use app\assets\AdminAsset;
use app\widgets\Alert;
use yii\helpers\Html;
$this->render('_head');
AdminAsset::register($this);
?>
<?php $this->beginPage() ?>
<!DOCTYPE html>
<html lang="zh-CN" data-bs-theme="light">
<head><?php $this->head() ?><title><?= Html::encode($this->title) ?> · WorldCup Journey</title></head>
<body class="auth-body">
<?php $this->beginBody() ?>
<div class="auth-container">
    <header class="auth-topline"><?= Html::a('WorldCup Journey', ['/site/index'], ['class' => 'auth-wordmark']) ?><?= Html::a('返回网站', ['/site/index']) ?></header>
    <main class="auth-card">
        <section class="auth-brand-panel" aria-label="管理平台介绍">
            <h2>网站后台管理</h2>
            <p class="auth-brand-description">管理赛事资料、团队内容和球迷留言</p>
            <p class="auth-brand-signature">2026 世界杯数据探索与记忆平台</p>
        </section>
        <section class="auth-form-panel"><?= Alert::widget() ?><?= $content ?></section>
    </main>
    <footer class="auth-footer">互联网数据库开发课程项目 · WorldCup Journey</footer>
</div>
<?php $this->endBody() ?>
</body>
</html>
<?php $this->endPage() ?>
