<?php
/**
 * WorldCup Journey - 球队比赛历程查询（B 侧阶段实现）
 * 模块：B 赛事与比赛后端
 * 负责人：B
 * 依赖：Yii 2.0、WcMatch、Tournament、MatchStory；不依赖尚未交付的 C 类
 * 待联调：球队存在性、本届参赛资格、真实对手名称、最终成绩
 * 最近修改：2026-11-05
 */

declare(strict_types=1);

namespace app\services;

use app\models\MatchStory;
use app\models\Tournament;
use app\models\WcMatch;
use Yii;
use UnexpectedValueException;
use yii\db\ActiveQuery;
use yii\db\Exception as DatabaseException;
use yii\web\BadRequestHttpException;
use yii\web\ServerErrorHttpException;

/**
 * 保持 V1.1 的 getTeamJourney() 签名和按时间排列的比赛项数组。
 *
 * 当前接入边界（不是已完成 C 联调的正式接口）：
 * - opponent.name_zh 暂为 null，只提供真实 ID，不编造球队名称。
 * - 暂不验证球队存在/参赛资格；[] 仅表示本届无匹配比赛，不可据此断定 404。
 * - 时间采用 V1.1 旅程样例的 UTC ISO 8601；不改变 MatchQueryService 的时间格式。
 * - 保留取消/延期赛程，并附现有字段 status，避免调用方把无比分误认为未开始。
 * - is_featured 仅依据已发布故事；没有故事或草稿为 false。
 * - 仅已结束的 R32/R16/QF/SF 表达 ADVANCED/ELIMINATED；小组赛、未完赛、
 *   决赛和季军赛返回 null，后两者的成绩展示需另行确认，不能称作下一轮晋级。
 * - 不返回或推算 final_result_code；该结论归 C 的 TournamentTeam 维护。
 *
 * 当前 Controller/View 仅接入本阶段结构；正式跨模块联调前须与 C 对齐，
 * 后续由 B 补资格验证和名称读取。
 */
class JourneyService
{
    private const ADVANCEMENT_STAGES = ['R32', 'R16', 'QF', 'SF'];
    private const FIELDS = [
        'id', 'match_no', 'home_team_id', 'away_team_id', 'kickoff_utc', 'stage_code', 'status',
        'home_score_90', 'away_score_90', 'home_score_120', 'away_score_120',
        'home_penalties', 'away_penalties', 'winner_team_id',
    ];

    /**
     * 不分页，不遗漏默认第一页以后的比赛；查询只使用 B 负责的表。
     *
     * @return array<array{
     *   match_id: int|string, match_no: int, kickoff_utc: string, stage_code: string, status: string,
     *   opponent: array{team_id: int|string, name_zh: null}, is_home: bool,
     *   goals_for: int|null, goals_against: int|null, penalties_for: int|null,
     *   penalties_against: int|null, advancement_result: string|null, is_featured: bool
     * }>
     */
    public function getTeamJourney(int $teamId): array
    {
        if ($teamId < 1) {
            throw new BadRequestHttpException('球队 ID 必须为正整数');
        }

        try {
            $rows = $this->journeyQuery($teamId)->all();
            return array_map(fn (array $row): array => $this->journeyItem($row, $teamId), $rows);
        } catch (DatabaseException | UnexpectedValueException $exception) {
            Yii::error(['operation' => __FUNCTION__, 'exception' => $exception], self::class);
            throw new ServerErrorHttpException('球队比赛历程暂时不可用，请稍后重试', 0, $exception);
        }
    }

    private function journeyQuery(int $teamId): ActiveQuery
    {
        $columns = array_map(static fn (string $field): string => 'm.' . $field, self::FIELDS);
        $columns['published_story_id'] = 's.id';

        // match_story.match_id 按契约唯一；发布条件必须在 LEFT JOIN 的 ON 中，
        // 否则没有故事的普通比赛会被过滤。仅读 ID，不读取草稿正文或时间轴。
        return WcMatch::find()->alias('m')->select($columns)
            ->innerJoin(['t' => Tournament::tableName()], '[[t.id]] = [[m.tournament_id]]')
            ->leftJoin(['s' => MatchStory::tableName()],
                '[[s.match_id]] = [[m.id]] AND [[s.is_published]] = :journey_published',
                [':journey_published' => 1])
            ->where(['t.year' => 2026])
            ->andWhere(['or', ['m.home_team_id' => $teamId], ['m.away_team_id' => $teamId]])
            ->orderBy(['m.kickoff_utc' => SORT_ASC, 'm.match_no' => SORT_ASC, 'm.id' => SORT_ASC])
            ->asArray();
    }

