<?php
/**
 * WorldCup Journey - 精选比赛比分走势
 * 模块：B；负责人：B；依赖：MatchQueryService 公开详情
 * 最近修改：2026-11-14
 */

declare(strict_types=1);

namespace app\services;

class MatchMomentumService
{
    public function build(array $match): array
    {
        $unavailable = ['available' => false, 'points' => [], 'message' => '进球事件尚未完整核对，暂不展示比分走势。'];
        if ($match['story'] === null || $match['status'] !== 'FINISHED') {
            return $unavailable;
        }
        $home = $away = $lastLeader = 0;
        $points = [['label' => '开场', 'home' => 0, 'away' => 0, 'event_id' => null, 'description' => '比赛开始', 'player' => '', 'change' => '平局']];
        $lastTime = -1;
        $regulation = [0, 0];
        $periods = ['H1' => 0, 'H2' => 1, 'ET1' => 2, 'ET2' => 3];
        foreach ($match['events'] as $event) {
            if ($event['period_code'] === 'SHOOTOUT' || !in_array($event['event_type'], ['GOAL', 'PENALTY_GOAL', 'OWN_GOAL'], true)) {
                continue;
            }
            // 乌龙球的 team_id 归属在当前契约中尚未冻结，避免推断受益球队。
            if ($event['event_type'] === 'OWN_GOAL' || $event['minute'] === null || !isset($periods[$event['period_code']])) {
                return $unavailable;
            }
            $period = $periods[$event['period_code']];
            if ($period > 1 && $match['home_score_120'] === null) {
                return $unavailable;
            }
            $time = $period * 100000000 + $event['minute'] * 100000 + ($event['added_minute'] ?? 0);
            if ($time < $lastTime) {
                return $unavailable;
            }
            $lastTime = $time;
            $previous = $home - $away;
            if ((string) $event['team_id'] === (string) $match['home_team_id']) {
                ++$home;
            } elseif ((string) $event['team_id'] === (string) $match['away_team_id']) {
                ++$away;
            } else {
                return $unavailable;
            }
            if ($period <= 1) {
                $regulation = [$home, $away];
            }
            $difference = $home - $away;
            $change = '比分变化';
            if ($difference === 0) {
                $change = '追平';
            } elseif ($lastLeader * $difference < 0) {
                $change = '反超';
            } elseif ($previous === 0) {
                $change = '取得领先';
            }
            if ($difference !== 0) {
                $lastLeader = $difference;
            }
            $points[] = ['label' => $event['minute'] . (($event['added_minute'] ?? 0) > 0 ? '+' . $event['added_minute'] : '') . '′',
                'home' => $home, 'away' => $away, 'event_id' => $event['id'], 'description' => $event['description'] ?? '',
                'player' => $event['player_name'] ?? '', 'change' => $change];
        }
        if ($regulation !== [(int) $match['home_score_90'], (int) $match['away_score_90']]
            || [$home, $away] !== [$match['final_score']['home'], $match['final_score']['away']]) {
            return $unavailable;
        }
        $points[] = ['label' => $match['home_score_120'] !== null ? '加时结束' : '终场', 'home' => $home, 'away' => $away,
            'event_id' => null, 'description' => '最终场上比分，点球大战另计', 'player' => '', 'change' => '比赛结束'];
        return ['available' => true, 'points' => $points, 'message' => ''];
    }
}
