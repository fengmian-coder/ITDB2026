<?php
/**
 * WorldCup Journey - 团队与个人作业下载列表。
 * 模块：A 作业下载；负责人：A。
 * 依赖：ProjectDocument、ActiveDataProvider、LinkPager。
 * 最近修改：2026-11-10。
 */
declare(strict_types=1);
use app\models\ProjectDocument;
use yii\helpers\Html;
use yii\widgets\LinkPager;
$this->title = '课程作业下载';
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="project-page">
    <header class="project-intro"><h1>课程作业下载</h1><p class="project-lead">下载团队资料和成员个人作业</p></header>
    <div class="project-documents">
        <?php foreach ($provider->getModels() as $document): ?>
            <article class="project-document">
                <div>
                    <p class="project-eyebrow"><?= Html::encode(ProjectDocument::TYPES[$document->document_type]) ?> · <?= Html::encode($document->member?->display_name ?? $document->projectTeam->name) ?></p>
                    <h2><?= Html::encode($document->title) ?></h2>
                    <p class="text-body-secondary mb-0"><?= Html::encode($document->original_name) ?> · <?= Yii::$app->formatter->asShortSize($document->file_size) ?></p>
                </div>
                <?= Html::a('下载文件 ↓', ['download', 'id' => $document->id], ['class' => 'btn btn-outline-success']) ?>
            </article>
        <?php endforeach ?>
        <?php if ($provider->getTotalCount() === 0): ?><div class="project-empty"><h2>暂无可下载的作业</h2></div><?php endif ?>
    </div>
    <?= LinkPager::widget(['pagination' => $provider->pagination]) ?>
</div>
