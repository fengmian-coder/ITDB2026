<?php
/**
 * WorldCup Journey - A 页面使用的只读参赛球队名称目录。
 * 模块：A 首页与纪念卡；负责人：A。
 * 依赖：C 的 national_team / tournament_team、B 的 tournament；不计算球队统计。
 * 最近修改：2026-11-11。
 */
declare(strict_types=1);
namespace app\services;

use yii\db\Query;

class TeamDirectoryService
{
    /** 历史比赛按本届参赛关系解析球队，允许保留已停用球队资料。 */
    public function getTeams(bool $includeInactive = false): array
    {
        $query = (new Query())->select(['n.id', 'n.name_zh', 'n.name_en', 'n.fifa_code'])
            ->from(['n' => 'national_team'])
            ->innerJoin(['tt' => 'tournament_team'], '[[tt.team_id]] = [[n.id]]')
            ->innerJoin(['t' => 'tournament'], '[[t.id]] = [[tt.tournament_id]]')
            ->where(['t.year' => 2026]);
        if (!$includeInactive) {
            $query->andWhere(['n.is_active' => 1]);
        }
        return $query->orderBy(['n.name_zh' => SORT_ASC, 'n.id' => SORT_ASC])->all();
    }
}