    private function journeyItem(array $row, int $teamId): array
    {
        $isHome = (string) $row['home_team_id'] === (string) $teamId;
        if (!$isHome && (string) $row['away_team_id'] !== (string) $teamId) {
            throw new UnexpectedValueException('历程记录不属于所查询球队');
        }
        if (!in_array($row['stage_code'], WcMatch::STAGE_CODES, true)
            || !in_array($row['status'], WcMatch::STATUS_CODES, true)) {
            throw new UnexpectedValueException('比赛阶段或状态不符合契约');
        }

        $finished = $row['status'] === 'FINISHED';
        // 累计加时比分只选一次，绝不与 90 分钟相加，也不加入点球大战。
        $home90 = $this->score($row, 'home_score_90');
        $away90 = $this->score($row, 'away_score_90');
        $home120 = $this->score($row, 'home_score_120');
        $away120 = $this->score($row, 'away_score_120');
        $homePenalties = $this->score($row, 'home_penalties');
        $awayPenalties = $this->score($row, 'away_penalties');
        foreach ([[$home90, $away90], [$home120, $away120], [$homePenalties, $awayPenalties]] as [$home, $away]) {
            if (($home === null) !== ($away === null)) {
                throw new UnexpectedValueException('比赛比分未成对填写');
            }
        }
        if ($finished && ($home90 === null || $away90 === null)) {
            throw new UnexpectedValueException('已结束比赛缺少 90 分钟比分');
        }
        $homeScore = $finished ? ($home120 ?? $home90) : null;
        $awayScore = $finished ? ($away120 ?? $away90) : null;

        $advancement = null;
        if ($finished && in_array($row['stage_code'], self::ADVANCEMENT_STAGES, true)) {
            if ($row['winner_team_id'] === null || !in_array((string) $row['winner_team_id'],
                [(string) $row['home_team_id'], (string) $row['away_team_id']], true)) {
                throw new UnexpectedValueException('已结束淘汰赛缺少有效获胜方');
            }
            $advancement = (string) $row['winner_team_id'] === (string) $teamId ? 'ADVANCED' : 'ELIMINATED';
        }

        return [
            'match_id' => $this->identifier($row['id']),
            'match_no' => (int) $row['match_no'],
            'kickoff_utc' => $this->utcTimestamp($row['kickoff_utc']),
            'stage_code' => $row['stage_code'],
            'status' => $row['status'],
            'opponent' => [
                'team_id' => $this->identifier($row[$isHome ? 'away_team_id' : 'home_team_id']),
                'name_zh' => null, // TODO(B/C)：接入真实球队 Model 后读取，不编造显示名。
            ],
            'is_home' => $isHome,
            'goals_for' => $isHome ? $homeScore : $awayScore,
            'goals_against' => $isHome ? $awayScore : $homeScore,
            'penalties_for' => $finished ? ($isHome ? $homePenalties : $awayPenalties) : null,
            'penalties_against' => $finished ? ($isHome ? $awayPenalties : $homePenalties) : null,
            'advancement_result' => $advancement,
            'is_featured' => $row['published_story_id'] !== null,
        ];
    }

    private function score(array $row, string $key): ?int
    {
        $value = $row[$key];
        if ($value === null) {
            return null;
        }
        if ((!is_int($value) && !is_string($value)) || !preg_match('/^[0-9]+$/D', (string) $value)
            || strlen((string) $value) > 3 || (int) $value > 255) {
            throw new UnexpectedValueException('比分超出字段允许范围');
        }
        return (int) $value;
    }

    private function utcTimestamp(string $value): string
    {
        $time = \DateTimeImmutable::createFromFormat('!Y-m-d H:i:s', $value, new \DateTimeZone('UTC'));
        if ($time === false || $time->format('Y-m-d H:i:s') !== $value) {
            throw new UnexpectedValueException('比赛时间不是有效的 UTC DATETIME');
        }
        return $time->format('Y-m-d\TH:i:s\Z');
    }

    private function identifier(int|string $value): int|string
    {
        // 与 MatchQueryService 一致：较大 ID 保留十进制字符串，避免整型截断。
        $digits = ltrim((string) $value, '0');
        $maximum = (string) PHP_INT_MAX;
        return strlen($digits) < strlen($maximum)
            || (strlen($digits) === strlen($maximum) && strcmp($digits, $maximum) <= 0) ? (int) $digits : $digits;
    }
}
