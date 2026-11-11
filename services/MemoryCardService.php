<?php
/**
 * WorldCup Journey - 纪念卡数据组合，复用 B 的比分与 C 的统计口径。
 * 模块：A 无注册纪念卡；负责人：A。
 * 依赖：TeamDirectoryService、JourneyService、MatchQueryService；可选 C 的 TeamStatsService。
 * 最近修改：2026-11-11。
 */
declare(strict_types=1);
namespace app\services;

use DateTimeImmutable;
use DateTimeZone;
use Throwable;
use Yii;
use yii\web\BadRequestHttpException;
use yii\web\NotFoundHttpException;

class MemoryCardService
{
    public const STAGES = ['GROUP' => '小组赛', 'R32' => '32 强赛', 'R16' => '16 强赛', 'QF' => '四分之一决赛', 'SF' => '半决赛', 'THIRD' => '季军赛', 'FINAL' => '决赛'];

    public function getEditor(?int $teamId, ?int $matchId): array
    {
        $directory = new TeamDirectoryService();
        $teams = $directory->getTeams();
        $byId = array_column($directory->getTeams(true), null, 'id');
        $detail = $matchId === null ? null : (new MatchQueryService())->getMatchDetail($matchId);
        if ($teamId === null && $detail !== null) {
            $teamId = (int) $detail['home_team_id'];
        }
        if ($teamId !== null && !isset($byId[$teamId])) {
            throw new NotFoundHttpException('找不到本届参赛球队');
        }
        // 从历史比赛进入时，让停用球队仍能保持选中，默认目录继续使用启用球队。
        if ($teamId !== null && !in_array($teamId, array_column($teams, 'id'), false)) {
            $teams[] = $byId[$teamId];
        }
        $journey = $teamId === null ? [] : (new JourneyService())->getTeamJourney($teamId);
        $matches = [];
        foreach ($journey as $item) {
            $opponent = $byId[$item['opponent']['team_id']]['name_zh'] ?? '待定对手';
            $date = (new DateTimeImmutable($item['kickoff_utc'], new DateTimeZone('UTC')))->setTimezone(new DateTimeZone('Asia/Shanghai'));
            $matches[$item['match_id']] = $date->format('m.d H:i') . ' · 对阵' . $opponent . ' · ' . (self::STAGES[$item['stage_code']] ?? $item['stage_code']);
        }
        if ($matchId !== null && !array_key_exists($matchId, $matches)) {
            throw new BadRequestHttpException('所选比赛不属于这支球队');
        }
        $card = null;
        if ($detail !== null) {
            $home = $byId[$detail['home_team_id']]['name_zh'] ?? '待定球队';
            $away = $byId[$detail['away_team_id']]['name_zh'] ?? '待定球队';
            $date = (new DateTimeImmutable($detail['kickoff_utc'], new DateTimeZone('UTC')))->setTimezone(new DateTimeZone('Asia/Shanghai'));
            $score = $detail['status'] === 'FINISHED'
                ? ($detail['final_score']['home'] . ' : ' . $detail['final_score']['away'])
                : match ($detail['status']) {
                    'SCHEDULED' => '尚未开赛', 'IN_PROGRESS' => '比赛进行中',
                    'POSTPONED' => '比赛延期', 'CANCELLED' => '比赛取消', default => '暂无比分',
                };
            $penalties = $detail['status'] === 'FINISHED' && $detail['home_penalties'] !== null
                ? '点球 ' . $detail['home_penalties'] . ' : ' . $detail['away_penalties'] : null;
            $stats = null;
            $statsClass = 'app\\services\\TeamStatsService';
            if (class_exists($statsClass)) {
                try {
                    $stats = Yii::createObject($statsClass)->getTeamStats($teamId);
                } catch (Throwable $exception) {
                    Yii::warning(['operation' => 'memory-team-stats', 'team_id' => $teamId], self::class);
                }
            }
            $card = [
                'team_id' => $teamId, 'match_id' => $matchId, 'team_name' => $byId[$teamId]['name_zh'],
                'home_name' => $home, 'away_name' => $away, 'score' => $score,
                'penalties' => $penalties, 'date' => $date->format('Y.m.d H:i') . ' 北京时间',
                'stage' => self::STAGES[$detail['stage_code']] ?? $detail['stage_code'],
                'story' => $detail['story'] === null ? null : mb_substr((string) $detail['story']['summary'], 0, 100),
                'stats' => $stats,
            ];
        }
        return ['teams' => $teams, 'matches' => $matches, 'card' => $card,
            'selectedTeamId' => $teamId, 'selectedMatchId' => $matchId];
    }
}
