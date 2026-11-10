<?php
/**
 * WorldCup Journey - 故事与标签关联 ActiveRecord
 * 模块：B 赛事与比赛后端
 * 负责人：B
 * 依赖：Yii 2.0 ActiveRecord、MatchStory、MatchTag、match_story_tag 表
 * 待确认：C 的正式联合主键、外键、索引和删除规则
 * 最近修改：2026-11-03
 */

declare(strict_types=1);

namespace app\models;

use yii\db\ActiveQuery;
use yii\db\ActiveRecord;

/**
 * 仅由 story_id 和 tag_id 组成，不增加自增 id、审计列或默认值。
 * Model 的联合主键声明不能替代 C 在正式数据库中建立的联合主键约束。
 *
 * @property int|string $story_id
 * @property int|string $tag_id
 */
class MatchStoryTag extends ActiveRecord
{
    public static function tableName(): string
    {
        return 'match_story_tag';
    }

    public static function primaryKey(): array
    {
        return ['story_id', 'tag_id'];
    }

    public function rules(): array
    {
        return [
            [['story_id', 'tag_id'], 'required'],
            [['story_id', 'tag_id'], 'integer', 'min' => 1],
            [['story_id', 'tag_id'], 'validateIdentifier'],
            ['story_id', 'exist', 'targetClass' => MatchStory::class,
                'targetAttribute' => ['story_id' => 'id']],
            ['tag_id', 'exist', 'targetClass' => MatchTag::class,
                'targetAttribute' => ['tag_id' => 'id']],
            [['story_id', 'tag_id'], 'unique', 'targetAttribute' => ['story_id', 'tag_id']],
        ];
    }

    public function attributeLabels(): array
    {
        return [
            'story_id' => '故事 ID',
            'tag_id' => '标签 ID',
        ];
    }

    public function getStory(): ActiveQuery
    {
        return $this->hasOne(MatchStory::class, ['id' => 'story_id']);
    }

    public function getTag(): ActiveQuery
    {
        return $this->hasOne(MatchTag::class, ['id' => 'tag_id']);
    }

    public function validateIdentifier(string $attribute): void
    {
        $value = ltrim(ltrim(trim((string) $this->$attribute), '+'), '0');
        if (strlen($value) > 20 || (strlen($value) === 20 && strcmp($value, '18446744073709551615') > 0)) {
            $this->addError($attribute, 'ID 超出 BIGINT UNSIGNED 范围');
        }
    }
}
