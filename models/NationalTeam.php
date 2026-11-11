<?php
/**
 * WorldCup Journey - 国家队 ActiveRecord
 * 模块：C 球队与统计
 * 负责人：C
 * 依赖：Yii 2.0 ActiveRecord、national_team 表、TournamentTeam
 * 最近修改：2026-11-11，B 协助补充 FIFA 代码格式校验
 */

declare(strict_types=1);

namespace app\models;

use yii\db\ActiveQuery;
use yii\db\ActiveRecord;

/**
 * 只使用 V1.1 已明确的列和当前审计列；不新增 short_name/logo_url/abbreviation。
 * 公开列表默认只展示启用球队；按 ID 读取停用球队仍可用于历史关联。
 *
 * @property int|string $id
 * @property string $fifa_code
 * @property string $name_zh
 * @property string $name_en
 * @property string|null $flag_path
 * @property string|null $description
 * @property int|bool $is_active
 * @property string|null $created_at
 * @property string|null $updated_at
 */
class NationalTeam extends ActiveRecord
{
    public const ACTIVE = 1;
    public const INACTIVE = 0;

    public static function tableName(): string
    {
        return 'national_team';
    }

    public function rules(): array
    {
        $hasValue = static fn (self $model, string $attribute): bool => $model->hasValue($attribute);

        return [
            ['is_active', 'default', 'value' => self::ACTIVE,
                'when' => static fn (self $model): bool => !$model->hasValue('is_active')],
            [['fifa_code', 'name_zh', 'name_en'], 'required'],
            ['fifa_code', 'string', 'length' => 3],
            ['fifa_code', 'match', 'pattern' => '/^[A-Z]{3}$/D', 'message' => 'FIFA 代码须为三个大写英文字母。'],
            ['fifa_code', 'unique'],
            ['name_zh', 'string', 'max' => 80],
            ['name_en', 'string', 'max' => 100],
            ['flag_path', 'string', 'max' => 255, 'skipOnEmpty' => false, 'when' => $hasValue],
            ['flag_path', 'validateFlagPath'],
            ['description', 'string'],
            ['is_active', 'boolean'],
        ];
    }

    public function attributeLabels(): array
    {
        return [
            'id' => '球队 ID',
            'fifa_code' => 'FIFA 代码',
            'name_zh' => '中文名',
            'name_en' => '英文名',
            'flag_path' => '队徽路径',
            'description' => '球队简介',
            'is_active' => '是否启用',
            'created_at' => '创建时间（UTC）',
            'updated_at' => '修改时间（UTC）',
        ];
    }

    public function getTournamentTeams(): ActiveQuery
    {
        return $this->hasMany(TournamentTeam::class, ['team_id' => 'id']);
    }

    /** 公开列表/搜索默认只展示启用球队；不影响按 ID 读取历史球队。 */
    public static function findActive(): ActiveQuery
    {
        return static::find()->andWhere(['is_active' => self::ACTIVE]);
    }

    /** 仅检查相对路径用途；根目录、文件存在性与素材版权由 A/B 约定。 */
    public function validateFlagPath(string $attribute): void
    {
        $rawPath = $this->$attribute;
        if ($rawPath === null || $rawPath === '') {
            return;
        }
        $path = str_replace('\\', '/', trim((string) $rawPath));
        if (str_contains((string) $rawPath, "\0") || $path === '' || str_starts_with($path, '/')
            || preg_match('/^[a-z][a-z0-9+.-]*:/i', $path)
            || in_array('..', explode('/', $path), true)) {
            $this->addError($attribute, '队徽须使用相对路径，不能包含协议、绝对路径或上级目录跳转。');
        }
    }

    private function hasValue(string $attribute): bool
    {
        return $this->$attribute !== null && $this->$attribute !== '';
    }
}
