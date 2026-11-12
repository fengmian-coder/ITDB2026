<?php
/**
 * WorldCup Journey - 本届球队轻量档案输入校验
 * 模块：A/C 资料联调；负责人：A；依赖：Yii Model
 * 最近修改：2026-11-12
 */
declare(strict_types=1);
namespace app\models;
use yii\base\Model;

class TeamProfileForm extends Model
{
    public const POSITIONS = ['GK' => '门将', 'DF' => '后卫', 'MF' => '中场', 'FW' => '前锋'];
    public $summary = '';
    public $coach_name = '';
    public $coach_description = '';
    public $players = [];
    public $source_url = '';
    public $source_label = '';
    public $edition = '';

    public function rules(): array
    {
        return [
            [['summary', 'coach_name', 'coach_description', 'source_url', 'source_label', 'edition'], 'required'],
            ['summary', 'string', 'max' => 2000],
            [['coach_name', 'source_label', 'edition'], 'string', 'max' => 160],
            ['coach_description', 'string', 'max' => 500],
            ['source_url', 'url', 'validSchemes' => ['http', 'https']],
            ['source_url', 'string', 'max' => 500],
            ['players', 'validatePlayers', 'skipOnEmpty' => false],
        ];
    }

    public function validatePlayers(string $attribute): void
    {
        if (!is_array($this->players) || array_keys($this->players) !== [0, 1, 2]) {
            $this->addError($attribute, '请填写三位代表球员'); return;
        }
        $names = [];
        foreach ($this->players as $player) {
            if (!is_array($player)) { $this->addError($attribute, '球员资料格式无效'); return; }
            foreach (['name' => 160, 'position' => 2, 'description' => 500] as $key => $max) {
                if (!isset($player[$key]) || !is_string($player[$key]) || trim($player[$key]) === '' || mb_strlen($player[$key]) > $max) {
                    $this->addError($attribute, '每位球员须填写有效的姓名、位置和简短介绍'); return;
                }
            }
            if (!isset(self::POSITIONS[$player['position']])) { $this->addError($attribute, '球员位置无效'); return; }
            $number = $player['shirt_number'] ?? null;
            if ((!is_int($number) && !is_string($number)) || !preg_match('/^(?:[1-9]|[1-9][0-9])$/D', (string) $number)) {
                $this->addError($attribute, '球衣号码须为 1—99'); return;
            }
            $names[] = trim($player['name']);
        }
        if (count(array_unique($names)) !== 3) $this->addError($attribute, '请选择三位不同的代表球员');
    }

    public function profile(): array
    {
        return ['summary' => trim($this->summary), 'coach_name' => trim($this->coach_name),
            'coach_description' => trim($this->coach_description),
            'players' => array_map(static fn ($row) => ['name' => trim($row['name']),
                'position' => $row['position'], 'description' => trim($row['description']),
                'shirt_number' => (int) $row['shirt_number']], $this->players),
            'source_url' => $this->source_url, 'source_label' => $this->source_label, 'edition' => $this->edition];
    }

    public function attributeLabels(): array
    {
        return ['summary' => '球队简介', 'coach_name' => '本届主教练', 'coach_description' => '教练介绍',
            'players' => '代表球员', 'source_url' => '来源链接', 'source_label' => '来源名称', 'edition' => '资料时点／版本'];
    }
}
