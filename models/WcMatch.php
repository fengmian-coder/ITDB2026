<?php
/**
 * WorldCup Journey - 比赛赛程与结果 ActiveRecord
 * 模块：B 赛事与比赛后端
 * 负责人：B
 * 依赖：Yii 2.0 ActiveRecord、Tournament、Stadium、wc_match 表
 * 待联调：C 的球队存在性、赛事参赛资格及球队关联；正式 DDL 尚待确认
 * 最近修改：2026-11-03
 */

declare(strict_types=1);

namespace app\models;

use yii\db\ActiveQuery;
use yii\db\ActiveRecord;

/**
 * 只使用 V1.1 已明确的列，不增加审计字段或冗余统计列。
 * 比分一致性在完整 validate()/save() 时检查，局部字段验证不能代替完整验证。
 *
 * @property int|string $id
 * @property int|string $tournament_id
 * @property int $match_no
 * @property int|string $home_team_id
 * @property int|string $away_team_id
 * @property int|string $stadium_id
 * @property string $kickoff_utc UTC，格式 Y-m-d H:i:s
 * @property string $stage_code
 * @property string|null $group_code
 * @property string $status
 * @property int|null $home_score_90
 * @property int|null $away_score_90
 * @property int|null $home_score_120
 * @property int|null $away_score_120
 * @property int|null $home_penalties
 * @property int|null $away_penalties
 * @property int|string|null $winner_team_id
 * @property string|null $summary
 */
class WcMatch extends ActiveRecord
{
    public const STAGE_CODES = ['GROUP', 'R32', 'R16', 'QF', 'SF', 'THIRD', 'FINAL'];
    public const STATUS_CODES = ['SCHEDULED', 'IN_PROGRESS', 'FINISHED', 'POSTPONED', 'CANCELLED'];

    public static function tableName(): string
    {
        return 'wc_match';
    }

    public function rules(): array
    {
        $hasValue = static fn (self $model, string $attribute): bool => $model->hasValue($attribute);

        return [
            ['status', 'default', 'value' => 'SCHEDULED',
                'when' => static fn (self $model): bool => !$model->hasValue('status')],
            [['tournament_id', 'match_no', 'home_team_id', 'away_team_id', 'stadium_id',
                'kickoff_utc', 'stage_code', 'status'], 'required'],
            [['tournament_id', 'home_team_id', 'away_team_id', 'stadium_id'], 'integer', 'min' => 1],
            ['winner_team_id', 'integer', 'min' => 1, 'skipOnEmpty' => false, 'when' => $hasValue],
            [['tournament_id', 'home_team_id', 'away_team_id', 'stadium_id', 'winner_team_id'],
                'validateIdentifier'],
            ['match_no', 'integer', 'min' => 1, 'max' => 104],
            ['kickoff_utc', 'datetime', 'format' => 'php:Y-m-d H:i:s',
                'strictDateFormat' => true, 'timeZone' => 'UTC'],
            ['stage_code', 'in', 'range' => self::STAGE_CODES, 'strict' => true],
            ['status', 'in', 'range' => self::STATUS_CODES, 'strict' => true],
            ['group_code', 'in', 'range' => range('A', 'L'), 'strict' => true,
                'skipOnEmpty' => false, 'when' => $hasValue],
            ['group_code', 'validateGroup'],
            [['home_score_90', 'away_score_90', 'home_score_120', 'away_score_120',
                'home_penalties', 'away_penalties'], 'integer', 'min' => 0, 'max' => 255,
                'skipOnEmpty' => false, 'when' => $hasValue],
            ['summary', 'string', 'max' => 300, 'skipOnEmpty' => false, 'when' => $hasValue],
            ['away_team_id', 'validateOpponents'],
            [['home_score_90', 'away_score_90'], 'validateScorePair',
                'params' => ['pair' => ['home_score_90', 'away_score_90']], 'skipOnEmpty' => false],
            [['home_score_120', 'away_score_120'], 'validateScorePair',
                'params' => ['pair' => ['home_score_120', 'away_score_120']], 'skipOnEmpty' => false],
            [['home_penalties', 'away_penalties'], 'validateScorePair',
                'params' => ['pair' => ['home_penalties', 'away_penalties']], 'skipOnEmpty' => false],
            ['home_score_120', 'validateExtraTime', 'params' => ['base' => 'home_score_90']],
            ['away_score_120', 'validateExtraTime', 'params' => ['base' => 'away_score_90']],
            ['status', 'validateScoreState'],
            ['winner_team_id', 'validateWinner', 'skipOnEmpty' => false],
            // 仅对已存在的 B 模块 Model 设置数据库验证；以下三项需要正式表。
            ['tournament_id', 'exist', 'targetClass' => Tournament::class,
                'targetAttribute' => ['tournament_id' => 'id']],
            ['stadium_id', 'exist', 'targetClass' => Stadium::class,
                'targetAttribute' => ['stadium_id' => 'id']],
            [['tournament_id', 'match_no'], 'unique',
                'targetAttribute' => ['tournament_id', 'match_no']],
            // TODO(B/C)：交接正式球队 Model 后补充存在性、参赛资格及球队关联。
        ];
    }

