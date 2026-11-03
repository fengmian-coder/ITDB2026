<?php
/**
 * WorldCup Journey - 数据库配置加载入口
 * 模块：公共骨架；负责人：B；依赖：db.example.php、可选 db.local.php
 * 最近修改：2026-11-03
 */

$config = require __DIR__ . '/db.example.php';
$localFile = __DIR__ . '/db.local.php';

return is_file($localFile) ? array_replace($config, require $localFile) : $config;
