<?php
/**
 * WorldCup Journey - 球队档案增量升级及首次资料登记
 * 模块：A/C 资料联调；负责人：A；依赖：TeamProfileService
 * 最近修改：2026-11-12
 */
declare(strict_types=1);
namespace app\commands;
use app\services\TeamProfileService;
use yii\console\Controller;
class TeamProfileController extends Controller
{
    public function actionPrepare(): int
    {
        $service = new TeamProfileService();
        $service->prepareSchema();
        $this->stdout(json_encode($service->importDefaults(), JSON_UNESCAPED_UNICODE) . "\n");
        return 0;
    }
}
