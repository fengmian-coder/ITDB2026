<?php
/** WorldCup Journey - Advanced 前台入口。 */
declare(strict_types=1);

require_once __DIR__ . '/../../common/config/bootstrap.php';
(new yii\web\Application(require __DIR__ . '/../config/main.php'))->run();
