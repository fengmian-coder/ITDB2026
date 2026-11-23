<?php
/** WorldCup Journey - 前台、后台与控制台共用的 Advanced 配置。 */
declare(strict_types=1);

$root = dirname(__DIR__, 2);

return [
    'name' => 'WorldCup Journey',
    'timeZone' => 'UTC',
    'runtimePath' => $root . '/runtime',
    'vendorPath' => $root . '/vendor',
    'bootstrap' => ['log'],
    'aliases' => [
        '@app' => $root,
        '@common' => $root . '/common',
        '@frontend' => $root . '/frontend',
        '@backend' => $root . '/backend',
        '@console' => $root . '/console',
        '@bower' => '@vendor/bower-asset',
        '@npm' => '@vendor/npm-asset',
    ],
    'components' => [
        'cache' => ['class' => \yii\caching\FileCache::class],
        'db' => require $root . '/config/db.php',
    ],
    'params' => require $root . '/config/params.php',
];
