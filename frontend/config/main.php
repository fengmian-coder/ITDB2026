<?php
/** WorldCup Journey - Yii2 Advanced 公开前台应用配置。 */
declare(strict_types=1);

$config = require __DIR__ . '/../../common/config/web.php';
$config['id'] = 'worldcup-journey-frontend';
$config['basePath'] = dirname(__DIR__);
$config['controllerNamespace'] = 'frontend\controllers';
return $config;
