<?php
/**
 * WorldCup Journey - 比赛场馆 ActiveRecord
 * 模块：B 赛事与比赛后端
 * 负责人：B
 * 依赖：Yii 2.0 ActiveRecord、stadium 表（正式 DDL 待 C 提供）
 * 最近修改：2026-11-03
 */

declare(strict_types=1);

namespace app\models;

use yii\db\ActiveRecord;

/**
 * 对应 V1.1 已明确的场馆字段，不预设名称唯一性或默认时区。
 * 场馆的比赛关联待比赛 Model 交付后补充。
 *
 * @property int|string $id
 * @property string $name
 * @property string $city
 * @property string $country_code
 * @property string|null $timezone_name
 */
class Stadium extends ActiveRecord
{
    public static function tableName(): string
    {
        return 'stadium';
    }

    public function rules(): array
    {
        return [
            [['name', 'city', 'country_code'], 'required'],
            ['name', 'string', 'max' => 120],
            ['city', 'string', 'max' => 100],
            ['country_code', 'string', 'length' => 2],
            ['country_code', 'in', 'range' => ['CA', 'MX', 'US'], 'strict' => true],
            ['timezone_name', 'string', 'max' => 64],
        ];
    }

    public function attributeLabels(): array
    {
        return [
            'id' => '场馆 ID',
            'name' => '场馆名称',
            'city' => '所在城市',
            'country_code' => '国家代码',
            'timezone_name' => '场馆时区',
        ];
    }
}
