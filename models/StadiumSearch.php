<?php
/**
 * WorldCup Journey - 后台场馆列表筛选
 * 模块：B 赛事与比赛后端；负责人：B
 * 依赖：Stadium、Yii 2.0 ActiveDataProvider
 * 最近修改：2026-11-05
 */
declare(strict_types=1);

namespace app\models;

use yii\base\Model;
use yii\data\ActiveDataProvider;
use yii\web\BadRequestHttpException;

class StadiumSearch extends Model
{
    public $name;
    public $city;
    public $country_code;

    public function rules(): array
    {
        return [
            [['name', 'city'], 'filter', 'filter' => static fn ($value) => is_string($value) ? trim($value) : $value],
            ['name', 'string', 'max' => 120],
            ['city', 'string', 'max' => 100],
            ['country_code', 'in', 'range' => ['CA', 'MX', 'US'], 'strict' => true],
        ];
    }

    public function attributeLabels(): array
    {
        return ['name' => '场馆名称', 'city' => '所在城市', 'country_code' => '国家'];
    }

    public function search(array $params = []): ActiveDataProvider
    {
        $input = $params[$this->formName()] ?? [];
        if (!is_array($input)) {
            throw new BadRequestHttpException('场馆筛选格式无效。');
        }
        foreach (['name', 'city', 'country_code'] as $key) {
            $value = $input[$key] ?? null;
            if ($value !== null && !is_string($value)) {
                throw new BadRequestHttpException('场馆筛选字段必须为字符串。');
            }
            $this->$key = $value;
        }
        if (!$this->validate()) {
            throw new BadRequestHttpException('场馆筛选参数无效，请检查字段长度及国家代码。');
        }
        $page = $this->positivePage($params, 'page', 1, PHP_INT_MAX);
        $size = $this->positivePage($params, 'per-page', 20, 100);
        if ($page - 1 > intdiv(PHP_INT_MAX, $size)) {
            throw new BadRequestHttpException('分页偏移超出有效范围。');
        }
        $sort = $params['sort'] ?? null;
        if ($sort !== null && (!is_string($sort) || !in_array($sort,
            ['name', '-name', 'city', '-city', 'country_code', '-country_code', 'id', '-id'], true))) {
            throw new BadRequestHttpException('场馆排序参数无效。');
        }
        $order = $sort === null ? ['name' => SORT_ASC, 'id' => SORT_ASC]
            : [ltrim($sort, '-') => str_starts_with($sort, '-') ? SORT_DESC : SORT_ASC];
        if (!isset($order['id'])) {
            $order['id'] = SORT_ASC;
        }
        $query = Stadium::find()->andFilterWhere(['country_code' => $this->country_code])
            ->andFilterWhere(['like', 'name', $this->name])->andFilterWhere(['like', 'city', $this->city]);

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
            throw new BadRequestHttpException('分页参数必须为正整数。');
        }
        $digits = ltrim((string) $value, '0');
        $max = (string) $maximum;
        if ($digits === '' || strlen($digits) > strlen($max)
            || (strlen($digits) === strlen($max) && strcmp($digits, $max) > 0)) {
            throw new BadRequestHttpException('分页参数超出有效范围。');
        }
        return (int) $digits;
    }
}
