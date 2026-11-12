<?php
/**
 * WorldCup Journey - 球队对比独立图表资源
 * 模块：A/C 前端整合；负责人：A；依赖：TeamStatsAsset、本地 ECharts
 * 最近修改：2026-11-12
 */
declare(strict_types=1);
namespace app\assets;
use yii\web\AssetBundle;
class TeamCompareAsset extends AssetBundle
{
    public $basePath = '@webroot';
    public $baseUrl = '@web';
    public $js = ['vendor/echarts/echarts.min.js', 'js/team-compare.js'];
    public $depends = [TeamStatsAsset::class];
}
