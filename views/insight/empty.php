<?php
/**
 * WorldCup Journey - 赛事数据尚未就绪时的公开提示
 * 模块：A 公共页面联调；负责人：A；依赖：InsightController
 * 最近修改：2026-11-12
 */
declare(strict_types=1);
use yii\helpers\Html;
$this->title = '世界杯数据 | WorldCup Journey';
$this->params['breadcrumbs'][] = '数据总览';
?>
<section class="project-intro">
    <h1>世界杯数据总览</h1>
    <p class="project-lead">赛事数据尚未就绪，暂时无法展示图表</p>
    <?= Html::a('返回首页', ['/site/index'], ['class' => 'btn btn-primary']) ?>
</section>
