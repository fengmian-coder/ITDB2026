<?php
/**
 * WorldCup Journey - Web 入口，仅 web 目录对外公开
 * 模块：公共骨架；负责人：B；依赖：Composer、config/web.php
 * 最近修改：2026-11-03
 */

declare(strict_types=1);

defined('YII_ENV') or define('YII_ENV', getenv('YII_ENV') ?: 'dev');
defined('YII_DEBUG') or define('YII_DEBUG', YII_ENV === 'dev');

require __DIR__ . '/../vendor/autoload.php';
require __DIR__ . '/../vendor/yiisoft/yii2/Yii.php';

$config = require __DIR__ . '/../config/web.php';

(new yii\web\Application($config))->run();
