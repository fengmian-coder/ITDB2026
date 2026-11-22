<?php
/**
 * WorldCup Journey - 关于我们、成员个人作业入口与团队作业下载。
 * 模块：A 团队资料；负责人：A。
 * 依赖：ProjectTeamController、ProjectTeam / ProjectMember、project.css。
 * 最近修改：2026-11-18。
 */
declare(strict_types=1);

use yii\helpers\Html;

/** @var app\models\ProjectTeam[] $teams */
/** @var array<string, string> $studentNumbers */
$this->title = '关于我们 · WorldCup Journey';
$this->params['breadcrumbs'][] = '关于我们';
?>
<div class="project-page project-about-page">
    <header class="project-intro">
        <h1>关于我们</h1>
    </header>
    <?php if ($teams === []): ?>
        <div class="project-empty">
            <h2>暂无团队资料</h2>
        </div>
    <?php endif ?>
    <?php foreach ($teams as $team): ?>
        <section class="project-team-section" id="team-<?= (int) $team->id ?>" aria-labelledby="team-title-<?= (int) $team->id ?>">
            <div class="project-team-heading">
                <?php if ($team->logo_path): ?>
                    <?= Html::img('@web/' . $team->logo_path, ['alt' => $team->name . '标志', 'class' => 'project-team-logo']) ?>
                <?php endif ?>
                <div>
                    <h2 id="team-title-<?= (int) $team->id ?>"><?= Html::encode($team->name) ?></h2>
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
                <?php $hasCourseMembers = array_diff_key($studentNumbers, array_count_values(array_column($team->members, 'display_name'))) === []; ?>
                <p class="project-section-note">点击成员卡片，查看并下载该成员的个人作业</p>
                <div class="project-members">
                    <?php foreach ($team->members as $index => $member): ?>
                        <a class="project-member project-member-link" href="<?= Html::encode(yii\helpers\Url::to(['member', 'id' => $member->id])) ?>" aria-label="<?= Html::encode('查看' . $member->display_name . '的个人作业') ?>">
                            <span class="project-member-number" aria-hidden="true"><?= sprintf('%02d', $index + 1) ?></span>
                            <h4><?= Html::encode($member->display_name) ?></h4>
                            <p class="project-member-role"><?= Html::encode($member->role_title) ?></p>
                            <?php if ($hasCourseMembers && isset($studentNumbers[$member->display_name])): ?>
                                <p class="small text-body-secondary mb-2">学号 <?= Html::encode($studentNumbers[$member->display_name]) ?></p>
                            <?php endif ?>
                            <p class="project-member-bio"><?= nl2br(Html::encode($member->bio ?: '暂无个人介绍')) ?></p>
                            <span class="project-member-work">
                                <span class="project-member-count">个人作业 · 已上传 <?= count($member->documents) ?> 份</span>
                                <span class="project-member-action">查看个人作业 <span aria-hidden="true">→</span></span>
                            </span>
                        </a>
                    <?php endforeach ?>
                </div>
            <?php endif ?>
            <section class="project-team-documents" aria-labelledby="team-documents-title-<?= (int) $team->id ?>">
                <div class="project-section-heading">
                    <h3 id="team-documents-title-<?= (int) $team->id ?>">团队作业</h3>
                    <span><?= count($team->documents) ?> 份作业</span>
                </div>
                <?= $this->render('_documents', ['documents' => $team->documents, 'emptyMessage' => '团队作业待上传', 'headingTag' => 'h4']) ?>
            </section>
        </section>
    <?php endforeach ?>
</div>
