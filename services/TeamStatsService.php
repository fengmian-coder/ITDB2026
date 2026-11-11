<?php
/**
 * WorldCup Journey - 球队统计服务
 * 模块：C 球队与统计
 * 负责人：C
 * 依赖：Yii 2.0、NationalTeam、TournamentTeam、Tournament、WcMatch
 * 最近修改：2026-11-11
 */

declare(strict_types=1);

namespace app\services;

use app\models\NationalTeam;
use app\models\Tournament;
use app\models\TournamentTeam;
use app\models\WcMatch;
use UnexpectedValueException;
use Yii;
use yii\db\Exception as DatabaseException;
use yii\web\BadRequestHttpException;
use yii\web\NotFoundHttpException;
use yii\web\ServerErrorHttpException;

/**
 * 球队 DNA / PK / 进球排名的唯一统计入口。
 *
 * 只统计 2026 届 FINISHED 比赛；最终场上比分优先 120 分钟累计值，
 * 否则 90 分钟；点球大战命中数不计入总进球。所有页面和 API 复用本服务，
 * 不在 Controller/View 中重复聚合 SQL。
 */
class TeamStatsService
{
    private const TOURNAMENT_YEAR = 2026;

    private const MATCH_FIELDS = [
        'id', 'home_team_id', 'away_team_id',
        'home_score_90', 'away_score_90',
        'home_score_120', 'away_score_120',
        'home_penalties', 'away_penalties',
    ];

    /**
     * 单队统一统计。不存在或未参加 2026 返回 404；非法 ID 返回 400。
     *
     * @return array{
     *   team_id: int, team_name: string, fifa_code: string,
     *   matches_played: int, wins: int, draws: int, losses: int,
     *   goals_for: int, goals_against: int,
     *   goals_per_match: float|null, goals_against_per_match: float|null,
     *   win_rate: float|null, penalty_wins: int, final_result_code: string|null
     * }
     */
    public function getTeamStats(int $teamId): array
    {
        if ($teamId < 1) {
            throw new BadRequestHttpException('球队 ID 必须为正整数。');
        }

        try {
            $tournamentId = $this->tournamentId();
            $team = $this->teamRow($teamId);
            if ($team === null) {
                throw new NotFoundHttpException('未找到该球队。');
            }

            $participation = $this->participationRow($tournamentId, $teamId);
            if ($participation === null) {
                throw new NotFoundHttpException('该球队未参加2026世界杯。');
            }

            $seed = $this->teamSeed($team, $this->nullableString($participation['final_result_code'] ?? null));
            $stats = $this->buildStats(
                [$teamId => $seed],
                $this->finishedMatches($tournamentId, $teamId),
            );

            return $stats[$teamId];
        } catch (DatabaseException | UnexpectedValueException $exception) {
            Yii::error(['operation' => __FUNCTION__, 'exception' => $exception], self::class);
            throw new ServerErrorHttpException('球队统计暂时不可用，请稍后重试。', 0, $exception);
        }
    }

    /**
     * 双队比较；left/right 与输入顺序一致。同队比较返回 400。
     *
     * @return array{left: array, right: array}
     */
    public function getCompareStats(int $leftId, int $rightId): array
    {
        if ($leftId < 1 || $rightId < 1) {
            throw new BadRequestHttpException('球队 ID 必须为正整数。');
        }
        if ($leftId === $rightId) {
            throw new BadRequestHttpException('请选择两支不同的球队。');
        }

        // 两边复用同一 getTeamStats，避免出现第二套统计公式。
        return [
            'left' => $this->getTeamStats($leftId),
            'right' => $this->getTeamStats($rightId),
        ];
    }

    /**
     * 2026 届 48 支参赛队的完整统计，按总进球降序。
     * 0 场完赛的球队也返回；无参赛记录时返回空数组。
     *
     * @return array<int, array>
     */
    public function getScoringRanking(): array
    {
        try {
            $tournamentId = $this->tournamentId();
            $teams = $this->participantRows($tournamentId);
            if ($teams === []) {
                return [];
            }

            $stats = $this->buildStats($teams, $this->finishedMatches($tournamentId));
            usort($stats, static function (array $left, array $right): int {
                $byGoals = $right['goals_for'] <=> $left['goals_for'];
                if ($byGoals !== 0) {
                    return $byGoals;
                }
                $byCode = strcmp($left['fifa_code'], $right['fifa_code']);
                if ($byCode !== 0) {
                    return $byCode;
                }
                return $left['team_id'] <=> $right['team_id'];
            });

            return array_values($stats);
        } catch (DatabaseException | UnexpectedValueException $exception) {
            Yii::error(['operation' => __FUNCTION__, 'exception' => $exception], self::class);
            throw new ServerErrorHttpException('球队排名暂时不可用，请稍后重试。', 0, $exception);
        }
    }

    private function tournamentId(): int
    {
        $id = Tournament::find()
            ->select('id')
            ->where(['year' => self::TOURNAMENT_YEAR])
            ->scalar();

        if ($id === false || $id === null) {
            Yii::error('2026 tournament row is missing.', self::class);
            throw new ServerErrorHttpException('2026届赛事数据尚未初始化。');
        }

        return (int) $id;
    }

    /** @return array{id: int|string, name_zh: string, name_en: string, fifa_code: string}|null */
    private function teamRow(int $teamId): ?array
    {
        return NationalTeam::find()
            ->select(['id', 'name_zh', 'name_en', 'fifa_code'])
            ->where(['id' => $teamId])
            ->asArray()
            ->one();
    }

