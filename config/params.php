<?php
/**
 * WorldCup Journey - 公共参数与本地参数加载
 * 模块：公共骨架；负责人：B；依赖：可选 params-local.php
 * 最近修改：2026-11-03
 */

$params = [
    'adminEmail' => 'admin@example.com',
    'senderEmail' => 'noreply@example.com',
    'senderName' => 'WorldCup Journey',
];

$localFile = __DIR__ . '/params-local.php';
return is_file($localFile) ? array_replace($params, require $localFile) : $params;
