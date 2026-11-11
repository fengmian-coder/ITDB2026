<?php
/**
 * WorldCup Journey - 后台球队列表筛选
 * 模块：C 球队管理；负责人：C，B 协助实现
 * 依赖：NationalTeam、Yii 2.0 ActiveDataProvider
 * 最近修改：2026-11-11
 */
declare(strict_types=1);

namespace app\models;

use yii\base\Model;
use yii\data\ActiveDataProvider;
use yii\web\BadRequestHttpException;

class NationalTeamSearch extends Model
{
    public $name_zh;
    public $name_en;
    public $is_active;

    public function rules(): array
    {
        return [
            [['name_zh', 'name_en'], 'filter', 'filter' => static fn ($value) => is_string($value) ? trim($value) : $value],
            ['name_zh', 'string', 'max' => 80],
            ['name_en', 'string', 'max' => 100],
            ['is_active', 'in', 'range' => ['0', '1'], 'strict' => true],
        ];
    }

    public function attributeLabels(): array
    {
        return ['name_zh' => '球队名称', 'name_en' => '英文名', 'is_active' => '启用状态'];
    }

    public function search(array $params = []): ActiveDataProvider
    {
        $input = $params[$this->formName()] ?? [];
        if (!is_array($input)) {
            throw new BadRequestHttpException('球队筛选格式无效');
        }
        foreach (['name_zh', 'name_en', 'is_active'] as $key) {
            $value = $input[$key] ?? null;
            if ($value !== null && !is_string($value)) {
                throw new BadRequestHttpException('球队筛选字段必须为字符串');
            }
            $this->$key = $value;
        }
        if (!$this->validate()) {
            throw new BadRequestHttpException('球队筛选参数无效，请检查字段长度及启用状态');
        }
        $page = $this->positivePage($params, 'page', 1, PHP_INT_MAX);
        $size = $this->positivePage($params, 'per-page', 20, 100);
        if ($page - 1 > intdiv(PHP_INT_MAX, $size)) {
            throw new BadRequestHttpException('分页偏移超出有效范围');
        }
        $sort = $params['sort'] ?? null;
        if ($sort !== null && (!is_string($sort) || !in_array($sort,
            ['name_zh', '-name_zh', 'name_en', '-name_en', 'is_active', '-is_active', 'id', '-id'], true))) {
            throw new BadRequestHttpException('球队排序参数无效');
        }
        $order = $sort === null ? ['name_zh' => SORT_ASC, 'id' => SORT_ASC]
            : [ltrim($sort, '-') => str_starts_with($sort, '-') ? SORT_DESC : SORT_ASC];
        if (!isset($order['id'])) {
            $order['id'] = SORT_ASC;
        }
        $query = NationalTeam::find()->andFilterWhere(['is_active' => $this->is_active])
            ->andFilterWhere(['like', 'name_zh', $this->name_zh])->andFilterWhere(['like', 'name_en', $this->name_en]);

        return new ActiveDataProvider([
            'query' => $query->orderBy($order), 'sort' => false,
            'pagination' => [
                'page' => $page - 1, 'pageSize' => $size, 'defaultPageSize' => 20,
                'pageSizeLimit' => [1, 100], 'validatePage' => false, 'params' => $params,
            ],
        ]);
    }

    private function positivePage(array $params, string $key, int $default, int $maximum): int
    {
        if (!array_key_exists($key, $params)) {
            return $default;
        }
        $value = $params[$key];
        if ((!is_int($value) && !is_string($value)) || !preg_match('/^[0-9]+$/D', (string) $value)) {
            throw new BadRequestHttpException('分页参数必须为正整数');
        }
        $digits = ltrim((string) $value, '0');
        $max = (string) $maximum;
        if ($digits === '' || strlen($digits) > strlen($max)
            || (strlen($digits) === strlen($max) && strcmp($digits, $max) > 0)) {
            throw new BadRequestHttpException('分页参数超出有效范围');
        }
        return (int) $digits;
    }
}