    /** @return array{final_result_code: string|null}|null */
    private function participationRow(int $tournamentId, int $teamId): ?array
    {
        return TournamentTeam::find()
            ->select(['final_result_code'])
            ->where(['tournament_id' => $tournamentId, 'team_id' => $teamId])
            ->asArray()
            ->one();
    }

    /**
     * 参赛球队的种子数据，key 为 team_id。
     *
     * @return array<int, array>
     */
    private function participantRows(int $tournamentId): array
    {
        $rows = TournamentTeam::find()
            ->alias('tt')
            ->innerJoin(['nt' => NationalTeam::tableName()], '[[nt.id]] = [[tt.team_id]]')
            ->select([
                'tt.team_id', 'tt.final_result_code',
                'nt.name_zh', 'nt.name_en', 'nt.fifa_code',
            ])
            ->where(['tt.tournament_id' => $tournamentId])
            ->orderBy(['tt.team_id' => SORT_ASC])
            ->asArray()
            ->all();

        $teams = [];
        foreach ($rows as $row) {
            $teamId = (int) $row['team_id'];
            $teams[$teamId] = $this->teamSeed(
                [
                    'id' => $row['team_id'],
                    'name_zh' => $row['name_zh'],
                    'name_en' => $row['name_en'],
                    'fifa_code' => $row['fifa_code'],
                ],
                $this->nullableString($row['final_result_code'] ?? null),
            );
        }

        return $teams;
    }

    /** @return array<int, array> */
    private function finishedMatches(int $tournamentId, ?int $teamId = null): array
    {
        $query = WcMatch::find()
            ->select(self::MATCH_FIELDS)
            ->where([
                'tournament_id' => $tournamentId,
                'status' => 'FINISHED',
            ]);

        if ($teamId !== null) {
            $query->andWhere(['or',
                ['home_team_id' => $teamId],
                ['away_team_id' => $teamId],
            ]);
        }

        return $query->asArray()->all();
    }

    /**
     * @param array<int, array> $seeds
     * @param array<int, array> $matches
     * @return array<int, array>
     */
    private function buildStats(array $seeds, array $matches): array
    {
        foreach ($matches as $match) {
            $homeId = (int) $match['home_team_id'];
            $awayId = (int) $match['away_team_id'];
            $homeScore = $this->finalScore($match, 'home');
            $awayScore = $this->finalScore($match, 'away');
            $penalties = $this->penaltyScores($match);

            if (isset($seeds[$homeId])) {
                $this->applyResult($seeds[$homeId], $homeScore, $awayScore, $penalties, true);
            }
            if (isset($seeds[$awayId])) {
                $this->applyResult($seeds[$awayId], $awayScore, $homeScore, $penalties, false);
            }
        }

        foreach ($seeds as &$stats) {
            $played = $stats['matches_played'];
            $stats['goals_per_match'] = $played > 0 ? $stats['goals_for'] / $played : null;
            $stats['goals_against_per_match'] = $played > 0 ? $stats['goals_against'] / $played : null;
            $stats['win_rate'] = $played > 0 ? $stats['wins'] / $played : null;
        }
        unset($stats);

        return $seeds;
    }

    private function applyResult(array &$stats, int $for, int $against, ?array $penalties, bool $isHome): void
    {
        $stats['matches_played']++;
        if ($for > $against) {
            $stats['wins']++;
        } elseif ($for === $against) {
            $stats['draws']++;
        } else {
            $stats['losses']++;
        }
        $stats['goals_for'] += $for;
        $stats['goals_against'] += $against;

        if ($penalties !== null) {
            $teamPenalties = $isHome ? $penalties[0] : $penalties[1];
            $opponentPenalties = $isHome ? $penalties[1] : $penalties[0];
            if ($teamPenalties > $opponentPenalties) {
                $stats['penalty_wins']++;
            }
        }
    }

    /** 已结束比赛必须至少有 90 分钟比分；120 分钟是累计值，不再与 90 相加。 */
    private function finalScore(array $match, string $side): int
    {
        $score90 = $match[$side . '_score_90'];
        if ($score90 === null || $score90 === '') {
            throw new UnexpectedValueException('已结束比赛缺少 90 分钟比分。');
        }

        $score120 = $match[$side . '_score_120'];
        return (int) (($score120 !== null && $score120 !== '') ? $score120 : $score90);
    }

    /** @return array{0: int, 1: int}|null */
    private function penaltyScores(array $match): ?array
    {
        $home = $match['home_penalties'];
        $away = $match['away_penalties'];

        if ($home === null && $away === null) {
            return null;
        }
        if ($home === null || $away === null) {
            throw new UnexpectedValueException('点球比分未成对填写。');
        }
        if ((int) $home === (int) $away) {
            throw new UnexpectedValueException('点球大战必须分出胜负。');
        }

        return [(int) $home, (int) $away];
    }

    /** @param array{id: int|string, name_zh: string, name_en: string, fifa_code: string} $team */
    private function teamSeed(array $team, ?string $finalResultCode): array
    {
        $name = (string) ($team['name_zh'] ?? '');
        if ($name === '') {
            $name = (string) ($team['name_en'] ?? '');
        }

        return [
            'team_id' => (int) $team['id'],
            'team_name' => $name,
            'fifa_code' => (string) $team['fifa_code'],
            'matches_played' => 0,
            'wins' => 0,
            'draws' => 0,
            'losses' => 0,
            'goals_for' => 0,
            'goals_against' => 0,
            'goals_per_match' => null,
            'goals_against_per_match' => null,
            'win_rate' => null,
            'penalty_wins' => 0,
            'final_result_code' => $finalResultCode,
        ];
    }

    private function nullableString(mixed $value): ?string
    {
        return $value === null || $value === '' ? null : (string) $value;
    }
}
