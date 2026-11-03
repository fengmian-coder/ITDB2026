<?php
/**
 * WorldCup Journey - 比赛列表、公开详情与阶段统计查询服务
 * 模块：B 赛事与比赛后端
 * 负责人：B
 * 依赖：Yii 2.0、B 模块七个 ActiveRecord、C 提供的正式数据库结构
 * 待集成：C 的球队名称、徽标与球队存在性验证；目前只返回球队 ID
 * 最近修改：2026-11-03
 */

declare(strict_types=1);

namespace app\services;

use app\models\MatchEvent;
use app\models\MatchStory;
use app\models\MatchStoryTag;
use app\models\MatchTag;
use app\models\Stadium;
use app\models\Tournament;
use app\models\WcMatch;
use Yii;
use yii\db\ActiveQuery;
use yii\db\Exception as DatabaseException;
use yii\db\Expression;
use yii\web\BadRequestHttpException;
use yii\web\NotFoundHttpException;
use yii\web\ServerErrorHttpException;

/**
 * 仅查询 2026 届，不写数据库，也不返回虚构球队资料。
 *
 * 本阶段明确的数组约定（不改写待确认的数据库字段设计）：
 * - 列表：items + pagination(page, page_size, total_count, page_count)。
 * - items 与详情共用比赛列，并附 tournament、stadium、final_score(home, away)。
 * - 详情额外包含 story、events、tags；没有已发布故事时为 null、[]、[]。
 * - kickoff_utc 保留数据库 UTC 字符串 Y-m-d H:i:s；展示时再转换时区。
 * - ID 在 PHP 整数范围内返回 int，超出范围保留十进制 string，避免截断。
 * - final_score 仅在 FINISHED 时提供最终场上比分；不含点球。
 * - 空统计返回 []，只返回有有效赛程记录的阶段，按阶段代码的业务顺序排列。
 *
 * 这些返回键名及分页边界处理须由调用方在联调时共同确认。
 */
class MatchQueryService
{
    private const TOURNAMENT_YEAR = 2026;
    private const DEFAULT_PAGE_SIZE = 20;
    private const MATCH_FIELDS = [
        'id', 'tournament_id', 'match_no', 'home_team_id', 'away_team_id', 'stadium_id',
        'kickoff_utc', 'stage_code', 'group_code', 'status', 'home_score_90', 'away_score_90',
        'home_score_120', 'away_score_120', 'home_penalties', 'away_penalties', 'winner_team_id', 'summary',
    ];
    private const TOURNAMENT_FIELDS = ['id', 'year', 'name', 'start_date', 'end_date', 'description'];
    private const STADIUM_FIELDS = ['id', 'name', 'city', 'country_code', 'timezone_name'];
    private const STORY_FIELDS = [
        'id', 'match_id', 'title', 'summary', 'body', 'cover_path', 'source_url',
        'is_published', 'created_at', 'updated_at',
    ];
    private const EVENT_FIELDS = [
        'id', 'match_id', 'team_id', 'player_name', 'minute', 'added_minute',
        'period_code', 'event_type', 'description', 'sort_order',
    ];
    private const TAG_FIELDS = ['id', 'code', 'name'];

    /**
     * stage/team_id 为 null 或空字符串时不筛选；page 默认 1，page_size 可选，默认 20。
     * 页码和页大小须为 PHP 整数范围内的正整数；超页返回 400，不静默改写页码。
     * 正整数球队 ID 无匹配时返回空列表，球队是否存在待 C 的接口就绪后再验证。
     *
     * @return array{items: array, pagination: array{page: int, page_size: int, total_count: int, page_count: int}}
     */
    public function getMatchList(array $filters = []): array
    {
        $options = $this->normalizeFilters($filters);

        try {
            $query = $this->filteredMatchQuery($options);
            // 只连接赛事父表，不连接事件或标签，计数和分页不会被一对多关系放大。
            $totalCount = (int) (clone $query)->count('m.id');
            $pagination = $this->pagination($options['page'], $options['page_size'], $totalCount);
            $rows = $totalCount === 0 ? [] : $query
                ->offset(($pagination['page'] - 1) * $pagination['page_size'])
                ->limit($pagination['page_size'])
                ->all();

            return [
                'items' => array_map(fn (array $row): array => $this->serializeMatch($row), $rows),
                'pagination' => $pagination,
            ];
        } catch (DatabaseException $exception) {
            throw $this->databaseFailure($exception, __FUNCTION__);
        }
    }

