<?php
/**
 * WorldCup Journey - 课程团队资料与成员关联。
 * 模块：A 团队资料；负责人：A。
 * 依赖：Yii 2.0 ActiveRecord、project_team / project_member。
 * 最近修改：2026-11-10。
 */
declare(strict_types=1);

namespace app\models;

use yii\db\ActiveQuery;
use yii\db\ActiveRecord;

class ProjectTeam extends ActiveRecord
{
    public static function tableName(): string
    {
        return 'project_team';
    }

    public function rules(): array
    {
        return [
            [['name', 'description', 'logo_path'], 'trim'],
            ['name', 'required'],
            ['name', 'string', 'max' => 100],
            ['description', 'string', 'max' => 10000],
            ['logo_path', 'default', 'value' => null],
            ['logo_path', 'string', 'max' => 255],
            ['logo_path', 'match',
                'pattern' => '~\Aimages/(?:[a-zA-Z0-9_-]+/)*[a-zA-Z0-9_-]+\.(?:png|jpe?g|webp|gif)\z~i',
                'message' => '请输入 web/images 下的图片相对路径，例如 images/team/logo.png'],
        ];
    }

    public function attributeLabels(): array
    {
        return ['name' => '团队名称', 'description' => '团队简介', 'logo_path' => '团队图片路径'];
    }

    public function getMembers(): ActiveQuery
    {
        return $this->hasMany(ProjectMember::class, ['project_team_id' => 'id'])
            ->orderBy(['sort_order' => SORT_ASC, 'id' => SORT_ASC]);
    }
}
