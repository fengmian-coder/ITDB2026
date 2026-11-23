<?php
/**
 * WorldCup Journey - 本机部署的兼容入口，按路由启动 Advanced 应用。
 */

declare(strict_types=1);

require __DIR__ . '/../common/config/bootstrap.php';
require \common\components\ApplicationUrlManager::isBackendRoute($_GET['r'] ?? null)
    ? __DIR__ . '/../backend/web/index.php'
    : __DIR__ . '/../frontend/web/index.php';
