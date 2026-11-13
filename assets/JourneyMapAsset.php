<?php
/**
 * WorldCup Journey - 球队旅程地图本地资源
 * 模块：B；负责人：B；依赖：AppAsset、Apache ECharts
 * 最近修改：2026-11-13
 */
declare(strict_types=1);
namespace app\assets;
use yii\web\AssetBundle;
class JourneyMapAsset extends AssetBundle
{
    public $basePath = '@webroot';
    public $baseUrl = '@web';
    public $css = ['css/journey-map.css'];
    public $js = ['vendor/echarts/echarts.min.js', 'js/journey-map.js'];
    public $depends = [AppAsset::class];
}