    /** 不存在或不属于 2026 届的比赛返回 404；非法 ID 返回 400。 */
    public function getMatchDetail(int $matchId): array
    {
        if ($matchId < 1) {
            throw new BadRequestHttpException('比赛 ID 必须为正整数。');
        }

        try {
            $match = $this->relatedMatchQuery()->andWhere(['m.id' => $matchId])->one();
            if ($match === null) {
                throw new NotFoundHttpException('未找到该届比赛。');
            }

            $story = $this->publishedStoryQuery($matchId)->one();
            // 在查询事件前判断发布状态；无公开故事时不读取或返回时间轴及标签。
            $events = $this->isPublishedStory($story) ? $this->eventQuery($matchId)->all() : [];

            return array_merge($this->serializeMatch($match), $this->serializePublicContent($story, $events));
        } catch (DatabaseException $exception) {
            throw $this->databaseFailure($exception, __FUNCTION__);
        }
    }

    /**
     * 返回各阶段的有效场次、完赛场次和完赛场上进球；不计点球大战。
     *
     * @return array<array{stage_code: string, match_count: int, finished_match_count: int, total_goals: int|null}>
     */
    public function getStageSummary(): array
    {
        try {
            return $this->normalizeStageSummary($this->stageSummaryQuery()->all());
        } catch (DatabaseException $exception) {
            throw $this->databaseFailure($exception, __FUNCTION__);
        }
    }

    private function baseMatchQuery(): ActiveQuery
    {
        // year 是 V1.1 已明确的赛事字段；不假定 2026 届的自增 ID 恰好为 1。
        return WcMatch::find()->alias('m')
            ->innerJoin(['t' => Tournament::tableName()], '[[t.id]] = [[m.tournament_id]]')
            ->where(['t.year' => self::TOURNAMENT_YEAR])
            ->asArray();
    }

    private function relatedMatchQuery(): ActiveQuery
    {
        return $this->baseMatchQuery()->select($this->columns('m', self::MATCH_FIELDS))->with([
            'tournament' => function (ActiveQuery $query): void {
                $query->select($this->columns(Tournament::tableName(), self::TOURNAMENT_FIELDS));
            },
            'stadium' => function (ActiveQuery $query): void {
                $query->select($this->columns(Stadium::tableName(), self::STADIUM_FIELDS));
            },
        ]);
    }

    private function filteredMatchQuery(array $options): ActiveQuery
    {
        $query = $this->relatedMatchQuery();
        if ($options['stage'] !== null) {
            $query->andWhere(['m.stage_code' => $options['stage']]);
        }
        if ($options['team_id'] !== null) {
            $query->andWhere(['or',
                ['m.home_team_id' => $options['team_id']],
                ['m.away_team_id' => $options['team_id']],
            ]);
        }
        return $query->orderBy(['m.kickoff_utc' => SORT_ASC, 'm.match_no' => SORT_ASC, 'm.id' => SORT_ASC]);
    }

    private function publishedStoryQuery(int $matchId): ActiveQuery
    {
        return MatchStory::find()->select($this->columns(MatchStory::tableName(), self::STORY_FIELDS))
            ->where(['match_id' => $matchId, 'is_published' => 1])
            ->with(['tags' => function (ActiveQuery $query): void {
                $query->select($this->columns(MatchTag::tableName(), self::TAG_FIELDS))
                    ->orderBy([MatchTag::tableName() . '.id' => SORT_ASC])
                    ->via('matchStoryTags', function (ActiveQuery $junction): void {
                        $junction->select($this->columns(MatchStoryTag::tableName(), ['story_id', 'tag_id']));
                    });
            }])->asArray();
    }

