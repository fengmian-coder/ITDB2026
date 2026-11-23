<?php
/** WorldCup Journey - 兼容单端口入口和隔离数据库测试的应用选择。 */
declare(strict_types=1);

Yii::setAlias('@common', dirname(__DIR__) . '/common');
$application = \common\components\ApplicationUrlManager::isBackendRoute($_GET['r'] ?? null)
    ? 'backend'
    : 'frontend';

return require dirname(__DIR__) . '/' . $application . '/config/main.php';
