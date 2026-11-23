<?php
/** WorldCup Journey - 团队及个人作业共用下载列表，沿用受控下载路由。 */
declare(strict_types=1);

use yii\helpers\Html;

/** @var app\models\ProjectDocument[] $documents */
/** @var string $emptyMessage */
/** @var string $headingTag */
?>
<div class="project-documents">
    <?php foreach ($documents as $document):
        $displayTitle = $document->title;
        if (isset($ownerName)) {
            $displayTitle = preg_replace('/\A个人作业\s*(\d+)(?:[：:]\s*' . preg_quote($ownerName, '/') . ')?\z/u', '作业 $1', $displayTitle) ?? $displayTitle;
        }
    ?>
        <article class="project-document">
            <div>
                <?= Html::tag($headingTag, Html::encode($displayTitle), ['class' => 'project-document-title']) ?>
                <p class="project-document-meta"><?= Html::encode(strtoupper(pathinfo($document->original_name, PATHINFO_EXTENSION))) ?> · <?= Yii::$app->formatter->asShortSize($document->file_size, 1) ?></p>
            </div>
            <?= Html::a('下载 ↓', ['/document/download', 'id' => $document->id], ['class' => 'btn btn-outline-success', 'aria-label' => '下载：' . $document->title, 'title' => $document->original_name]) ?>
        </article>
    <?php endforeach ?>
    <?php if ($documents === []): ?>
        <div class="project-assignment-empty"><p><?= Html::encode($emptyMessage) ?></p></div>
    <?php endif ?>
</div>
