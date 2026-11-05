<?php
/**
 * WorldCup Journey - 共享标签字典编辑与受保护删除
 * 模块：B；负责人：B；依赖：MatchStory、MatchTag、Html、现有 Bootstrap
 * 最近修改：2026-11-05
 */
declare(strict_types=1);
use yii\helpers\Html;
/** @var yii\web\View $this */
/** @var app\models\MatchStory $story */
/** @var app\models\MatchTag $tag */
$this->title = $tag->isNewRecord ? '新增字典标签' : '编辑共享标签';
$this->params['breadcrumbs'][] = ['label' => '故事标签管理', 'url' => ['/admin-story/tags', 'id' => $story->id]];
$this->params['breadcrumbs'][] = $this->title;
$action = $tag->isNewRecord ? ['/admin-story/tag-create', 'id' => $story->id]
    : ['/admin-story/tag-update', 'id' => $story->id, 'tag_id' => $tag->id];
?>
<div class="admin-story-tag-form pb-4">
    <h1 class="h3 fw-bold mb-4"><?= Html::encode($this->title) ?></h1>
    <p class="alert alert-warning">此处维护全站共享字典。修改代码或名称会影响所有关联故事；新建标签不会自动关联到当前故事。</p>
    <div class="card border-0 shadow-sm rounded-4 mb-4"><div class="card-body p-4">
        <?= Html::beginForm($action, 'post') ?>
        <?= Html::errorSummary($tag, ['class' => 'alert alert-danger']) ?>
        <div class="row g-3 mb-4">
            <?php foreach (['code', 'name'] as $attribute): ?>
                <div class="col-12 col-md-6"><?= Html::activeLabel($tag, $attribute, ['class' => 'form-label']) ?>
                    <?= Html::activeTextInput($tag, $attribute, ['class' => 'form-control', 'maxlength' => 40, 'required' => true]) ?></div>
            <?php endforeach ?>
        </div>
        <?php if (!$tag->isNewRecord): ?>
            <div class="form-check mb-3">
                <?= Html::checkbox('confirm_shared', false, ['id' => 'tag-confirm-shared', 'value' => '1', 'required' => true, 'class' => 'form-check-input']) ?>
                <?= Html::label('已核对标签内容与素材许可，确认修改所有关联故事的展示。', 'tag-confirm-shared', ['class' => 'form-check-label']) ?>
            </div>
        <?php endif ?>
        <?= Html::submitButton('保存标签字典', ['class' => 'btn btn-primary']) ?>
        <?= Html::a('返回故事标签', ['/admin-story/tags', 'id' => $story->id], ['class' => 'btn btn-outline-secondary']) ?>
        <?= Html::endForm() ?>
    </div></div>
    <?php if (!$tag->isNewRecord): ?>
        <section class="card border-danger-subtle"><div class="card-body p-4">
            <h2 class="h5">删除字典标签</h2><p>已有故事关联时拒绝删除，不自动清理任何故事的关联记录。</p>
            <?= Html::beginForm(['/admin-story/tag-delete', 'id' => $story->id, 'tag_id' => $tag->id], 'post') ?>
            <?php foreach (['confirm_delete' => '确认删除该标签。', 'confirm_shared' => '确认删除共享字典项的影响。'] as $name => $label): ?>
                <div class="form-check mb-2">
                    <?= Html::checkbox($name, false, ['id' => 'tag-delete-' . $name, 'value' => '1', 'required' => true, 'class' => 'form-check-input']) ?>
                    <?= Html::label($label, 'tag-delete-' . $name, ['class' => 'form-check-label']) ?>
                </div>
            <?php endforeach ?>
            <?= Html::submitButton('删除字典标签', ['class' => 'btn btn-outline-danger']) ?>
            <?= Html::endForm() ?>
        </div></section>
    <?php endif ?>
</div>
