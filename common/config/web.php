<?php
/** WorldCup Journey - 前后台共用的 Web 组件与本机部署参数。 */
declare(strict_types=1);

$config = require __DIR__ . '/main.php';
$root = dirname(__DIR__, 2);
$config['bootstrap'][] = static function () use ($root): void {
    Yii::setAlias('@webroot', $root . '/web');
    $assetUrl = getenv('WCJ_ASSET_URL');
    if ($assetUrl !== false && $assetUrl !== '') {
        Yii::setAlias('@web', rtrim($assetUrl, '/'));
    }
};
$config['layoutPath'] = $root . '/views/layouts';
$config['container']['singletons'][\yii\mail\MailerInterface::class] = [
    'class' => \yii\symfonymailer\Mailer::class,
    'useFileTransport' => true,
    'viewPath' => '@app/mail',
];
$config['components'] += [
    'request' => [
        'cookieValidationKey' => $config['params']['cookieValidationKey'] ?? '',
        'enableCsrfValidation' => true,
    ],
    'user' => [
        'identityClass' => \app\models\AdminUser::class,
        'enableAutoLogin' => false,
        'idParam' => '__worldcup_admin_id',
        'loginUrl' => ['/auth/login'],
    ],
    'errorHandler' => ['errorAction' => 'site/error'],
    'mailer' => \yii\mail\MailerInterface::class,
    'log' => [
        'traceLevel' => YII_DEBUG ? 3 : 0,
        'targets' => [[
            'class' => \yii\log\FileTarget::class,
            'levels' => ['error', 'warning'],
            'logVars' => ['_GET'],
        ]],
    ],
    'urlManager' => [
        'class' => \common\components\ApplicationUrlManager::class,
        'frontendEntry' => getenv('WCJ_FRONTEND_ENTRY') ?: null,
        'backendEntry' => getenv('WCJ_BACKEND_ENTRY') ?: null,
    ],
];

if (YII_ENV_DEV) {
    $config['bootstrap'][] = 'debug';
    $config['modules']['debug'] = ['class' => \yii\debug\Module::class];
    $config['bootstrap'][] = 'gii';
    $config['modules']['gii'] = ['class' => \yii\gii\Module::class];
}

return $config;
