<?php
/**
 * WorldCup Journey - 基于个人作业 2 原型的数据库驱动首页。
 * 模块：A 首页；负责人：A。
 * 依赖：SiteController、B 的比赛服务、C 的参赛目录、journey.css。
 * 最近修改：2026-11-10。
 */
declare(strict_types=1);
use yii\helpers\Html;
$this->title = 'WorldCup Journey · 每支球队，都有自己的世界杯旅程';
$this->params['wideLayout'] = true;
$this->params['meta_description'] = '探索 2026 世界杯球队与比赛，记录属于你的世界杯记忆';
$this->registerCssFile('@web/css/home.css', ['depends' => [app\assets\AppAsset::class]]);
?>
<div class="wc-home">
    <section class="wc-hero">
        <div class="wc-home-inner">
            <p class="wc-kicker">FIFA WORLD CUP 2026</p>
            <h1>每支球队，都有自己的世界杯旅程</h1>
            <p class="wc-hero-description">查看球队与比赛信息，制作自己的世界杯纪念卡</p>
            <div class="wc-hero-actions">
                <?= Html::a('选择球队，开始旅程 →', ['/site/explore'], ['class' => 'btn wc-hero-button']) ?>
                <?= Html::a('探索比赛 →', ['/wc-match/index'], ['class' => 'btn wc-hero-secondary']) ?>
            </div>
        </div>
    </section>
    <section class="wc-explore" aria-labelledby="explore-heading">
        <div class="wc-home-inner wc-explore-layout">
            <div class="wc-explore-copy">
                <h2 id="explore-heading">探索参赛球队</h2>
                <p class="wc-explore-description">选择你关注的球队，探索它的 2026 世界杯故事</p>
                <p class="wc-data-summary">当前收录 <?= (int) $teamCount ?> 支球队 · <?= (int) $matchCount ?> 场比赛</p>
            </div>
            <div class="wc-explore-tools">
                <label class="wc-search-label" for="home-team-search">搜索球队</label>
                <?= Html::beginForm(['/site/explore'], 'get', ['class' => 'wc-search']) ?>
                <?= Html::textInput('q', '', ['id' => 'home-team-search', 'placeholder' => '输入球队名称或 FIFA 代码', 'maxlength' => 100]) ?>
                <?= Html::submitButton('搜索 →', ['aria-label' => '搜索球队']) ?>
                <?= Html::endForm() ?>
                <div class="wc-explore-support">
                    <p class="wc-feature-line">本届赛事表现 <span>·</span> 晋级之路 <span>·</span> 球队对比</p>
                    <?= Html::a('浏览全部球队', ['/site/explore']) ?>
                </div>
            </div>
        </div>
    </section>
    <section class="wc-memory-section">
        <div class="wc-home-inner wc-memory-layout">
            <div class="wc-memory-copy">
                <h2>留下你的世界杯记忆</h2>
                <p>选择一场比赛，写下感想，也可以添加照片</p>
                <?= Html::a('制作纪念卡', ['/memory/card'], ['class' => 'btn btn-success']) ?>
                <p class="wc-memory-note">无需注册，支持 PNG 下载</p>
            </div>
            <div class="wc-sample-card">
                <header class="wc-sample-header"><h3>我的世界杯记忆</h3><span class="wc-sample-label">卡片示例</span></header>
                <div class="wc-card-body wc-card-example">
                    <div class="wc-card-example-copy">
                        <p>我的主队：西班牙</p>
                        <p class="wc-card-match-date">2026.07.19 · 决赛（当地日期）</p>
                        <p><strong class="wc-card-score">阿根廷 0 : 1 西班牙（加时）</strong></p>
                        <p>经典时刻：费兰·托雷斯 106 分钟破门</p>
                        <p>感想示例：拼到最后，也值得铭记</p>
                    </div>
                    <?= Html::img('@web/images/memory-example-ferran.png', ['class' => 'wc-card-example-photo', 'alt' => '费兰·托雷斯庆祝进球', 'width' => 720, 'height' => 537, 'loading' => 'lazy']) ?>
                </div>
                <div class="wc-card-example-footer"><p class="wc-card-signature">WorldCup Journey · 2026</p><?= Html::a('比赛与图片来源：FIFA', 'https://inside.fifa.com/organisation/news/spain-crowned-world-cup-2026-champions-new-york-new-jersey', ['class' => 'wc-example-source', 'target' => '_blank', 'rel' => 'noopener noreferrer']) ?></div>
            </div>
        </div>
    </section>
</div>
