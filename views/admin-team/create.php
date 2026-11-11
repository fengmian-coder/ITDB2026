<?php
/**
 * WorldCup Journey - 后台新增球队
 * 模块：C；负责人：C，B 协助实现；依赖：NationalTeam、_form.php、现有布局
 * 最近修改：2026-11-11
 */
declare(strict_types=1);
use yii\helpers\Html;
/** @var yii\web\View $this */
/** @var app\models\NationalTeam $model */
$this->title = '新增球队';
$this->params['breadcrumbs'][] = ['label' => '球队管理', 'url' => ['/admin-team/index']];
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="admin-team-create pb-4">
    <h1 class="h3 fw-bold mb-4"><?= Html::encode($this->title) ?></h1>
    <?= $this->render('_form', ['model' => $model]) ?>
</div>
