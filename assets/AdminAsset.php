<?php
/**
 * WorldCup Journey - 后台及管理员登录专用视觉资源。
 * 模块：A 后台公共界面；负责人：A。
 * 依赖：AppAsset，复用同一套 Yii / Bootstrap 资源。
 * 最近修改：2026-11-10。
 */
declare(strict_types=1);
namespace app\assets;

use yii\web\AssetBundle;

class AdminAsset extends AssetBundle
{
    public $basePath = '@webroot';
    public $baseUrl = '@web';
    public $css = ['css/admin.css'];
    public $depends = [AppAsset::class];
}
