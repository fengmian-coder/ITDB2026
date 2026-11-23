<?php
/** WorldCup Journey - Web 入口共用的 PHP 与 Yii 引导。 */
declare(strict_types=1);

defined('YII_ENV') or define('YII_ENV', getenv('YII_ENV') ?: 'dev');
defined('YII_DEBUG') or define('YII_DEBUG', YII_ENV === 'dev');

$root = dirname(__DIR__, 2);
require_once $root . '/vendor/autoload.php';
require_once $root . '/vendor/yiisoft/yii2/Yii.php';
Yii::setAlias('@common', $root . '/common');
