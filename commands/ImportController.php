<?php
/**
 * WorldCup Journey - CSV 导入控制台入口
 * 模块：B；负责人：B；依赖：MatchDataImportService、C 的正式表结构
 * 最近修改：2026-11-11
 */
declare(strict_types=1);

namespace app\commands;

use app\services\MatchDataImportService;
use Throwable;
use Yii;
use yii\console\Controller;
use yii\console\ExitCode;

class ImportController extends Controller
{
    public bool $dryRun = false;

    public function options($actionID): array
    {
        return array_merge(parent::options($actionID), ['dryRun']);
    }

    /** php yii import/world-cup [CSV目录] --dryRun=1 */
    public function actionWorldCup(?string $directory = null): int
    {
        try {
            $result = (new MatchDataImportService())->import($directory ?? Yii::getAlias('@app/data/import'), $this->dryRun);
            $this->stdout(json_encode($result, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) . PHP_EOL);
            return ExitCode::OK;
        } catch (Throwable $exception) {
            $this->stderr($exception->getMessage() . PHP_EOL);
            return ExitCode::DATAERR;
        }
    }
}