    private function eventQuery(int $matchId): ActiveQuery
    {
        // 本阶段按用户要求采用 sort_order 主排序，id 仅作为同值记录的稳定次级排序。
        return MatchEvent::find()->select($this->columns(MatchEvent::tableName(), self::EVENT_FIELDS))
            ->where(['match_id' => $matchId])
            ->orderBy(['sort_order' => SORT_ASC, 'id' => SORT_ASC])
            ->asArray();
    }

    private function stageSummaryQuery(): ActiveQuery
    {
        // 加时比分是累计比分，只选择 120 或 90 分钟，既不重复相加，也不读取点球列。
        // ELSE NULL 使没有完赛的阶段保持 NULL；真实完赛 0-0 则可汇总为 0。
        // 使用不同参数名兼容 MySQL 原生预处理，避免重复占位符。
        return $this->baseMatchQuery()->andWhere(['<>', 'm.status', 'CANCELLED'])->select([
            'stage_code' => 'm.stage_code',
            'match_count' => new Expression('COUNT(*)'),
            'finished_match_count' => new Expression(
                'SUM(CASE WHEN [[m.status]] = :finished_count THEN 1 ELSE 0 END)',
                [':finished_count' => 'FINISHED']
            ),
            'total_goals' => new Expression(
                'SUM(CASE WHEN [[m.status]] = :finished_goals THEN '
                . 'COALESCE([[m.home_score_120]], [[m.home_score_90]])'
                . ' + COALESCE([[m.away_score_120]], [[m.away_score_90]]) ELSE NULL END)',
                [':finished_goals' => 'FINISHED']
            ),
        ])->groupBy(['m.stage_code']);
    }

    private function normalizeFilters(array $filters): array
    {
        if (array_diff(array_keys($filters), ['stage', 'team_id', 'page', 'page_size']) !== []) {
            throw new BadRequestHttpException('包含不支持的比赛筛选参数。');
        }
        $stage = $filters['stage'] ?? null;
        if ($stage === '') {
            $stage = null;
        }
        if ($stage !== null && (!is_string($stage) || !in_array($stage, WcMatch::STAGE_CODES, true))) {
            throw new BadRequestHttpException('比赛阶段参数无效。');
        }
        $teamId = $filters['team_id'] ?? null;
        if ($teamId === '') {
            $teamId = null;
        }

        return [
            'stage' => $stage,
            'team_id' => $teamId === null ? null : $this->positiveDigits($teamId, 'team_id', '18446744073709551615'),
            'page' => array_key_exists('page', $filters)
                ? (int) $this->positiveDigits($filters['page'], 'page', (string) PHP_INT_MAX) : 1,
            'page_size' => array_key_exists('page_size', $filters)
                ? (int) $this->positiveDigits($filters['page_size'], 'page_size', (string) PHP_INT_MAX)
                : self::DEFAULT_PAGE_SIZE,
        ];
    }

    private function positiveDigits(mixed $value, string $name, string $maximum): string
    {
        if ((!is_int($value) && !is_string($value)) || !preg_match('/^[0-9]+$/D', (string) $value)) {
            throw new BadRequestHttpException($name . ' 必须为正整数。');
        }
        $digits = ltrim((string) $value, '0');
        if ($digits === '' || strlen($digits) > strlen($maximum)
            || (strlen($digits) === strlen($maximum) && strcmp($digits, $maximum) > 0)) {
            throw new BadRequestHttpException($name . ' 超出有效范围。');
        }
        return $digits;
    }

    private function pagination(int $page, int $pageSize, int $totalCount): array
    {
        $pageCount = intdiv($totalCount, $pageSize) + (int) ($totalCount % $pageSize !== 0);
        if ($page > max(1, $pageCount)) {
            throw new BadRequestHttpException('请求页码超出查询结果范围。');
        }
        return ['page' => $page, 'page_size' => $pageSize, 'total_count' => $totalCount, 'page_count' => $pageCount];
    }

