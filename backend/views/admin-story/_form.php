<?php
/**
 * WorldCup Journey - 精选故事编辑与发布确认表单
 * 模块：B；负责人：B；依赖：MatchStory、Html、现有 Bootstrap
 * 最近修改：2026-11-05
 */
declare(strict_types=1);
use yii\helpers\Html;
/** @var yii\web\View $this */
/** @var app\models\MatchStory $model */
$action = $model->isNewRecord ? ['/admin-story/create'] : ['/admin-story/update', 'id' => $model->id];
?>
<div class="card border-0 shadow-sm rounded-4">
    <div class="card-body p-4">
        <?= Html::beginForm($action, 'post') ?>
        <?= Html::errorSummary($model, ['class' => 'alert alert-danger', 'header' => '请检查以下字段：']) ?>
        <div class="row g-3">
            <div class="col-12 col-md-3">
                <?= Html::activeLabel($model, 'match_id', ['class' => 'form-label', 'label' => '比赛 ID']) ?>
                <?= Html::activeTextInput($model, 'match_id', ['class' => 'form-control', 'inputmode' => 'numeric', 'pattern' => '[0-9]+', 'required' => true, 'aria-describedby' => 'story-match-hint']) ?>
                <p id="story-match-hint" class="small text-body-secondary mt-2 mb-0">填写比赛详情链接中的 ID，与赛事内比赛编号不同；一场比赛最多一篇故事</p>
            </div>
            <div class="col-12 col-md-9">
                <?= Html::activeLabel($model, 'title', ['class' => 'form-label']) ?>
                <?= Html::activeTextInput($model, 'title', ['class' => 'form-control', 'maxlength' => 150, 'required' => true]) ?>
            </div>
            <div class="col-12">
                <?= Html::activeLabel($model, 'summary', ['class' => 'form-label']) ?>
                <?= Html::activeTextarea($model, 'summary', ['class' => 'form-control', 'rows' => 3, 'maxlength' => 500, 'required' => true]) ?>
            </div>
            <div class="col-12">
                <?= Html::activeLabel($model, 'body', ['class' => 'form-label']) ?>
                <?= Html::activeTextarea($model, 'body', ['class' => 'form-control', 'rows' => 14, 'required' => true, 'aria-describedby' => 'story-body-hint']) ?>
                <p id="story-body-hint" class="small text-body-secondary mt-2 mb-0">正文使用纯文本，展示时保留换行；HTML 不作为页面代码执行</p>
            </div>
            <div class="col-12 col-md-6">
                <?= Html::activeLabel($model, 'cover_path', ['class' => 'form-label']) ?>
                <?= Html::activeTextInput($model, 'cover_path', ['class' => 'form-control', 'maxlength' => 255, 'placeholder' => '例如 images/stories/cover.jpg']) ?>
                <p class="small text-body-secondary mt-2 mb-0">可留空。使用已核验素材的站内相对路径，不填写外部图片链接</p>
            </div>
            <div class="col-12 col-md-6">
                <?= Html::activeLabel($model, 'source_url', ['class' => 'form-label']) ?>
                <?= Html::activeInput('url', $model, 'source_url', ['class' => 'form-control', 'maxlength' => 600]) ?>
            </div>
        </div>
        <section class="bg-body-tertiary rounded-3 p-3 mt-4" aria-labelledby="story-publication-title">
            <h2 id="story-publication-title" class="h6 fw-bold">发布设置</h2>
            <div class="form-check mb-2">
                <?= Html::activeCheckbox($model, 'is_published', ['label' => false, 'id' => 'story-is-published', 'class' => 'form-check-input', 'uncheck' => '0', 'value' => '1']) ?>
                <?= Html::label('发布到前台（取消勾选则保存为未发布）', 'story-is-published', ['class' => 'form-check-label']) ?>
            </div>
            <div class="form-check mb-2">
                <?= Html::checkbox('confirm_publish', false, ['id' => 'story-confirm-publish', 'class' => 'form-check-input', 'value' => '1']) ?>
                <?= Html::label('我已核对故事事实、来源及素材使用许可', 'story-confirm-publish', ['class' => 'form-check-label']) ?>
            </div>
            <p class="small text-body-secondary mb-0">保存为已发布时须完成上述确认，包括修改已发布故事。发布会一并开放该场已整理的事件时间轴和标签；撤回发布后，前台只保留比赛基础信息</p>
        </section>
        <div class="d-flex flex-wrap gap-2 mt-4">
            <?= Html::submitButton('保存故事', ['class' => 'btn btn-primary']) ?>
            <?= Html::a('取消', $model->isNewRecord ? ['/admin-story/index'] : ['/admin-story/view', 'id' => $model->id], ['class' => 'btn btn-outline-secondary']) ?>
        </div>
        <?= Html::endForm() ?>
    </div>
</div>
