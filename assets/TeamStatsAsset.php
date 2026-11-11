<?php
/**
 * WorldCup Journey - 球队 PK 与图表页面样式
 * 模块：C 球队与统计；负责人：C，B 协助实现
 * 依赖：A 的 AppAsset；最近修改：2026-11-11
 */
declare(strict_types=1);

namespace app\assets;

use yii\web\AssetBundle;

class TeamStatsAsset extends AssetBundle
{
    public $basePath = '@webroot';
    public $baseUrl = '@web';
    public $css = ['css/stats.css'];
    public $depends = [AppAsset::class];
}
