<?php
/**
 * WorldCup Journey - 参赛球队搜索与现有模块的只读衔接入口。
 * 模块：A 首页入口；负责人：A。
 * 依赖：TeamDirectoryService；C 的正式列表交付后由 SiteController 自动转交。
 * 最近修改：2026-11-10。
 */
declare(strict_types=1);
use yii\helpers\Html;
$this->title = '探索球队';
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="project-page wc-team-explore">
    <header class="project-intro"><h1>探索每一支球队</h1><p class="project-lead">搜索球队，查看比赛历程和相关比赛</p></header>
    <?= Html::beginForm(['/site/explore'], 'get', ['class' => 'wc-search my-4']) ?>
    <label for="explore-search" class="visually-hidden">球队名称或 FIFA 代码</label><?= Html::textInput('q', $q, ['id' => 'explore-search', 'maxlength' => 100, 'placeholder' => '输入球队名称或 FIFA 代码']) ?><?= Html::submitButton('搜索') ?><?= Html::endForm() ?>
    <p class="text-body-secondary">找到 <?= count($teams) ?> 支球队</p>
    <div class="wc-team-directory">
        <?php foreach ($teams as $team): ?>
            <article><p class="project-eyebrow"><?= Html::encode($team['fifa_code']) ?></p><h2><?= Html::encode($team['name_zh']) ?></h2><p><?= Html::encode($team['name_en']) ?></p><div class="d-flex gap-3 flex-wrap"><?= Html::a('球队旅程 →', ['/journey/view', 'team_id' => $team['id']]) ?><?= Html::a('制作纪念卡 →', ['/memory/card', 'team_id' => $team['id']]) ?></div></article>
        <?php endforeach ?>
    </div>
    <?php if ($teams === []): ?><div class="project-empty"><h2><?= $q === '' ? '暂无球队数据' : '未找到匹配的球队' ?></h2><p><?= $q === '' ? '可前往比赛页面查看已收录的赛程' : '试试其他球队名称、英文名称或 FIFA 代码' ?></p><?= Html::a('探索比赛', ['/wc-match/index'], ['class' => 'btn btn-success']) ?></div><?php endif ?>
</div>
