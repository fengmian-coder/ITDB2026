<?php
/**
 * WorldCup Journey - 后台新增赛事
 * 模块：B；负责人：B；依赖：Tournament、_form.php、现有布局
 * 最近修改：2026-11-05
 */
declare(strict_types=1);
use yii\helpers\Html;
/** @var yii\web\View $this */
/** @var app\models\Tournament $model */
$this->title = '新增赛事';
$this->params['breadcrumbs'][] = ['label' => '赛事管理', 'url' => ['/admin-tournament/index']];
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="admin-tournament-create pb-4">
    <h1 class="h3 fw-bold mb-4"><?= Html::encode($this->title) ?></h1>
    <?= $this->render('_form', ['model' => $model]) ?>
</div>
