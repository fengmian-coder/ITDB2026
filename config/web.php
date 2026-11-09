<?php
/**
 * WorldCup Journey - Web 应用公共配置（基于官方 Basic 模板）
 * 模块：公共骨架；负责人：B；依赖：params.php、db.php、Yii 2.0
 * 最近修改：2026-11-03
 */

$params = require __DIR__ . '/params.php';
$db = require __DIR__ . '/db.php';

$config = [
    'id' => 'worldcup-journey',
    'name' => 'WorldCup Journey',
    'timeZone' => 'UTC',
    'basePath' => dirname(__DIR__),
    'bootstrap' => ['log'],
    'container' => [
        'singletons' => [
            \yii\mail\MailerInterface::class => [
                'class' => \yii\symfonymailer\Mailer::class,
                // send all mails to a file by default.
                'useFileTransport' => true,
                'viewPath' => '@app/mail',
            ],
        ],
    ],
    'aliases' => [
        '@bower' => '@vendor/bower-asset',
        '@npm'   => '@vendor/npm-asset',
    ],
    'controllerMap' => [
        'admin-tournament' => [
            'class' => \app\controllers\AdminTournamentController::class,
            'adminAccessChecker' => [\app\models\AdminUser::class, 'canManage'],
        ],
        'admin-match' => [
            'class' => \app\controllers\AdminMatchController::class,
            'adminAccessChecker' => [\app\models\AdminUser::class, 'canManage'],
        ],
        'admin-stadium' => [
            'class' => \app\controllers\AdminStadiumController::class,
            'adminAccessChecker' => [\app\models\AdminUser::class, 'canManage'],
        ],
        'admin-story' => [
            'class' => \app\controllers\AdminStoryController::class,
            'adminAccessChecker' => [\app\models\AdminUser::class, 'canManage'],
        ],
    ],
    'components' => [
        'request' => [
            'cookieValidationKey' => $params['cookieValidationKey'] ?? '',
            'enableCsrfValidation' => true,
        ],
        'cache' => [
            'class' => \yii\caching\FileCache::class,
        ],
        'user' => [
            'identityClass' => \app\models\AdminUser::class,
            'enableAutoLogin' => false,
            'idParam' => '__worldcup_admin_id',
            'loginUrl' => ['/auth/login'],
        ],
        'errorHandler' => [
            'errorAction' => 'site/error',
        ],
        'mailer' => \yii\mail\MailerInterface::class,
        'log' => [
            'traceLevel' => YII_DEBUG ? 3 : 0,
            'targets' => [
                [
                    'class' => \yii\log\FileTarget::class,
                    'levels' => ['error', 'warning'],
                    'logVars' => ['_GET'],
                ],
            ],
        ],
        'db' => $db,
        'urlManager' => [
            'enablePrettyUrl' => false,
            'showScriptName' => true,
        ],
    ],
    'params' => $params,
];

if (YII_ENV_DEV) {
    // configuration adjustments for 'dev' environment
    $config['bootstrap'][] = 'debug';
    $config['modules']['debug'] = [
        'class' => \yii\debug\Module::class,
        // uncomment the following to add your IP if you are not connecting from localhost.
        //'allowedIPs' => ['127.0.0.1', '::1'],
    ];

    $config['bootstrap'][] = 'gii';
    $config['modules']['gii'] = [
        'class' => \yii\gii\Module::class,
        // uncomment the following to add your IP if you are not connecting from localhost.
        //'allowedIPs' => ['127.0.0.1', '::1'],
    ];
}

return $config;