    private function serializeMatch(array $row): array
    {
        $match = $this->record($row, self::MATCH_FIELDS,
            ['id', 'tournament_id', 'home_team_id', 'away_team_id', 'stadium_id', 'winner_team_id'],
            ['match_no', 'home_score_90', 'away_score_90', 'home_score_120', 'away_score_120',
                'home_penalties', 'away_penalties']);
        $match['tournament'] = $this->record($row['tournament'] ?? null, self::TOURNAMENT_FIELDS, ['id'], ['year']);
        $match['stadium'] = $this->record($row['stadium'] ?? null, self::STADIUM_FIELDS, ['id']);
        $match['final_score'] = $this->finalScore($match);
        return $match;
    }

    private function finalScore(array $match): array
    {
        if ($match['status'] !== 'FINISHED') {
            return ['home' => null, 'away' => null];
        }
        return [
            'home' => $match['home_score_120'] ?? $match['home_score_90'],
            'away' => $match['away_score_120'] ?? $match['away_score_90'],
        ];
    }

    private function isPublishedStory(?array $story): bool
    {
        return $story !== null && in_array($story['is_published'] ?? null, [1, '1', true], true);
    }

    private function serializePublicContent(?array $story, array $events): array
    {
        if (!$this->isPublishedStory($story)) {
            return ['story' => null, 'events' => [], 'tags' => []];
        }
        $publicStory = $this->record($story, self::STORY_FIELDS, ['id', 'match_id']);
        $publicStory['is_published'] = true;
        return [
            'story' => $publicStory,
            'events' => array_map(fn (array $event): array => $this->record($event, self::EVENT_FIELDS,
                ['id', 'match_id', 'team_id'], ['minute', 'added_minute', 'sort_order']), $events),
            'tags' => array_map(fn (array $tag): array => $this->record($tag, self::TAG_FIELDS, ['id']), $story['tags'] ?? []),
        ];
    }

    private function normalizeStageSummary(array $rows): array
    {
        $summary = array_map(static fn (array $row): array => [
            'stage_code' => $row['stage_code'],
            'match_count' => (int) $row['match_count'],
            'finished_match_count' => (int) $row['finished_match_count'],
            'total_goals' => (int) $row['finished_match_count'] === 0 || $row['total_goals'] === null
                ? null : (int) $row['total_goals'],
        ], $rows);
        $order = array_flip(WcMatch::STAGE_CODES);
        usort($summary, static fn (array $a, array $b): int =>
            ($order[$a['stage_code']] ?? PHP_INT_MAX) <=> ($order[$b['stage_code']] ?? PHP_INT_MAX));
        return $summary;
    }

    private function record(?array $row, array $fields, array $identifiers = [], array $integers = []): ?array
    {
        if ($row === null) {
            return null;
        }
        $result = [];
        foreach ($fields as $field) {
            $value = $row[$field] ?? null;
            if ($value !== null && in_array($field, $identifiers, true)) {
                $digits = ltrim((string) $value, '0');
                $max = (string) PHP_INT_MAX;
                $value = strlen($digits) < strlen($max)
                    || (strlen($digits) === strlen($max) && strcmp($digits, $max) <= 0) ? (int) $value : $digits;
            } elseif ($value !== null && in_array($field, $integers, true)) {
                $value = (int) $value;
            }
            $result[$field] = $value;
        }
        return $result;
    }

    private function columns(string $table, array $fields): array
    {
        return array_map(static fn (string $field): string => $table . '.' . $field, $fields);
    }

    private function databaseFailure(DatabaseException $exception, string $operation): ServerErrorHttpException
    {
        Yii::error(['operation' => $operation, 'exception' => $exception], self::class);
        return new ServerErrorHttpException('比赛数据暂时不可用，请稍后重试。', 0, $exception);
    }
}
