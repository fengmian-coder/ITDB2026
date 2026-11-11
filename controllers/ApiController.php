<?php
/**
 * WorldCup Journey - 只读统计 JSON 统一入口
 * 模块：B 赛事与比赛后端；负责人：B
 * 依赖：MatchQueryService、C 的 TeamStatsService
 * 最近修改：2026-11-11
 */
declare(strict_types=1);

namespace app\controllers;

use app\services\MatchQueryService;
use Yii;
use yii\base\InvalidRouteException;
use yii\filters\VerbFilter;
use yii\web\BadRequestHttpException;
use yii\web\Controller;
use yii\web\HttpException;
use yii\web\NotFoundHttpException;
use yii\web\Response;
use yii\web\ServerErrorHttpException;

/** 只包装已有服务结果，不重复球队统计 SQL。 */
class ApiController extends Controller
{
    /** 仅供服务器端 DI 配置；不从 GET/POST 加载，不提供替代球队数据。 */
    public ?object $teamStatsService = null;

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
        return ['verbs' => [
            'class' => VerbFilter::class,
            'actions' => ['team-stats' => ['GET'], 'team-compare' => ['GET'], 'insights' => ['GET']],
        ]];
    }

    /**
     * 覆盖范围包括参数绑定、VerbFilter 和 Service 异常，失败同样输出 JSON。
     * HTTP 错误保留真实状态；非预期异常记录日志并返回友好 500，不泄露 SQL/路径。
     */
    public function runAction($id, $params = [])
    {
        $this->response->format = Response::FORMAT_JSON;
        try {
            return parent::runAction($id, $params);
        } catch (InvalidRouteException $exception) {
            return $this->failure(404, 'NOT_FOUND', '未找到该接口');
        } catch (HttpException $exception) {
            $status = $exception->statusCode;
            if ($status >= 500) {
                Yii::error(['operation' => $id, 'exception' => $exception], self::class);
                return $this->failure($status, 'SERVER_ERROR', '统计服务暂时不可用，请稍后重试');
            }
            $code = match ($status) {
                400 => 'INVALID_PARAMETER', 403 => 'FORBIDDEN',
                404 => in_array($id, ['team-stats', 'team-compare'], true) ? 'TEAM_NOT_FOUND' : 'NOT_FOUND',
                405 => 'METHOD_NOT_ALLOWED', default => 'REQUEST_FAILED',
            };
            $message = match ($status) {
                400 => '请求参数格式无效', 403 => '无权访问该接口',
                404 => $code === 'TEAM_NOT_FOUND' ? '未找到该球队或其 2026 世界杯参赛记录' : '未找到请求的数据',
                405 => '接口仅允许 GET 请求', default => '请求未能完成',
            };
            return $this->failure($status, $code, $message);
        } catch (\Throwable $exception) {
            Yii::error(['operation' => $id, 'exception' => $exception], self::class);
            return $this->failure(500, 'SERVER_ERROR', '统计服务暂时不可用，请稍后重试');
        }
    }

    public function actionInsights(mixed $chart = null): array
    {
        return match ($chart) {
            'stage-goals' => $this->success($this->matchQueryService->getStageSummary()),
            'scoring-ranking' => $this->success($this->getTeamStatsService()->getScoringRanking()),
            'journey-flow' => $this->success(Yii::createObject(\app\services\JourneyFlowService::class)->getFlow()),
            default => $this->failure(400, 'INVALID_CHART', '支持 chart=stage-goals、scoring-ranking 或 journey-flow'),
        };
    }

    public function actionTeamStats(mixed $id = null): array
    {
        $teamId = $this->positiveId($id);
        $data = $this->getTeamStatsService()->getTeamStats($teamId);
        if ($data === []) {
            throw new NotFoundHttpException('未找到球队或参赛记录');
        }
        if (!is_array($data)) {
            throw new ServerErrorHttpException('球队统计返回结构无效');
        }
        return $this->success($data);
    }

    public function actionTeamCompare(mixed $left_id = null, mixed $right_id = null): array
    {
        $leftId = $this->positiveId($left_id);
        $rightId = $this->positiveId($right_id);
        if ($leftId === $rightId) {
            throw new BadRequestHttpException('请选择两支不同的球队');
        }
        $data = $this->getTeamStatsService()->getCompareStats($leftId, $rightId);
        if ($data === []) {
            throw new NotFoundHttpException('未找到球队或参赛记录');
        }
        if (!is_array($data) || !isset($data['left'], $data['right'])
            || !is_array($data['left']) || !is_array($data['right'])
            || $data['left'] === [] || $data['right'] === []) {
            throw new ServerErrorHttpException('球队对比返回结构无效');
        }
        return $this->success($data);
    }

    protected function getTeamStatsService(): object
    {
        if ($this->teamStatsService !== null) {
            return $this->teamStatsService;
        }
        $class = 'app\\services\\TeamStatsService';
        if (!class_exists($class)) {
            throw new ServerErrorHttpException('球队统计依赖尚未交付');
        }
        return $this->teamStatsService = Yii::createObject($class);
    }

    private function positiveId(mixed $value): int
    {
        if ((!is_int($value) && !is_string($value)) || !preg_match('/^[0-9]+$/D', (string) $value)) {
            throw new BadRequestHttpException('球队 ID 必须为正整数');
        }
        $digits = ltrim((string) $value, '0');
        $max = (string) PHP_INT_MAX;
        if ($digits === '' || strlen($digits) > strlen($max)
            || (strlen($digits) === strlen($max) && strcmp($digits, $max) > 0)) {
            throw new BadRequestHttpException('球队 ID 超出有效范围');
        }
        return (int) $digits;
    }

    private function success(array $data): array
    {
        $this->response->statusCode = 200;
        return ['success' => true, 'data' => $data, 'error' => null];
    }

    private function failure(int $status, string $code, string $message): array
    {
        $this->response->statusCode = $status;
        return ['success' => false, 'data' => null, 'error' => compact('code', 'message')];
    }
}
