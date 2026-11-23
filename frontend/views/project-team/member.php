<?php
/** WorldCup Journey - 成员介绍与个人作业下载页。 */
declare(strict_types=1);

use yii\helpers\Html;

/** @var app\models\ProjectMember $member */
/** @var app\models\ProjectDocument[] $documents */
$this->title = $member->display_name . '的个人作业 · WorldCup Journey';
$this->params['breadcrumbs'][] = ['label' => '关于我们', 'url' => ['index']];
$this->params['breadcrumbs'][] = $member->display_name . '的个人作业';
?>
<div class="project-page project-member-page">
    <header class="project-intro">
        <div class="project-member-heading">
            <h1><?= Html::encode($member->display_name) ?>的个人作业</h1>
            <?= Html::a('← 返回关于我们', ['index', '#' => 'team-' . $member->project_team_id], ['class' => 'project-back-link']) ?>
        </div>
        <p class="project-lead"><?= Html::encode($member->role_title) ?></p>
    </header>
    <section class="project-personal-documents" aria-labelledby="personal-documents-title">
        <div class="project-section-heading">
            <h2 id="personal-documents-title">作业文件</h2>
            <span><?= count($documents) ?> 份作业</span>
        </div>
        <?= $this->render('_documents', ['documents' => $documents, 'emptyMessage' => '个人作业待上传', 'headingTag' => 'h3', 'ownerName' => $member->display_name]) ?>
    </section>
</div>
