<?php
/**
 * WorldCup Journey - 使用 C 的唯一建表文件初始化本地数据库
 * 模块：C 数据库与 D 部署；负责人：C/D，B 协助实现
 * 依赖：MySQL 8.0.16+、data/install.sql、现有 Yii 数据库配置
 * 最近修改：2026-11-11
 */
declare(strict_types=1);

namespace app\services;

use PDO;
use RuntimeException;
use Yii;

class DatabaseSetupService
{
    /** 已有完整库保留，首次空库还原；本命令中断留下的表按检查点继续。 */
    public function prepare(): array
    {
        $db = Yii::$app->db;
        if (!str_starts_with($db->dsn, 'mysql:')
            || !preg_match('/(?:^|;)dbname=([A-Za-z0-9_]{1,64})(?:;|$)/D', substr($db->dsn, 6), $match)) {
            throw new RuntimeException('需要配置 MySQL DSN，库名仅允许 1—64 个字母、数字或下划线。');
        }
        $database = $match[1];
        $serverDsn = preg_replace('/(?:^|;)dbname=[^;]*/', '', substr($db->dsn, 6));
        $pdo = new PDO('mysql:' . ltrim($serverDsn, ';'), $db->username, $db->password,
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
        $version = (string) $pdo->query('SELECT VERSION()')->fetchColumn();
        if (str_contains($version, 'MariaDB') || !preg_match('/^8\.0\.(\d+)/', $version, $parts)
            || (int) $parts[1] < 16) {
            throw new RuntimeException('项目需要 MySQL 8.0.16 或更新的 8.0 补丁版本。');
        }
        $lock = 'wcj-setup-' . substr(hash('sha256', $database), 0, 40);
        $statement = $pdo->prepare('SELECT GET_LOCK(?, 15)');
        $statement->execute([$lock]);
        if ((int) $statement->fetchColumn() !== 1) {
            throw new RuntimeException('另一个初始化正在运行，请稍后重试。');
        }
        try {
            return $this->restoreSchema($pdo, $database, $db->dsn);
        } finally {
            $statement = $pdo->prepare('SELECT RELEASE_LOCK(?)');
            $statement->execute([$lock]);
        }
    }

    private function restoreSchema(PDO $pdo, string $database, string $dsn): array
    {
        $sql = file_get_contents(Yii::getAlias('@app/data/install.sql'));
        if ($sql === false) {
            throw new RuntimeException('无法读取 C 的 data/install.sql。');
        }
        preg_match_all('/CREATE TABLE `([a-z_]+)` \([\s\S]*?\) ENGINE=InnoDB[^;]*;/', $sql, $statements, PREG_SET_ORDER);
        $definitions = [];
        foreach ($statements as $statement) {
            $definitions[$statement[1]] = $statement[0];
        }
        if (count($definitions) !== 15) {
            throw new RuntimeException('C 建表文件应包含 15 张表，请检查当前版本。');
        }
        $statement = $pdo->prepare('SELECT SCHEMA_NAME FROM information_schema.SCHEMATA WHERE SCHEMA_NAME = ?');
        $statement->execute([$database]);
        if ($statement->fetchColumn() === false) {
            $pdo->exec('CREATE DATABASE `' . $database . '` CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci');
        }
        $pdo->exec('USE `' . $database . '`');
        $tables = $pdo->query('SHOW TABLES')->fetchAll(PDO::FETCH_COLUMN);
        if (array_diff($tables, array_keys($definitions)) !== []) {
            throw new RuntimeException('目标库包含项目之外的表，请为本项目配置独立数据库。');
        }
        $checkpoint = Yii::getAlias('@runtime/deploy-schema-' . hash('sha256', $dsn) . '.json');
        $fingerprint = hash('sha256', $sql);
        $saved = is_file($checkpoint) ? json_decode(file_get_contents($checkpoint), true) : null;
        if ($tables !== [] && count($tables) < 15 && ($saved['schema_sha256'] ?? null) !== $fingerprint) {
            throw new RuntimeException('目标库已有不完整表结构，缺少本次还原检查点，请先核对 C 的建表版本。');
        }
        $created = [];
        if (count($tables) < 15) {
            if (file_put_contents($checkpoint, json_encode(['schema_sha256' => $fingerprint]), LOCK_EX) === false) {
                throw new RuntimeException('无法保存初始化检查点，请检查 runtime 写权限。');
            }
            $pdo->exec('SET NAMES utf8mb4');
            $pdo->exec("SET time_zone = '+00:00'");
            foreach ($definitions as $table => $definition) {
                if (!in_array($table, $tables, true)) {
                    $pdo->exec($definition);
                    $created[] = $table;
                }
            }
        }
        if (is_file($checkpoint)) {
            unlink($checkpoint);
        }
        Yii::$app->db->schema->refresh();
        return ['database' => $database, 'tables' => 15, 'created_tables' => $created];
    }
}
