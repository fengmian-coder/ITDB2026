<?php
/**
 * WorldCup Journey - 统一品牌页脚与课程资料入口。
 * 模块：A 公共界面；负责人：A。
 * 依赖：Yii Html、团队与作业路由；最近修改：2026-11-10。
 */
declare(strict_types=1);
use yii\helpers\Html;
?>
<footer id="footer" class="wc-footer mt-auto"><div class="container">
    <div><strong>WorldCup Journey</strong><p>每支球队，都有自己的世界杯旅程</p></div>
    <nav aria-label="页脚导航"><?= Html::a('球迷留言', ['/guestbook/index']) ?><?= Html::a('关于我们', ['/project-team/index']) ?><?= Html::a('作业下载', ['/document/index']) ?></nav>
    <p class="wc-footer-note">互联网数据库开发课程项目 · <?= date('Y') ?></p>
</div></footer>
