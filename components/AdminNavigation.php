<?php
/**
 * WorldCup Journey - 后台侧栏与工作台共用的模块入口。
 * 模块：A 后台公共界面；负责人：A。
 * 依赖：已交付的后台控制器；C 的球队后台存在时才展示入口。
 * 最近修改：2026-11-10。
 */
declare(strict_types=1);
namespace app\components;

class AdminNavigation
{
    public static function groups(): array
    {
        $groups = [
            '工作台' => [
                ['id' => 'admin-dashboard', 'label' => '管理概览', 'description' => '查看内容数量与常用维护入口'],
            ],
            '网站内容' => [
                ['id' => 'admin-project', 'label' => '团队与成员', 'description' => '维护团队介绍、成员资料与实际分工'],
                ['id' => 'admin-document', 'label' => '作业管理', 'description' => '上传、替换和管理团队及个人作业'],
                ['id' => 'admin-guestbook', 'label' => '留言审核', 'description' => '审核球迷留言，并回复已通过的内容'],
            ],
            '赛事资料' => [
                ['id' => 'admin-tournament', 'label' => '赛事届次', 'description' => '维护世界杯届次、日期与赛事简介'],
                ['id' => 'admin-match', 'label' => '比赛管理', 'description' => '维护比赛赛程、状态与比分'],
                ['id' => 'admin-stadium', 'label' => '场馆管理', 'description' => '维护比赛场馆、所在城市与时区'],
                ['id' => 'admin-story', 'label' => '故事与事件', 'description' => '整理精选故事、比赛事件和共享标签'],
            ],
        ];
        if (class_exists('app\\controllers\\AdminTeamController')) {
            $groups['赛事资料'][] = ['id' => 'admin-team', 'label' => '球队管理', 'description' => '维护国家队资料与参赛信息'];
        }
        return $groups;
    }

    public static function current(string $controllerId): array
    {
        foreach (self::groups() as $items) {
            foreach ($items as $item) {
                if ($item['id'] === $controllerId) return $item;
            }
        }
        return ['id' => $controllerId, 'label' => '后台管理', 'description' => '维护网站内容'];
    }
}
