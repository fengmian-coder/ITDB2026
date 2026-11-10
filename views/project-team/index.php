<?php
/**
 * WorldCup Journey - 团队与成员公开介绍页。
 * 模块：A 团队资料；负责人：A。
 * 依赖：ProjectTeamController、ProjectTeam / ProjectMember、project.css。
 * 最近修改：2026-11-10。
 */
declare(strict_types=1);

use yii\helpers\Html;

/** @var app\models\ProjectTeam[] $teams */
$this->title = '关于我们 · WorldCup Journey';
$this->params['breadcrumbs'][] = '关于我们';
?>
<div class="project-page">
    <header class="project-intro">
        <h1>团队介绍</h1>
        <p class="project-lead">了解项目内容、团队成员及任务分工</p>
    </header>
    <?php if ($teams === []): ?>
        <div class="project-empty">
            <h2>暂无团队资料</h2>
            <?= Html::a('浏览比赛', ['/wc-match/index'], ['class' => 'btn btn-success']) ?>
        </div>
    <?php endif ?>
    <?php foreach ($teams as $team): ?>
        <section class="project-team-section" aria-labelledby="team-<?= Html::encode((string) $team->id) ?>">
            <div class="project-team-heading">
                <?php if ($team->logo_path): ?>
                    <?= Html::img('@web/' . $team->logo_path, ['alt' => $team->name . '标志', 'class' => 'project-team-logo']) ?>
                <?php endif ?>
                <div>
                    <p class="project-eyebrow">互联网数据库开发 · 课程团队</p>
                    <h2 id="team-<?= Html::encode((string) $team->id) ?>"><?= Html::encode($team->name) ?></h2>
                </div>
            </div>
            <?php if ($team->description): ?>
                <p class="project-description"><?= nl2br(Html::encode($team->description)) ?></p>
            <?php endif ?>
            <div class="project-section-heading">
                <h3>团队成员</h3>
                <span><?= count($team->members) ?> 位成员</span>
            </div>
            <?php if ($team->members === []): ?>
                <p class="text-body-secondary">暂无成员资料</p>
            <?php else: ?>
                <div class="project-members">
                    <?php foreach ($team->members as $index => $member): ?>
                        <article class="project-member">
                            <span class="project-member-number" aria-hidden="true"><?= sprintf('%02d', $index + 1) ?></span>
                            <h4><?= Html::encode($member->display_name) ?></h4>
                            <p class="project-member-role"><?= Html::encode($member->role_title) ?></p>
                            <p class="project-member-bio"><?= nl2br(Html::encode($member->bio ?: '暂无个人介绍')) ?></p>
                        </article>
                    <?php endforeach ?>
                </div>
            <?php endif ?>
        </section>
    <?php endforeach ?>
    <section class="project-outro">
        <div><h2>查看比赛信息</h2><p>浏览赛程、比分和比赛详情</p></div>
        <?= Html::a('探索比赛', ['/wc-match/index'], ['class' => 'btn btn-success']) ?>
    </section>
</div>
