<?php
/**
 * WorldCup Journey - 后台修改场馆
 * 模块：B；负责人：B；依赖：Stadium、_form.php、现有布局
 * 最近修改：2026-11-05
 */
declare(strict_types=1);
use yii\helpers\Html;
/** @var yii\web\View $this */
/** @var app\models\Stadium $model */
$this->title = '修改场馆：' . $model->name;
$this->params['breadcrumbs'][] = ['label' => '场馆管理', 'url' => ['/admin-stadium/index']];
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="admin-stadium-update pb-4">
    <h1 class="h3 fw-bold mb-4"><?= Html::encode($this->title) ?></h1>
    <?= $this->render('_form', ['model' => $model]) ?>
</div>
