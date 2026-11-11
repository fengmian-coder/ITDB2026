<?php
/**
 * WorldCup Journey - 本地 ECharts 与动态统计入口
 * 模块：C 球队与统计；负责人：C，B 协助实现
 * 依赖：TeamStatsAsset、Apache ECharts 6.1.0；最近修改：2026-11-11
 */
declare(strict_types=1);

namespace app\assets;

use yii\web\AssetBundle;

class InsightAsset extends AssetBundle
{
    public $basePath = '@webroot';
    public $baseUrl = '@web';
    public $js = ['vendor/echarts/echarts.min.js', 'js/insight-charts.js'];
    public $depends = [TeamStatsAsset::class];
}
