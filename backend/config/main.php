<?php
/** WorldCup Journey - Yii2 Advanced 管理后台应用配置。 */
declare(strict_types=1);

$config = require __DIR__ . '/../../common/config/web.php';
$config['id'] = 'worldcup-journey-backend';
$config['basePath'] = dirname(__DIR__);
$config['controllerNamespace'] = 'backend\controllers';
$config['defaultRoute'] = 'admin-dashboard/index';
foreach ([
    'admin-tournament' => \backend\controllers\AdminTournamentController::class,
    'admin-match' => \backend\controllers\AdminMatchController::class,
    'admin-stadium' => \backend\controllers\AdminStadiumController::class,
    'admin-story' => \backend\controllers\AdminStoryController::class,
] as $id => $class) {
    $config['controllerMap'][$id] = [
        'class' => $class,
        'adminAccessChecker' => [\app\models\AdminUser::class, 'canManage'],
    ];
}

return $config;
