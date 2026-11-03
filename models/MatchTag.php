<?php
/**
 * WorldCup Journey - 比赛标签字典 ActiveRecord
 * 模块：B 赛事与比赛后端
 * 负责人：B
 * 依赖：Yii 2.0 ActiveRecord、match_tag 表；关联查询待 B 的 MatchStoryTag 就绪
 * 最近修改：2026-11-03
 */

declare(strict_types=1);

namespace app\models;

use yii\base\InvalidConfigException;
use yii\db\ActiveQuery;
use yii\db\ActiveRecord;

/**
 * 不预设标签代码集合、名称唯一性、默认值或审计字段。
 *
 * @property int|string $id
 * @property string $code
 * @property string $name
 */
class MatchTag extends ActiveRecord
{
    public static function tableName(): string
    {
        return 'match_tag';
    }

    public function rules(): array
    {
        return [
            [['code', 'name'], 'required'],
            [['code', 'name'], 'string', 'max' => 40],
            ['code', 'unique'],
        ];
    }

    public function attributeLabels(): array
    {
        return [
            'id' => '标签 ID',
            'code' => '标签代码',
            'name' => '标签名称',
        ];
    }

    /**
     * 定义已明确的 B 模块关联，不在此阶段创建目标 Model。
     * 目标未就绪时明确报告依赖缺失，不返回伪造的空查询结果。
     */
    public function getMatchStoryTags(): ActiveQuery
    {
        if (!class_exists(MatchStoryTag::class)) {
            throw new InvalidConfigException('MatchStoryTag 尚未实现，故事标签关联需待该 Model 就绪后使用。');
        }

        return $this->hasMany(MatchStoryTag::class, ['tag_id' => 'id']);
    }
}
