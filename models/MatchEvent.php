<?php
/**
 * WorldCup Journey - 精选比赛事件 ActiveRecord
 * 模块：B 赛事与比赛后端
 * 负责人：B
 * 依赖：Yii 2.0 ActiveRecord、WcMatch、match_event 表
 * 待联调：C 的球队存在性和赛事归属；事件枚举与完整排序约定待确认
 * 最近修改：2026-11-03
 */

declare(strict_types=1);

namespace app\models;

use yii\db\ActiveQuery;
use yii\db\ActiveRecord;

/**
 * 事件只用于时间轴展示，不作为球队进球统计来源。
 * 不预设审计字段，不将 V1.1 的建议枚举当作已冻结白名单。
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
            ['sort_order', 'integer', 'min' => -2147483648, 'max' => 2147483647],
            ['player_name', 'string', 'max' => 100, 'skipOnEmpty' => false, 'when' => $hasValue],
            ['description', 'string', 'max' => 500, 'skipOnEmpty' => false, 'when' => $hasValue],
            // Q07 尚未冻结代码集合；目前不加入 in 验证或自定默认代码。
            ['period_code', 'string', 'max' => 12],
            ['event_type', 'string', 'max' => 20],
            ['match_id', 'exist', 'targetClass' => WcMatch::class,
                'targetAttribute' => ['match_id' => 'id']],
            // TODO(B/C)：正式交接后补充 team_id 的球队存在性、参赛资格和球队关联。
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
