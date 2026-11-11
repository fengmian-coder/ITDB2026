<?php
/**
 * WorldCup Journey - 精选比赛事件 ActiveRecord
 * 模块：B 赛事与比赛后端
 * 负责人：B
 * 依赖：Yii 2.0 ActiveRecord、WcMatch、match_event 表
 * 规则依据：当前 C 的 install.sql 事件枚举、分钟约束及本场双方关系
 * 最近修改：2026-11-11
 */

declare(strict_types=1);

namespace app\models;

use yii\db\ActiveQuery;
use yii\db\ActiveRecord;

/**
 * 事件只用于时间轴展示，不作为球队进球统计来源。
 * 枚举与分钟约束对齐已交付的数据库结构。
 *
 * @property int|string $id
 * @property int|string $match_id
 * @property int|string|null $team_id
 * @property string|null $player_name
 * @property int|null $minute
 * @property int|null $added_minute
 * @property string $period_code
 * @property string $event_type
 * @property string|null $description
 * @property int $sort_order
 */
class MatchEvent extends ActiveRecord
{
    public const PERIOD_CODES = ['H1', 'H2', 'ET1', 'ET2', 'SHOOTOUT'];
    public const EVENT_TYPES = ['GOAL', 'OWN_GOAL', 'YELLOW', 'RED', 'PENALTY_GOAL', 'PENALTY_MISS', 'OTHER'];

    public static function tableName(): string
    {
        return 'match_event';
    }

    /** 显式按事件顺序升序查询；同值补序与跨时段排序待共同冻结。 */
    public static function find(): ActiveQuery
    {
        return parent::find()->orderBy(['sort_order' => SORT_ASC]);
    }

    public function rules(): array
    {
        $hasValue = static fn (self $model, string $attribute): bool => $model->hasValue($attribute);

        return [
            ['sort_order', 'default', 'value' => 0,
                'when' => static fn (self $model): bool => !$model->hasValue('sort_order')],
            [['match_id', 'period_code', 'event_type', 'sort_order'], 'required'],
            ['match_id', 'integer', 'min' => 1],
            ['team_id', 'integer', 'min' => 1, 'skipOnEmpty' => false, 'when' => $hasValue],
            [['match_id', 'team_id'], 'validateIdentifier'],
            [['minute', 'added_minute'], 'integer', 'min' => 0, 'max' => 65535,
                'skipOnEmpty' => false, 'when' => $hasValue],
            ['minute', 'required', 'when' => static fn (self $model): bool =>
                (in_array($model->period_code, self::PERIOD_CODES, true) && $model->period_code !== 'SHOOTOUT')
                || $model->hasValue('added_minute')],
            ['sort_order', 'integer', 'min' => -2147483648, 'max' => 2147483647],
            ['player_name', 'string', 'max' => 100, 'skipOnEmpty' => false, 'when' => $hasValue],
            ['description', 'string', 'max' => 500, 'skipOnEmpty' => false, 'when' => $hasValue],
            ['period_code', 'in', 'range' => self::PERIOD_CODES, 'strict' => true],
            ['event_type', 'in', 'range' => self::EVENT_TYPES, 'strict' => true],
            ['match_id', 'exist', 'targetClass' => WcMatch::class,
                'targetAttribute' => ['match_id' => 'id']],
            ['team_id', 'validateTeamMembership', 'when' => $hasValue],
        ];
    }

    public function attributeLabels(): array
    {
        return [
            'id' => '事件 ID',
            'match_id' => '所属比赛',
            'team_id' => '关联球队',
            'player_name' => '球员姓名',
            'minute' => '比赛分钟',
            'added_minute' => '补时分钟',
            'period_code' => '比赛时段代码',
            'event_type' => '事件类型',
            'description' => '事件描述',
            'sort_order' => '事件排序',
        ];
    }

    public function getMatch(): ActiveQuery
    {
        return $this->hasOne(WcMatch::class, ['id' => 'match_id']);
    }

    public function getTeam(): ActiveQuery
    {
        return $this->hasOne(NationalTeam::class, ['id' => 'team_id']);
    }

    public function validateTeamMembership(string $attribute): void
    {
        if ($this->hasErrors('match_id')) {
            return;
        }
        $match = WcMatch::find()->select(['home_team_id', 'away_team_id'])
            ->where(['id' => $this->match_id])->asArray()->one();
        if ($match === null) {
            return; // match_id 的 exist 规则提供所属比赛错误。
        }
        $teamId = ltrim(ltrim(trim((string) $this->$attribute), '+'), '0');
        if (!in_array($teamId, [(string) $match['home_team_id'], (string) $match['away_team_id']], true)) {
            $this->addError($attribute, '事件球队必须属于本场比赛双方');
        }
    }

    /** 与现有 WcMatch 一致，避免 BIGINT UNSIGNED 上界的浮点精度损失。 */
    public function validateIdentifier(string $attribute): void
    {
        $value = ltrim(ltrim(trim((string) $this->$attribute), '+'), '0');
        if (strlen($value) > 20 || (strlen($value) === 20 && strcmp($value, '18446744073709551615') > 0)) {
            $this->addError($attribute, 'ID 超出 BIGINT UNSIGNED 范围');
        }
    }

    private function hasValue(string $attribute): bool
    {
        return $this->$attribute !== null && $this->$attribute !== '';
    }
}
