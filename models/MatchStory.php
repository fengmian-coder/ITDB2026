<?php
/**
 * WorldCup Journey - 精选比赛故事 ActiveRecord
 * 模块：B 赛事与比赛后端
 * 负责人：B
 * 依赖：Yii 2.0 ActiveRecord、WcMatch、MatchStoryTag、MatchTag、match_story 表
 * 待确认：C 的正式 DDL、审计时间列属性和维护机制、正文与素材约定
 * 最近修改：2026-11-03
 */

declare(strict_types=1);

namespace app\models;

use yii\db\ActiveQuery;
use yii\db\ActiveRecord;

/**
 * 一场比赛最多一篇精选故事。前台 Service 必须先确认发布状态，
 * 未发布时不返回故事正文、对应事件时间轴或标签；后台仍需能查询草稿。
 * 不在 Model 中自动更新时间、净化或改写正文，也不默认过滤 find()。
 *
 * @property int|string $id
 * @property int|string $match_id
 * @property string $title
 * @property string $summary
 * @property string $body
 * @property string|null $cover_path
 * @property string|null $source_url
 * @property int|bool $is_published
 * @property string|null $created_at
 * @property string|null $updated_at
 */
class MatchStory extends ActiveRecord
{
    public static function tableName(): string
    {
        return 'match_story';
    }

    public function rules(): array
    {
        $hasValue = static fn (self $model, string $attribute): bool => $model->hasValue($attribute);

        return [
            ['is_published', 'default', 'value' => 0,
                'when' => static fn (self $model): bool => !$model->hasValue('is_published')],
            [['match_id', 'title', 'summary', 'body', 'is_published'], 'required'],
            ['match_id', 'integer', 'min' => 1],
            ['match_id', 'validateIdentifier'],
            ['title', 'string', 'max' => 150],
            ['summary', 'string', 'max' => 500],
            ['body', 'string'],
            ['cover_path', 'string', 'max' => 255, 'skipOnEmpty' => false, 'when' => $hasValue],
            ['cover_path', 'validateCoverPath'],
            ['source_url', 'string', 'max' => 600, 'skipOnEmpty' => false, 'when' => $hasValue],
            ['source_url', 'url', 'validSchemes' => ['http', 'https'],
                'skipOnEmpty' => false, 'when' => $hasValue],
            // 接受表单的 0/1 字符串、整数及 PHP 布尔值；不自动转换存储值。
            ['is_published', 'boolean'],
            // Q03 未冻结非空、默认值和自动维护机制，只校验已填写的 UTC 时间。
            [['created_at', 'updated_at'], 'string', 'skipOnEmpty' => false, 'when' => $hasValue],
            [['created_at', 'updated_at'], 'datetime', 'format' => 'php:Y-m-d H:i:s',
                'strictDateFormat' => true, 'timeZone' => 'UTC',
                'skipOnEmpty' => false, 'when' => $hasValue],
            ['match_id', 'exist', 'targetClass' => WcMatch::class,
                'targetAttribute' => ['match_id' => 'id']],
            ['match_id', 'unique'],
        ];
    }

    public function attributeLabels(): array
    {
        return [
            'id' => '故事 ID',
            'match_id' => '所属比赛',
            'title' => '故事标题',
            'summary' => '故事摘要',
            'body' => '故事正文',
            'cover_path' => '封面相对路径',
            'source_url' => '内容来源 URL',
            'is_published' => '是否发布',
            'created_at' => '创建时间（UTC）',
            'updated_at' => '修改时间（UTC）',
        ];
    }

    public function getMatch(): ActiveQuery
    {
        return $this->hasOne(WcMatch::class, ['id' => 'match_id']);
    }

    public function getMatchStoryTags(): ActiveQuery
    {
        return $this->hasMany(MatchStoryTag::class, ['story_id' => 'id']);
    }

    public function getTags(): ActiveQuery
    {
        return $this->hasMany(MatchTag::class, ['id' => 'tag_id'])->via('matchStoryTags');
    }

    /** 与 Yii boolean 验证的非严格 0/1 语义一致，不把任意非空值视为已发布。 */
    public function isPublished(): bool
    {
        return is_scalar($this->is_published) && $this->is_published == 1;
    }

    public function validateIdentifier(string $attribute): void
    {
        $value = ltrim(ltrim(trim((string) $this->$attribute), '+'), '0');
        if (strlen($value) > 20 || (strlen($value) === 20 && strcmp($value, '18446744073709551615') > 0)) {
            $this->addError($attribute, 'ID 超出 BIGINT UNSIGNED 范围。');
        }
    }

    /** 仅检查相对路径用途，不假定素材根目录、扩展名或文件已经存在。 */
    public function validateCoverPath(string $attribute): void
    {
        $rawPath = $this->$attribute;
        $path = str_replace('\\', '/', trim($rawPath));
        if (str_contains($rawPath, "\0") || $path === '' || str_starts_with($path, '/')
            || preg_match('/^[a-z][a-z0-9+.-]*:/i', $path)
            || in_array('..', explode('/', $path), true)) {
            $this->addError($attribute, '封面须使用相对路径，不能包含协议、绝对路径或上级目录跳转。');
        }
    }

    private function hasValue(string $attribute): bool
    {
        return $this->$attribute !== null && $this->$attribute !== '';
    }
}
