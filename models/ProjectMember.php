<?php
/**
 * WorldCup Journey - 课程团队成员资料。
 * 模块：A 团队资料；负责人：A。
 * 依赖：Yii 2.0 ActiveRecord、ProjectTeam、project_member。
 * 最近修改：2026-11-10。
 */
declare(strict_types=1);

namespace app\models;

use yii\db\ActiveQuery;
use yii\db\ActiveRecord;

class ProjectMember extends ActiveRecord
{
    public static function tableName(): string
    {
        return 'project_member';
    }

    public function rules(): array
    {
        return [
            [['display_name', 'role_title', 'bio'], 'trim'],
            [['project_team_id', 'display_name', 'role_title'], 'required'],
            ['project_team_id', 'integer', 'min' => 1],
            ['project_team_id', 'exist', 'targetClass' => ProjectTeam::class, 'targetAttribute' => 'id'],
            ['display_name', 'string', 'max' => 80],
            ['role_title', 'string', 'max' => 100],
            ['bio', 'string', 'max' => 10000],
            ['sort_order', 'default', 'value' => 0],
            ['sort_order', 'integer', 'min' => -2147483648, 'max' => 2147483647],
        ];
    }

    public function attributeLabels(): array
    {
        return [
            'display_name' => '成员姓名', 'role_title' => '负责方向',
            'bio' => '个人介绍与具体分工', 'sort_order' => '显示顺序',
        ];
    }

    public function getProjectTeam(): ActiveQuery
    {
        return $this->hasOne(ProjectTeam::class, ['id' => 'project_team_id']);
    }
}
