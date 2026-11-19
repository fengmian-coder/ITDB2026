<?php
/** WorldCup Journey - 按名单版本读取球队图文样板，不写入或覆盖已有档案。 */
declare(strict_types=1);
namespace app\services;

use app\models\TeamProfileForm;
use Yii;

class TeamProfileFeatureService
{
    public function get(string $code, array $profile): ?array
    {
        $bundle = json_decode(file_get_contents(Yii::getAlias('@app/data/team-profile-features-2026.json')), true, 512, JSON_THROW_ON_ERROR);
        $feature = $bundle['teams'][$code] ?? null;
        if (!is_array($feature) || $feature['source_url'] !== $profile['source_url'] || $feature['edition'] !== $profile['edition']) {
            return null;
        }
        $roster = $feature['roster'] ?? [];
        $numbers = [];
        $counts = array_fill_keys(array_keys(TeamProfileForm::POSITIONS), 0);
        foreach ($roster as $player) {
            if (!is_array($player) || !isset($counts[$player['position'] ?? ''])
                || !is_int($player['shirt_number'] ?? null) || $player['shirt_number'] < 1 || $player['shirt_number'] > 99
                || !is_string($player['name'] ?? null) || trim($player['name']) === ''
                || !is_string($player['club'] ?? null) || trim($player['club']) === ''
                || !is_int($player['caps'] ?? null) || $player['caps'] < 0
                || !is_int($player['goals'] ?? null) || $player['goals'] < 0) {
                return null;
            }
            $numbers[] = $player['shirt_number'];
            ++$counts[$player['position']];
        }
        if (count($roster) !== 26 || count(array_unique($numbers)) !== 26) {
            return null;
        }
        $creditsFile = Yii::getAlias('@app/web/images/team-profiles/credits.json');
        $credits = is_file($creditsFile) ? json_decode(file_get_contents($creditsFile), true, 512, JSON_THROW_ON_ERROR) : [];
        $photo = static function (?string $key, string $name) use ($credits): ?array {
            $item = $credits[$key ?? ''] ?? null;
            if (!is_array($item) || ($item['name'] ?? '') !== $name
                || !preg_match('~\Aimages/team-profiles/[a-z0-9-]+\.(?:jpg|webp|png)\z~', $item['path'] ?? '')
                || !is_file(Yii::getAlias('@app/web/') . $item['path'])) {
                return null;
            }
            return $item;
        };
        $feature['summary'] = $profile['summary'] === $feature['base_summary'] ? $feature['summary'] : $profile['summary'];
        $feature['counts'] = $counts;
        $sameCoach = $profile['coach_name'] === $feature['coach_name'];
        $feature['coach_name_zh'] = $sameCoach ? $feature['coach_name_zh'] : $profile['coach_name'];
        if (!$sameCoach || $profile['coach_description'] !== $feature['coach_description_base']) {
            $feature['coach_facts'] = $profile['coach_description'];
        }
        $feature['coach_photo'] = $profile['coach_name'] === $feature['coach_name'] ? $photo($feature['coach_photo'], $profile['coach_name']) : null;
        $feature['highlighted'] = [];
        foreach ($profile['players'] as $selected) {
            foreach ($roster as $player) {
                if ($player['shirt_number'] === $selected['shirt_number'] && $player['name'] === $selected['name'] && $player['position'] === $selected['position']) {
                    $player['photo'] = $photo($feature['player_photos'][(string) $player['shirt_number']] ?? null, $player['name']);
                    $feature['highlighted'][$player['shirt_number']] = $player;
                }
            }
        }
        return $feature;
    }
}
