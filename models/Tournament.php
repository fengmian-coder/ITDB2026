<?php
/**
 * WorldCup Journey - 赛事信息 ActiveRecord
 * 模块：B 赛事与比赛后端
 * 负责人：B
 * 依赖：Yii 2.0 ActiveRecord、tournament 表（正式 DDL 待 C 提供）
 * 最近修改：2026-11-03
 */

declare(strict_types=1);

namespace app\models;

use yii\db\ActiveRecord;

/**
 * 对应 V1.1 已明确的赛事字段，不预设审计字段或数据库默认值。
 * 比赛和参赛关系关联待对应 Model 交付后补充。
 *
 * @property int|string $id
 * @property int $year
 * @property string $name
 * @property string $start_date
 * @property string $end_date
 * @property string|null $description
 */
class Tournament extends ActiveRecord
{
    public static function tableName(): string
    {
        return 'tournament';
    }

    public function rules(): array
    {
        return [
            [['year', 'name', 'start_date', 'end_date'], 'required'],
            ['year', 'integer', 'min' => 0, 'max' => 65535],
            ['name', 'string', 'max' => 120],
            [['start_date', 'end_date'], 'date', 'format' => 'php:Y-m-d', 'strictDateFormat' => true],
            ['description', 'string'],
            // V1.1 已明确 year 唯一；此项验证需要正式数据库及表。
            ['year', 'unique'],
        ];
    }

    public function attributeLabels(): array
    {
        return [
            'id' => '赛事 ID',
            'year' => '赛事年份',
            'name' => '赛事名称',
            'start_date' => '开始日期',
            'end_date' => '结束日期',
            'description' => '赛事简介',
        ];
    }
}
