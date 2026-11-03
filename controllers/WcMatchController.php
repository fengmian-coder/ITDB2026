<?php
/**
 * WorldCup Journey - 比赛列表与公开详情控制器
 * 模块：B 赛事与比赛后端
 * 负责人：B
 * 依赖：MatchQueryService、Yii 2.0 Controller；比赛 View 待下一阶段实现
 * 最近修改：2026-11-03
 */

declare(strict_types=1);

namespace app\controllers;

use app\services\MatchQueryService;
use yii\filters\VerbFilter;
use yii\web\BadRequestHttpException;
use yii\web\Controller;

/**
 * 只读取请求、调用 Service 并渲染；比分、发布过滤和数据库查询均由 Service 负责。
 * 不捕获并掩盖 HTTP 或服务器异常，交给 Yii 统一处理。
 */
class WcMatchController extends Controller
{
    private const FILTER_KEYS = ['stage', 'team_id', 'page', 'page_size'];

    public function __construct(
        $id,
        $module,
        private readonly MatchQueryService $matchQueryService,
        $config = [],
    ) {
        parent::__construct($id, $module, $config);
    }

    public function behaviors(): array
    {
        return [
            'verbs' => [
                'class' => VerbFilter::class,
                'actions' => [
                    'index' => ['GET', 'HEAD'],
                    'view' => ['GET', 'HEAD'],
                ],
            ],
        ];
    }

    /**
     * wc-match/index → views/wc-match/index.php（本阶段不创建该文件）。
     * View 参数：matches 为 Service 的 items，pagination 原样传递。
     * filters 含四个白名单键；阶段/球队保留有效输入，页码/页大小使用 Service 返回值。
     */
    public function actionIndex(): string
    {
        // 保留参数是否存在及原始类型，包括显式传入的 null，由 Service 统一校验。
        $filters = array_intersect_key($this->request->getQueryParams(), array_flip(self::FILTER_KEYS));
        $result = $this->matchQueryService->getMatchList($filters);

        return $this->render('index', [
            'matches' => $result['items'],
            'pagination' => $result['pagination'],
            'filters' => [
                'stage' => $filters['stage'] ?? null,
                'team_id' => $filters['team_id'] ?? null,
                'page' => $result['pagination']['page'],
                'page_size' => $result['pagination']['page_size'],
            ],
        ]);
    }

    /**
     * wc-match/view?id=... → views/wc-match/view.php（本阶段不创建该文件）。
     * View 参数 match 为 Service 的完整详情数组，不在此重新处理故事、事件或标签。
     * mixed 用于接收原始路由参数，防止隐式类型转换绕过 ID 校验。
     */
    public function actionView(mixed $id): string
    {
        $matchId = $this->positiveMatchId($id);
        $match = $this->matchQueryService->getMatchDetail($matchId);

        return $this->render('view', ['match' => $match]);
    }

    /** Service 接收 int；超出 PHP 整数范围的 ID 返回 400，不能截断后查询另一场比赛。 */
    private function positiveMatchId(mixed $id): int
    {
        if ((!is_int($id) && !is_string($id)) || !preg_match('/^[0-9]+$/D', (string) $id)) {
            throw new BadRequestHttpException('比赛 ID 必须为正整数。');
        }

        $digits = ltrim((string) $id, '0');
        $maximum = (string) PHP_INT_MAX;
        if ($digits === '' || strlen($digits) > strlen($maximum)
            || (strlen($digits) === strlen($maximum) && strcmp($digits, $maximum) > 0)) {
            throw new BadRequestHttpException('比赛 ID 超出有效范围。');
        }

        return (int) $digits;
    }
}
