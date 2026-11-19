<?php
/** Public-facing labels only; never changes match records or score calculations. */
declare(strict_types=1);
namespace app\components;

final class MatchDisplay
{
    public const PERIODS = ['H1' => '上半场', 'H2' => '下半场', 'ET1' => '加时上半场',
        'ET2' => '加时下半场', 'SHOOTOUT' => '点球大战'];
    public const EVENTS = ['GOAL' => '进球', 'OWN_GOAL' => '乌龙球', 'YELLOW' => '黄牌',
        'RED' => '红牌', 'PENALTY_GOAL' => '点球罚进', 'PENALTY_MISS' => '点球未进', 'OTHER' => '其他事件'];
    public const KEY_EVENTS = ['GOAL', 'OWN_GOAL', 'RED', 'PENALTY_GOAL', 'PENALTY_MISS'];

    public static function period(string $code): string
    {
        return self::PERIODS[$code] ?? $code;
    }

    public static function event(string $code): string
    {
        return self::EVENTS[$code] ?? $code;
    }

    /** Explain absent score pairs conservatively; a null value alone proves nothing. */
    public static function scoreNote(array $match, string $period): string
    {
        $fields = match ($period) {
            '90' => ['home_score_90', 'away_score_90'],
            '120' => ['home_score_120', 'away_score_120'],
            'penalties' => ['home_penalties', 'away_penalties'],
            default => [],
        };
        if ($fields === []) return '比分待补充';
        [$home, $away] = array_map(static fn(string $key) => $match[$key] ?? null, $fields);
        if ($home !== null && $away !== null) return '';
        if ($home !== null || $away !== null) return '比分记录不完整';
        if (($match['stage_code'] ?? '') === 'GROUP' && $period !== '90') {
            return $period === '120' ? '小组赛不设加时' : '小组赛不设点球大战';
        }
        if (($match['status'] ?? '') !== 'FINISHED') {
            return match ($match['status'] ?? '') {
                'SCHEDULED' => '尚未开赛', 'IN_PROGRESS' => '比赛进行中',
                'POSTPONED' => '比赛已延期', 'CANCELLED' => '比赛已取消', default => '比分待补充',
            };
        }
        $has90 = isset($match['home_score_90'], $match['away_score_90']);
        $has120 = isset($match['home_score_120'], $match['away_score_120']);
        $no120 = ($match['home_score_120'] ?? null) === null && ($match['away_score_120'] ?? null) === null;
        $noPenalties = ($match['home_penalties'] ?? null) === null && ($match['away_penalties'] ?? null) === null;
        $decidedIn90 = $has90 && $match['home_score_90'] !== $match['away_score_90'] && $no120 && $noPenalties;
        if ($period === '120' && $decidedIn90) return '未进行加时';
        if ($period === 'penalties' && ($decidedIn90 || ($has120 && $match['home_score_120'] !== $match['away_score_120']))) {
            return '未进入点球大战';
        }
        return $period === '90' ? '比分待补充' : '是否进行及比分未注明';
    }
}
