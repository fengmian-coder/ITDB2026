<?php
/**
 * WorldCup Journey - 共享标签字典与故事标签关联管理
 * 模块：B；负责人：B；依赖：MatchStory、MatchTag、ActiveDataProvider、Bootstrap LinkPager
 * 最近修改：2026-11-05
 */
declare(strict_types=1);
use yii\helpers\Html;
use yii\bootstrap5\LinkPager;
/** @var yii\web\View $this */
/** @var app\models\MatchStory $story */
/** @var yii\data\ActiveDataProvider $dataProvider */
/** @var array $selectedIds */
$this->title = '故事标签管理';
$this->params['breadcrumbs'][] = ['label' => '精选故事管理', 'url' => ['/admin-story/index']];
$this->params['breadcrumbs'][] = $this->title;
$selected = array_fill_keys(array_map('strval', $selectedIds), true);
$tags = $dataProvider->getModels();
?>
<div class="admin-story-tags pb-4">
    <header class="d-flex flex-wrap justify-content-between gap-3 mb-4">
        <div><h1 class="h3 fw-bold"><?= Html::encode($this->title) ?></h1><p class="text-body-secondary mb-0"><?= Html::encode($story->title) ?></p></div>
        <div class="d-flex gap-2 align-items-start">
            <?= Html::a('新增字典标签', ['/admin-story/tag-create', 'id' => $story->id], ['class' => 'btn btn-primary']) ?>
            <?= Html::a('返回故事', ['/admin-story/view', 'id' => $story->id], ['class' => 'btn btn-outline-secondary']) ?>
        </div>
    </header>
    <p class="alert alert-info">标签字典全站共享；关联和解除仅作用于当前故事<?= $story->isPublished() ? '本故事已发布，修改关联前请核对事实及前台影响' : '未发布故事的标签不在前台显示' ?></p>
    <div class="table-responsive card border-0 shadow-sm rounded-4"><table class="table align-middle mb-0">
        <thead><tr><th>代码</th><th>名称</th><th>当前故事</th><th>操作</th></tr></thead><tbody>
        <?php foreach ($tags as $tag): $linked = isset($selected[(string) $tag->id]); ?>
            <tr><td class="text-break"><?= Html::encode($tag->code) ?></td><td class="text-break"><?= Html::encode($tag->name) ?></td>
                <td><?= $linked ? '已关联' : '未关联' ?></td>
                <td>
                    <?= Html::a('编辑字典', ['/admin-story/tag-update', 'id' => $story->id, 'tag_id' => $tag->id], ['class' => 'btn btn-sm btn-outline-secondary mb-2']) ?>
                    <?= Html::beginForm(['/admin-story/' . ($linked ? 'tag-remove' : 'tag-add'), 'id' => $story->id, 'tag_id' => $tag->id], 'post') ?>
                    <?php if ($story->isPublished()): ?>
                        <div class="form-check mb-2">
                            <?= Html::checkbox('confirm_publish', false, ['id' => 'tag-review-' . $tag->id, 'value' => '1', 'required' => true, 'class' => 'form-check-input']) ?>
                            <?= Html::label('确认前台影响', 'tag-review-' . $tag->id, ['class' => 'form-check-label small']) ?>
                        </div>
                    <?php endif ?>
                    <?= Html::submitButton($linked ? '解除关联' : '关联到故事', ['class' => 'btn btn-sm ' . ($linked ? 'btn-outline-danger' : 'btn-outline-primary')]) ?>
                    <?= Html::endForm() ?>
                </td></tr>
        <?php endforeach ?>
        <?php if (!$tags): ?><tr><td colspan="4" class="text-center text-body-secondary py-5">暂无标签，可先新增字典标签</td></tr><?php endif ?>
        </tbody></table></div>
    <div class="mt-4"><?= LinkPager::widget(['pagination' => $dataProvider->getPagination()]) ?></div>
</div>
