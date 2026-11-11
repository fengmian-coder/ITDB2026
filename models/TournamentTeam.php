<?php
/**
 * WorldCup Journey - 赛事参赛关系 ActiveRecord
 * 模块：C 球队与统计
 * 负责人：C
 * 依赖：Yii 2.0 ActiveRecord、tournament_team 表、Tournament、NationalTeam
 * 最近修改：2026-11-11，B 协助补充成绩类别展示标签
 */

declare(strict_types=1);

namespace app\models;

use yii\db\ActiveQuery;
use yii\db\ActiveRecord;

/**
 * 联合主键 (tournament_id, team_id)，不添加自增 id。
 * 该表表达“球队参加了哪一届赛事”，不能仅凭 national_team 存在就认定参赛。
 *
 * @property int|string $tournament_id
 * @property int|string $team_id
 * @property string $group_code
 * @property string|null $final_result_code
 * @property string|null $created_at
 * @property string|null $updated_at
 */
class TournamentTeam extends ActiveRecord
{
    public const FINAL_RESULT_CODES = [
        'GROUP', 'R32', 'R16', 'QF', 'FOURTH', 'THIRD', 'RUNNER_UP', 'CHAMPION',
    ];

    public const FINAL_RESULT_LABELS = [
        'GROUP' => '小组赛', 'R32' => '32 强', 'R16' => '16 强', 'QF' => '八强',
        'FOURTH' => '第四名', 'THIRD' => '季军', 'RUNNER_UP' => '亚军', 'CHAMPION' => '冠军',
    ];

    public static function tableName(): string
    {
        return 'tournament_team';
    }

    public static function primaryKey(): array
    {
        return ['tournament_id', 'team_id'];
    }

    public function rules(): array
    {
        $hasValue = static fn (self $model, string $attribute): bool => $model->hasValue($attribute);

        return [
            [['tournament_id', 'team_id', 'group_code'], 'required'],
            [['tournament_id', 'team_id'], 'integer', 'min' => 1],
            [['tournament_id', 'team_id'], 'validateIdentifier'],
            ['group_code', 'in', 'range' => range('A', 'L'), 'strict' => true],
            ['final_result_code', 'in', 'range' => self::FINAL_RESULT_CODES, 'strict' => true,
                'skipOnEmpty' => false, 'when' => $hasValue],
            ['tournament_id', 'exist', 'targetClass' => Tournament::class,
                'targetAttribute' => ['tournament_id' => 'id']],
            ['team_id', 'exist', 'targetClass' => NationalTeam::class,
                'targetAttribute' => ['team_id' => 'id']],
            [['tournament_id', 'team_id'], 'unique',
                'targetAttribute' => ['tournament_id', 'team_id']],
        ];
    }

    public function attributeLabels(): array
    {
        return [
            'tournament_id' => '赛事 ID',
            'team_id' => '球队 ID',
            'group_code' => '小组代码',
            'final_result_code' => '最终成绩',
            'created_at' => '创建时间（UTC）',
            'updated_at' => '修改时间（UTC）',
        ];
    }

    public function getTeam(): ActiveQuery
    {
        return $this->hasOne(NationalTeam::class, ['id' => 'team_id']);
    }

    public function getTournament(): ActiveQuery
    {
        return $this->hasOne(Tournament::class, ['id' => 'tournament_id']);
    }

    /** 与 B 的 Model 一致：按 BIGINT UNSIGNED 上界校验，避免强转截断。 */
    public function validateIdentifier(string $attribute): void
    {
        $value = ltrim(ltrim(trim((string) $this->$attribute), '+'), '0');
        if (strlen($value) > 20 || (strlen($value) === 20 && strcmp($value, '18446744073709551615') > 0)) {
            $this->addError($attribute, 'ID 超出 BIGINT UNSIGNED 范围。');
        }
    }

    private function hasValue(string $attribute): bool
    {
        return $this->$attribute !== null && $this->$attribute !== '';
    }
}
