<?php
/**
 * WorldCup Journey - 球队比赛历程页面控制器
 * 模块：B 赛事与比赛后端
 * 负责人：B
 * 依赖：JourneyService、views/journey/view.php、Yii 2.0 Controller
 * 球队资料及参赛资格通过 JourneyService 读取 C 的 Model
 * 最近修改：2026-11-11
 */

declare(strict_types=1);

namespace app\controllers;

use app\services\JourneyService;
use app\services\TeamDirectoryService;
use yii\filters\VerbFilter;
use yii\web\BadRequestHttpException;
use yii\web\Controller;

/**
 * 处理请求和渲染，不重复查询球队、计算比分或推断最终成绩。
 * 当前 Service 的 [] 仅代表未查到比赛，不能据此抛球队不存在的 404。
 * Service 的 400/404/500 及其他异常均交 Yii 统一处理。
 */
class JourneyController extends Controller
{
    public function __construct(
        $id,
        $module,
        private readonly JourneyService $journeyService,
        private readonly TeamDirectoryService $teamDirectoryService,
        $config = [],
    ) {
        parent::__construct($id, $module, $config);
    }

    public function behaviors(): array
    {
        return [
            'verbs' => [
                'class' => VerbFilter::class,
                'actions' => ['view' => ['GET', 'HEAD']],
            ],
        ];
    }

    /**
     * GET journey/view?team_id=...
     *
     * Yii 只将对应的 team_id 路由参数绑定到本 Action，不把整个 GET 传入 Service。
     * mixed 保留数组、浮点数等原始类型，避免隐式强转绕过 ID 校验。
     *
     * View 参数：teamId 为校验后的 int；journey 为 Service 返回的完整历程数组。
     * 名称缺失、赛程状态、比分和精选标记均原样保留，供后续 View 展示。
     */
    public function actionView(mixed $team_id): string
    {
        $teamId = $this->positiveTeamId($team_id);
        $journey = $this->journeyService->getTeamJourney($teamId);
        $team = $this->journeyService->getTeamProfile($teamId);

        return $this->render('view', [
            'teamId' => $teamId,
            'journey' => $journey,
            'team' => $team,
            'teams' => $this->teamDirectoryService->getTeams(true),
        ]);
    }

    private function positiveTeamId(mixed $value): int
    {
        if ((!is_int($value) && !is_string($value)) || !preg_match('/^[0-9]+$/D', (string) $value)) {
            throw new BadRequestHttpException('球队 ID 必须为正整数');
        }

        $digits = ltrim((string) $value, '0');
        $maximum = (string) PHP_INT_MAX;
        if ($digits === '' || strlen($digits) > strlen($maximum)
            || (strlen($digits) === strlen($maximum) && strcmp($digits, $maximum) > 0)) {
            throw new BadRequestHttpException('球队 ID 超出有效范围');
        }

        return (int) $digits;
    }
}
