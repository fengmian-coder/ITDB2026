<?php
/**
 * WorldCup Journey - 后台修改精选故事
 * 模块：B；负责人：B；依赖：MatchStory、_form.php、现有布局
 * 最近修改：2026-11-05
 */
declare(strict_types=1);
use yii\helpers\Html;
/** @var yii\web\View $this */
/** @var app\models\MatchStory $model */
$this->title = '修改精选故事：' . $model->title;
$this->params['breadcrumbs'][] = ['label' => '精选故事管理', 'url' => ['/admin-story/index']];
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="admin-story-update pb-4">
    <h1 class="h3 fw-bold mb-4"><?= Html::encode($this->title) ?></h1>
    <?= $this->render('_form', ['model' => $model]) ?>
</div>
