<?php
/**
 * WorldCup Journey - 无密码数据库配置示例
 * 模块：公共骨架；负责人：B；依赖：MySQL 8.0、pdo_mysql
 * 最近修改：2026-11-03
 */

return [
    'class' => \yii\db\Connection::class,
    'dsn' => 'mysql:host=127.0.0.1;port=3306;dbname=worldcup_journey',
    'username' => '', // 仅在被 Git 忽略的 db.local.php 中填写自己的账号。
    'password' => '',
    'charset' => 'utf8mb4',
    'attributes' => [PDO::ATTR_EMULATE_PREPARES => false],
    'on afterOpen' => static function ($event): void {
        $event->sender->createCommand("SET time_zone = '+00:00'")->execute();
    },
];
