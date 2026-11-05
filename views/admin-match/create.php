<?php
/**
 * WorldCup Journey - 后台新增比赛
 * 模块：B；负责人：B；依赖：WcMatch、共用 _form、Html
 * 最近修改：2026-11-05
 */
declare(strict_types=1);
use yii\helpers\Html;
/** @var yii\web\View $this */
/** @var app\models\WcMatch $model */
$this->title = '新增比赛';
$this->params['breadcrumbs'][] = ['label' => '比赛管理', 'url' => ['/admin-match/index']];
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="admin-match-create pb-4">
    <h1 class="h3 fw-bold mb-4"><?= Html::encode($this->title) ?></h1>
    <?= $this->render('_form', ['model' => $model]) ?>
</div>
