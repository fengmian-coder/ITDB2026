<?php
/** WorldCup Journey - Yii2 Advanced 控制台应用配置。 */
declare(strict_types=1);

$config = require __DIR__ . '/../../common/config/main.php';
$config += [
    'id' => 'worldcup-journey-console',
    'basePath' => dirname(__DIR__),
    'controllerNamespace' => 'console\controllers',
];
$config['aliases']['@tests'] = '@app/tests';
$config['components']['log'] = [
    'targets' => [[
        'class' => \yii\log\FileTarget::class,
        'levels' => ['error', 'warning'],
    ]],
];
if (YII_ENV_DEV) {
    $config['bootstrap'][] = 'gii';
    $config['modules']['gii'] = ['class' => \yii\gii\Module::class];
    $config['bootstrap'][] = 'debug';
    $config['modules']['debug'] = ['class' => \yii\debug\Module::class];
}

return $config;
