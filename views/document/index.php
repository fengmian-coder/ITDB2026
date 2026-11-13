<?php
/**
 * WorldCup Journey - 团队与个人作业下载列表。
 * 模块：A 作业下载；负责人：A。
 * 依赖：ProjectDocument、ActiveDataProvider、LinkPager。
 * 最近修改：2026-11-13，B 协助拆分团队及个人作业栏目。
 */
declare(strict_types=1);
use app\models\ProjectDocument;
use yii\helpers\Html;
use yii\widgets\LinkPager;
$this->title = '课程作业下载';
$this->params['breadcrumbs'][] = $this->title;
/** @var array<string, yii\data\ActiveDataProvider> $providers */
?>
<div class="project-page">
    <header class="project-intro"><h1>课程作业下载</h1><p class="project-lead">下载团队资料和成员个人作业</p></header>
    <nav class="d-flex flex-wrap gap-2 mb-4" aria-label="作业分类">
        <a href="#team-documents" class="btn btn-outline-success">团队作业</a>
        <a href="#personal-documents" class="btn btn-outline-success">个人作业</a>
    </nav>
    <?php foreach (['TEAM' => 'team', 'PERSONAL' => 'personal'] as $type => $section): $provider = $providers[$type]; ?>
    <section id="<?= $section ?>-documents" aria-labelledby="<?= $section ?>-title" class="mb-5">
    <h2 id="<?= $section ?>-title" class="h3"><?= Html::encode(ProjectDocument::TYPES[$type]) ?></h2>
    <div class="project-documents">
        <?php foreach ($provider->getModels() as $document): ?>
            <article class="project-document">
                <div>
                    <p class="project-eyebrow"><?= Html::encode(ProjectDocument::TYPES[$document->document_type]) ?> · <?= Html::encode($document->member?->display_name ?? $document->projectTeam->name) ?></p>
                    <h3 class="h5"><?= Html::encode($document->title) ?></h3>
                    <p class="text-body-secondary mb-0"><?= Html::encode($document->original_name) ?> · <?= Yii::$app->formatter->asShortSize($document->file_size) ?></p>
                </div>
                <?= Html::a('下载文件 ↓', ['download', 'id' => $document->id], ['class' => 'btn btn-outline-success']) ?>
            </article>
        <?php endforeach ?>
        <?php if ($provider->getTotalCount() === 0): ?><p class="text-body-secondary">暂无可下载的<?= Html::encode(ProjectDocument::TYPES[$type]) ?>。</p><?php endif ?>
    </div>
    <?= LinkPager::widget(['pagination' => $provider->pagination, 'options' => ['class' => 'pagination flex-wrap', 'aria-label' => ProjectDocument::TYPES[$type] . '分页']]) ?>
    </section>
    <?php endforeach ?>
</div>