    public function attributeLabels(): array
    {
        return [
            'id' => '比赛 ID',
            'tournament_id' => '所属赛事',
            'match_no' => '比赛编号',
            'home_team_id' => '主队',
            'away_team_id' => '客队',
            'stadium_id' => '比赛场馆',
            'kickoff_utc' => '开球时间（UTC）',
            'stage_code' => '比赛阶段',
            'group_code' => '小组代码',
            'status' => '比赛状态',
            'home_score_90' => '主队 90 分钟比分',
            'away_score_90' => '客队 90 分钟比分',
            'home_score_120' => '主队含加时最终比分',
            'away_score_120' => '客队含加时最终比分',
            'home_penalties' => '主队点球大战比分',
            'away_penalties' => '客队点球大战比分',
            'winner_team_id' => '最终获胜球队',
            'summary' => '比赛摘要',
        ];
    }

    public function getTournament(): ActiveQuery
    {
        return $this->hasOne(Tournament::class, ['id' => 'tournament_id']);
    }

    public function getStadium(): ActiveQuery
    {
        return $this->hasOne(Stadium::class, ['id' => 'stadium_id']);
    }

    /** BIGINT UNSIGNED 的上界使用十进制字符串核对，避免强转或浮点比较丢失精度。 */
    public function validateIdentifier(string $attribute): void
    {
        $value = $this->identifier($attribute);
        if (strlen($value) > 20 || (strlen($value) === 20 && strcmp($value, '18446744073709551615') > 0)) {
            $this->addError($attribute, 'ID 超出 BIGINT UNSIGNED 范围');
        }
    }

    public function validateOpponents(string $attribute): void
    {
        if (!$this->hasErrors('home_team_id') && $this->identifier('home_team_id') === $this->identifier($attribute)) {
            $this->addError($attribute, '比赛双方不能是同一支球队');
        }
    }

    public function validateGroup(string $attribute): void
    {
        if (!$this->hasErrors('stage_code') && $this->stage_code !== 'GROUP') {
            $this->addError($attribute, '只有小组赛可以填写小组代码');
        }
    }

    public function validateScorePair(string $attribute, array $params): void
    {
        [$home, $away] = $params['pair'];
        if ($this->hasValue($home) !== $this->hasValue($away)) {
            $this->addError($attribute, '主客队对应比分必须成对填写或同时留空');
        }
    }

    public function validateExtraTime(string $attribute, array $params): void
    {
        $base = $params['base'];
        if (!$this->hasValue($base)) {
            $this->addError($attribute, '填写加时累计比分前必须填写 90 分钟比分');
        } elseif (!$this->hasErrors($base) && (int) $this->$attribute < (int) $this->$base) {
            $this->addError($attribute, '加时累计比分不能小于对应的 90 分钟比分');
        }
    }

