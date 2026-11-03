<?php
/**
 * WorldCup Journey - 初始化本机配置，保留已有文件
 * 模块：公共骨架；负责人：B；依赖：Yii 2.0、config/db.example.php
 * 最近修改：2026-11-03
 */

declare(strict_types=1);

namespace app\commands;

use Yii;
use yii\console\Controller;
use yii\console\ExitCode;

class BootstrapController extends Controller
{
    public function actionIndex(): int
    {
        $configDir = Yii::getAlias('@app/config');
        $this->createOnce($configDir . '/db.local.php', file_get_contents($configDir . '/db.example.php'));
        $paramsFile = $configDir . '/params-local.php';
        if (!is_file($paramsFile)) {
            $key = Yii::$app->security->generateRandomString(64);
            $contents = "<?php\n/**\n * WorldCup Journey - 本机私有参数，禁止提交\n"
                . " * 模块：公共骨架；负责人：B；依赖：Yii Security\n"
                . " * 最近修改：" . gmdate('Y-m-d') . "\n */\n\nreturn "
                . var_export(['cookieValidationKey' => $key], true) . ";\n";
            $this->createOnce($paramsFile, $contents);
        }
        $params = require $paramsFile;
        if (empty($params['cookieValidationKey'])) {
            $this->stderr("本地 cookieValidationKey 为空，请在 config/params-local.php 中设置随机密钥。\n");
            return ExitCode::CONFIG;
        }
        $this->stdout("本地配置已就绪，已有文件保持原样。请在 config/db.local.php 填写本机数据库账号。\n");
        return ExitCode::OK;
    }

    private function createOnce(string $path, string $contents): void
    {
        if (is_file($path)) {
            return;
        }
        $handle = fopen($path, 'x');
        if ($handle === false) {
            throw new \RuntimeException('无法创建本地配置文件：' . basename($path));
        }
        try {
            if (fwrite($handle, $contents) !== strlen($contents)) {
                throw new \RuntimeException('无法完整写入本地配置文件。');
            }
        } finally {
            fclose($handle);
        }
    }
}
