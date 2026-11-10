<?php
/**
 * WorldCup Journey - 后台比赛筛选与分页
 * 模块：B 赛事与比赛后端；负责人：B；依赖：WcMatch、ActiveDataProvider
 * 最近修改：2026-11-05
 */
declare(strict_types=1);
namespace app\models;

use yii\base\Model;
use yii\data\ActiveDataProvider;
use yii\web\BadRequestHttpException;

class WcMatchSearch extends Model
{
    public $tournament_id;
    public $team_id;
    public $stage_code;
    public $status;

    public function rules(): array
    {
        return [
            [['tournament_id', 'team_id'], 'safe'],
            ['stage_code', 'in', 'range' => WcMatch::STAGE_CODES, 'strict' => true],
            ['status', 'in', 'range' => WcMatch::STATUS_CODES, 'strict' => true],
        ];
    }

    public function attributeLabels(): array
    {
        return ['tournament_id' => '赛事 ID', 'team_id' => '球队 ID', 'stage_code' => '比赛阶段', 'status' => '比赛状态'];
    }

    public function search(array $params = []): ActiveDataProvider
    {
        $input = $params[$this->formName()] ?? [];
        if (!is_array($input)) {
            throw new BadRequestHttpException('比赛筛选格式无效');
        }
        foreach (['tournament_id', 'team_id'] as $attribute) {
            $value = $input[$attribute] ?? null;
            $this->$attribute = $value === null || $value === '' ? null : $this->positiveDigits($value, '18446744073709551615');
        }
        $this->stage_code = $input['stage_code'] ?? null;
        $this->status = $input['status'] ?? null;
        foreach (['stage_code', 'status'] as $attribute) {
            if ($this->$attribute !== null && !is_string($this->$attribute)) {
                throw new BadRequestHttpException('比赛阶段和状态筛选必须为字符串');
            }
        }
        if (!$this->validate()) {
            throw new BadRequestHttpException('比赛阶段或状态筛选无效');
        }
        $page = array_key_exists('page', $params) ? (int) $this->positiveDigits($params['page'], (string) PHP_INT_MAX) : 1;
        $size = array_key_exists('per-page', $params) ? (int) $this->positiveDigits($params['per-page'], '100') : 20;
        if ($page - 1 > intdiv(PHP_INT_MAX, $size)) {
            throw new BadRequestHttpException('分页偏移超出有效范围');
        }
        $sort = $params['sort'] ?? null;
        if ($sort !== null && (!is_string($sort) || !in_array($sort,
            ['id', '-id', 'kickoff_utc', '-kickoff_utc', 'match_no', '-match_no', 'stage_code', '-stage_code', 'status', '-status'], true))) {
            throw new BadRequestHttpException('比赛排序参数无效');
        }
        $order = $sort === null ? ['kickoff_utc' => SORT_ASC, 'match_no' => SORT_ASC]
            : [ltrim($sort, '-') => str_starts_with($sort, '-') ? SORT_DESC : SORT_ASC];
        if (!isset($order['id'])) {
            $order['id'] = SORT_ASC;
        }
        $query = WcMatch::find()->andFilterWhere(['tournament_id' => $this->tournament_id,
            'stage_code' => $this->stage_code, 'status' => $this->status]);
        if ($this->team_id !== null) {
            $query->andWhere(['or', ['home_team_id' => $this->team_id], ['away_team_id' => $this->team_id]]);
        }
        return new ActiveDataProvider([
            'query' => $query->orderBy($order), 'sort' => false,
            'pagination' => ['page' => $page - 1, 'pageSize' => $size, 'pageSizeLimit' => [1, 100],
                'defaultPageSize' => 20, 'validatePage' => false, 'params' => $params],
        ]);
    }

    private function positiveDigits(mixed $value, string $maximum): string
    {
        if ((!is_int($value) && !is_string($value)) || !preg_match('/^[0-9]+$/D', (string) $value)) {
            throw new BadRequestHttpException('ID 或分页参数必须为正整数');
        }
        $digits = ltrim((string) $value, '0');
        if ($digits === '' || strlen($digits) > strlen($maximum)
            || (strlen($digits) === strlen($maximum) && strcmp($digits, $maximum) > 0)) {
            throw new BadRequestHttpException('ID 或分页参数超出有效范围');
        }
        return $digits;
    }
}
