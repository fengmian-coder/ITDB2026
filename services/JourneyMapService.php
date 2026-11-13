<?php
/**
 * WorldCup Journey - 球队旅程地点与连线组合
 * 模块：B；负责人：B；依赖：JourneyService、已核对的场馆坐标清单
 * 最近修改：2026-11-13
 */
declare(strict_types=1);
namespace app\services;

use Yii;

class JourneyMapService
{
    /** 连线表示比赛地点的时间顺序，同场馆合并站点，未知地点形成断点。 */
    public function build(array $journey): array
    {
        $catalog = json_decode(file_get_contents(Yii::getAlias('@app/data/stadium-coordinates.json')), true, 512, JSON_THROW_ON_ERROR);
        $coordinates = array_column($catalog['stadiums'], null, 'name');
        $stops = [];
        $legs = [];
        $previous = null;
        $unmapped = 0;
        foreach ($journey as $match) {
            if ($match['status'] === 'CANCELLED') continue;
            $stadium = $match['stadium'] ?? null;
            $location = $coordinates[$stadium['name'] ?? ''] ?? null;
            if ($location === null || $location['city'] !== $stadium['city'] || $location['country_code'] !== $stadium['country_code']) {
                ++$unmapped;
                $previous = null;
                continue;
            }
            $key = (string) $stadium['id'];
            $stops[$key] ??= ['id' => $key, 'name' => $stadium['name'], 'city' => $stadium['city'],
                'country_code' => $stadium['country_code'], 'coordinate' => [$location['longitude'], $location['latitude']], 'matches' => []];
            $stops[$key]['matches'][] = $match;
            if ($previous !== null && $previous['stadium_id'] !== $key) {
                $legs[] = ['from' => $previous['stadium_id'], 'to' => $key,
                    'completed' => $previous['status'] === 'FINISHED' && $match['status'] === 'FINISHED'];
            }
            $previous = ['stadium_id' => $key, 'status' => $match['status']];
        }
        return ['stops' => array_values($stops), 'legs' => $legs, 'unmapped_match_count' => $unmapped];
    }
}
