<?php
/**
 * WorldCup Journey - 世界杯动态图表总览
 * 模块：C 球队与统计；负责人：C，B 协助实现
 * 依赖：TeamStatsService、MatchQueryService、insight/index
 * 最近修改：2026-11-11
 */
declare(strict_types=1);

namespace app\controllers;

use app\services\MatchQueryService;
use app\services\JourneyFlowService;
use app\services\TeamStatsService;
use yii\filters\VerbFilter;
use yii\web\Controller;

class InsightController extends Controller
{
    public function __construct(
        $id,
        $module,
        private readonly TeamStatsService $teamStatsService,
        private readonly MatchQueryService $matchQueryService,
        private readonly JourneyFlowService $journeyFlowService,
        $config = [],
    ) {
        parent::__construct($id, $module, $config);
    }

    public function behaviors(): array
    {
        return ['verbs' => [
            'class' => VerbFilter::class,
            'actions' => ['index' => ['GET', 'HEAD']],
        ]];
    }

    public function actionIndex(): string
    {
        return $this->render('index', [
            'ranking' => $this->teamStatsService->getScoringRanking(),
            'stages' => $this->matchQueryService->getStageSummary(),
            'flow' => $this->journeyFlowService->getFlow(),
        ]);
    }
}
