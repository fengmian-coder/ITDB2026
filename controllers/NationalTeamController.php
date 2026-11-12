<?php
/**
 * WorldCup Journey - 国家队列表、DNA 详情与球队 PK 控制器
 * 模块：C 球队与统计
 * 负责人：C，B 协助实现 PK
 * 依赖：NationalTeam、Tournament、TournamentTeam、TeamStatsService、TeamDirectoryService
 * 最近修改：2026-11-11
 */

declare(strict_types=1);

namespace app\controllers;

use app\models\NationalTeam;
use app\models\Tournament;
use app\models\TournamentTeam;
use app\services\TeamDirectoryService;
use app\services\TeamStatsService;
use yii\data\ActiveDataProvider;
use yii\filters\VerbFilter;
use yii\web\BadRequestHttpException;
use yii\web\Controller;
use yii\web\NotFoundHttpException;

/**
 * 公开只读页面。列表只展示启用球队；详情按 ID 读取，停用球队仍可用于历史链接。
 * 统计值全部来自 TeamStatsService，不在 Controller/View 重复计算。
 */
class NationalTeamController extends Controller
{
    private const PAGE_SIZE = 20;
    private const TOURNAMENT_YEAR = 2026;

    public function __construct(
        $id,
        $module,
        private readonly TeamStatsService $teamStatsService,
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
                'actions' => [
                    'index' => ['GET', 'HEAD'],
                    'view' => ['GET', 'HEAD'],
                    'compare' => ['GET', 'HEAD'],
                ],
            ],
        ];
    }

    /**
     * GET national-team/index?q=&page=
     * q 为空时不筛选；page 默认 1，超出结果范围返回 400，不静默钳制。
     */
    public function actionIndex(): string
    {
        $q = $this->request->get('q');
        if ($q !== null && !is_string($q)) {
            throw new BadRequestHttpException('搜索关键词格式无效。');
        }
        $q = trim((string) $q);

        $query = NationalTeam::findActive()
            ->andWhere(['id' => TournamentTeam::find()->alias('tt')
                ->select('tt.team_id')
                ->innerJoin(['t' => Tournament::tableName()], '[[t.id]] = [[tt.tournament_id]]')
                ->where(['t.year' => self::TOURNAMENT_YEAR])])
            ->orderBy(['name_zh' => SORT_ASC, 'id' => SORT_ASC]);

        if ($q !== '') {
            $query->andWhere(['or',
                ['like', 'name_zh', $q],
                ['like', 'name_en', $q],
                ['like', 'fifa_code', strtoupper($q)],
            ]);
        }

        $total = (int) (clone $query)->count();
        $page = $this->normalizePage($this->request->get('page'), $total);

        $provider = new ActiveDataProvider([
            'query' => $query,
            'pagination' => [
                'pageSize' => self::PAGE_SIZE,
                'page' => $page - 1,
                'totalCount' => $total,
                'params' => ['q' => $q],
            ],
            'sort' => false,
        ]);

        return $this->render('index', [
            'dataProvider' => $provider,
            'q' => $q,
        ]);
    }

    /**
     * GET national-team/view?id=
     * 球队不存在返回 404；未参加 2026 由 TeamStatsService 返回 404。
     */
    public function actionView(mixed $id): string
    {
        $teamId = $this->positiveId($id);
        $team = NationalTeam::findOne(['id' => $teamId]);
        if ($team === null) {
            throw new NotFoundHttpException('未找到该球队。');
        }

        return $this->render('view', [
            'team' => $team,
            'stats' => $this->teamStatsService->getTeamStats($teamId),
            'participation' => $this->findParticipation($teamId),
        ]);
    }

    /** GET national-team/compare?left_id=&right_id=，允许先选择一支球队。 */
    public function actionCompare(mixed $left_id = null, mixed $right_id = null): string
    {
        $leftId = $left_id === null || $left_id === '' ? null : $this->positiveId($left_id);
        $rightId = $right_id === null || $right_id === '' ? null : $this->positiveId($right_id);
        $comparison = null;
        if ($leftId !== null && $rightId !== null) {
            $comparison = $this->teamStatsService->getCompareStats($leftId, $rightId);
        } elseif ($leftId !== null || $rightId !== null) {
            // 单选深链接同样核对本届参赛资格，保留 Service 的 404 行为。
            $this->teamStatsService->getTeamStats($leftId ?? $rightId);
        }

        return $this->render('compare', [
            'teams' => $this->teamDirectoryService->getTeams(true),
            'leftId' => $leftId,
            'rightId' => $rightId,
            'comparison' => $comparison,
        ]);
    }

    /** @return array{group_code: string, final_result_code: string|null}|null */
    private function findParticipation(int $teamId): ?array
    {
        return TournamentTeam::find()
            ->alias('tt')
            ->innerJoin(['t' => Tournament::tableName()], '[[t.id]] = [[tt.tournament_id]]')
            ->select(['tt.group_code', 'tt.final_result_code'])
            ->where(['tt.team_id' => $teamId, 't.year' => self::TOURNAMENT_YEAR])
            ->asArray()
            ->one();
    }

    private function normalizePage(mixed $value, int $total): int
    {
        if ($value === null || $value === '') {
            return 1;
        }
        if ((!is_int($value) && !is_string($value))
            || !preg_match('/^[0-9]+$/D', (string) $value)) {
            throw new BadRequestHttpException('页码必须为正整数。');
        }

        $digits = ltrim((string) $value, '0');
        $maximum = (string) PHP_INT_MAX;
        if ($digits === '' || strlen($digits) > strlen($maximum)
            || (strlen($digits) === strlen($maximum) && strcmp($digits, $maximum) > 0)) {
            throw new BadRequestHttpException('页码超出有效范围。');
        }

        $page = (int) $digits;
        $pageCount = intdiv($total, self::PAGE_SIZE) + (int) ($total % self::PAGE_SIZE !== 0);
        if ($page > max(1, $pageCount)) {
            throw new BadRequestHttpException('请求页码超出查询结果范围。');
        }

        return $page;
    }

    private function positiveId(mixed $value): int
    {
        if ((!is_int($value) && !is_string($value))
            || !preg_match('/^[0-9]+$/D', (string) $value)) {
            throw new BadRequestHttpException('球队 ID 必须为正整数。');
        }

        $digits = ltrim((string) $value, '0');
        $maximum = (string) PHP_INT_MAX;
        if ($digits === '' || strlen($digits) > strlen($maximum)
            || (strlen($digits) === strlen($maximum) && strcmp($digits, $maximum) > 0)) {
            throw new BadRequestHttpException('球队 ID 超出有效范围。');
        }

        return (int) $digits;
    }
}
