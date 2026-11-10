<?php
/**
 * WorldCup Journey - 作业文件管理列表。
 * 模块：A 作业下载；负责人：A。
 * 依赖：ProjectDocument、ActiveDataProvider、Yii Html。
 * 最近修改：2026-11-10。
 */
declare(strict_types=1);
use app\models\ProjectDocument;
use yii\helpers\Html;
use yii\widgets\LinkPager;
$this->title = '作业管理';
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="project-admin">
    <div class="project-section-heading mb-4"><div><h1>作业管理</h1><p class="text-body-secondary mb-0">上传团队或个人作业，文件通过受控入口下载</p></div><?= Html::a('上传作业', ['create'], ['class' => 'btn btn-success']) ?></div>
    <div class="table-responsive project-table-wrap"><table class="table align-middle mb-0">
        <thead><tr><th>标题与文件</th><th>类型 / 所属</th><th>操作</th></tr></thead>
        <tbody>
        <?php foreach ($provider->getModels() as $document): ?>
            <tr><td><strong><?= Html::encode($document->title) ?></strong><br><small class="text-body-secondary"><?= Html::encode($document->original_name) ?> · <?= Yii::$app->formatter->asShortSize($document->file_size) ?></small></td>
                <td><?= Html::encode(ProjectDocument::TYPES[$document->document_type]) ?><br><?= Html::encode($document->member?->display_name ?? $document->projectTeam->name) ?></td>
                <td><div class="d-flex flex-wrap gap-2">
                    <?= Html::a('下载', ['/document/download', 'id' => $document->id], ['class' => 'btn btn-sm btn-outline-secondary']) ?>
                    <?= Html::a('编辑', ['update', 'id' => $document->id], ['class' => 'btn btn-sm btn-outline-success']) ?>
                    <?= Html::beginForm(['delete', 'id' => $document->id], 'post', ['class' => 'm-0']) ?>
                    <label class="admin-delete-confirm"><?= Html::checkbox('confirm_delete', false, ['value' => '1', 'required' => true, 'aria-label' => '确认删除 ' . $document->title]) ?> 确认</label>
                    <?= Html::submitButton('删除', ['class' => 'btn btn-sm btn-outline-danger']) ?>
                    <?= Html::endForm() ?>
                </div></td></tr>
        <?php endforeach ?>
        <?php if ($provider->getTotalCount() === 0): ?><tr><td colspan="3">暂无文件，请先上传</td></tr><?php endif ?>
        </tbody></table></div>
    <p class="text-body-secondary mt-2">删除前请勾选确认框，删除后前台不再提供该文件</p>
    <?= LinkPager::widget(['pagination' => $provider->pagination]) ?>
</div>
