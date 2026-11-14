<?php
/**
 * WorldCup Journey - 比分走势资源
 * 模块：B；负责人：B；依赖：AppAsset、ECharts
 * 最近修改：2026-11-14
 */

declare(strict_types=1);

namespace app\assets;

use yii\web\AssetBundle;

class MatchMomentumAsset extends AssetBundle
{
    public $basePath = '@webroot';
    public $baseUrl = '@web';
    public $js = ['vendor/echarts/echarts.min.js', 'js/match-momentum.js'];
    public $depends = [AppAsset::class];
}