    public function validateScoreState(string $attribute): void
    {
        if (in_array($this->$attribute, ['SCHEDULED', 'POSTPONED', 'CANCELLED'], true)) {
            foreach (['home_score_90', 'away_score_90', 'home_score_120', 'away_score_120',
                'home_penalties', 'away_penalties', 'winner_team_id'] as $field) {
                if ($this->hasValue($field) && !$this->hasErrors($field)) {
                    $this->addError($field, '未开始、延期或取消的比赛不能填写赛果');
                }
            }
        } elseif ($this->$attribute === 'FINISHED') {
            foreach (['home_score_90', 'away_score_90'] as $field) {
                if (!$this->hasValue($field) && !$this->hasErrors($field)) {
                    $this->addError($field, '已结束比赛必须填写 90 分钟比分');
                }
            }
        }
        // IN_PROGRESS 的具体状态流转尚待确认，本阶段不实现实时比分或状态转换流程。
    }

    public function validateWinner(string $attribute): void
    {
        if ($this->hasErrors('stage_code') || $this->hasErrors('status')) {
            return;
        }
        $hasWinner = $this->hasValue($attribute);
        if ($this->stage_code === 'GROUP') {
            if ($hasWinner) {
                $this->addError($attribute, '小组赛不填写最终获胜球队');
            }
            if ($this->hasValue('home_penalties') || $this->hasValue('away_penalties')) {
                $this->addError('home_penalties', '小组赛不使用点球决胜比分');
            }
            return;
        }
        if ($hasWinner && !$this->hasErrors('home_team_id') && !$this->hasErrors('away_team_id')) {
            $participants = [$this->identifier('home_team_id'), $this->identifier('away_team_id')];
            if (!in_array($this->identifier($attribute), $participants, true)) {
                $this->addError($attribute, '最终获胜球队必须是本场比赛双方之一');
                return;
            }
        }

        $penalties = $this->scorePair('home_penalties', 'away_penalties');
        // 最终场上比分只选 120/90 分钟字段，绝不加上点球大战命中数。
        $fieldScore = $this->scorePair('home_score_120', 'away_score_120')
            ?? $this->scorePair('home_score_90', 'away_score_90');
        if ($penalties !== null) {
            if ($penalties[0] === $penalties[1]) {
                $this->addError('home_penalties', '点球决胜比分必须能确定获胜一方');
                return;
            }
            if ($fieldScore !== null && $fieldScore[0] !== $fieldScore[1]) {
                $this->addError('home_penalties', '场上比分未打平时不能使用点球决胜比分');
            }
            $this->validateWinningSide($attribute, $penalties);
        } elseif ($this->status === 'FINISHED') {
            if (!$hasWinner) {
                $this->addError($attribute, '已结束的淘汰赛、季军赛或决赛必须填写最终获胜球队');
            } elseif ($fieldScore !== null) {
                if ($fieldScore[0] === $fieldScore[1]) {
                    $this->addError($attribute, '场上比分打平时需填写点球决胜结果，不能直接指定获胜方');
                } else {
                    $this->validateWinningSide($attribute, $fieldScore);
                }
            }
        }
    }

    private function validateWinningSide(string $attribute, array $scores): void
    {
        if (!$this->hasValue($attribute)) {
            $this->addError($attribute, '必须填写与决胜比分一致的最终获胜球队');
        } elseif (!$this->hasErrors('home_team_id') && !$this->hasErrors('away_team_id')) {
            $side = $scores[0] > $scores[1] ? 'home_team_id' : 'away_team_id';
            if ($this->identifier($attribute) !== $this->identifier($side)) {
                $this->addError($attribute, '最终获胜球队与比赛决胜比分不一致');
            }
        }
    }

    private function scorePair(string $home, string $away): ?array
    {
        if (!$this->hasValue($home) || !$this->hasValue($away) || $this->hasErrors($home) || $this->hasErrors($away)) {
            return null;
        }
        return [(int) $this->$home, (int) $this->$away];
    }

    private function hasValue(string $attribute): bool
    {
        return $this->$attribute !== null && $this->$attribute !== '';
    }

    private function identifier(string $attribute): string
    {
        return ltrim(ltrim(trim((string) $this->$attribute), '+'), '0');
    }
}
