<?php
/**
 * WorldCup Journey - 后台赛事列表筛选
 * 模块：B 赛事与比赛后端；负责人：B
 * 依赖：Tournament、Yii 2.0 ActiveDataProvider
 * 最近修改：2026-11-05
 */
declare(strict_types=1);

namespace app\models;

use yii\base\Model;
use yii\data\ActiveDataProvider;
use yii\web\BadRequestHttpException;

class TournamentSearch extends Model
{
    public $year;
    public $name;

    public function rules(): array
    {
        return [
            ['year', 'integer', 'min' => 0, 'max' => 65535],
            ['name', 'filter', 'filter' => static fn ($value) => is_string($value) ? trim($value) : $value],
            ['name', 'string', 'max' => 120],
        ];
    }

    public function attributeLabels(): array
    {
        return ['year' => '赛事年份', 'name' => '赛事名称'];
    }

    /** 只接受自己的筛选字段；分页与排序也显式校验，不静默修正非法参数。 */
    public function search(array $params = []): ActiveDataProvider
    {
        $input = $params[$this->formName()] ?? [];
        if (!is_array($input)) {
            throw new BadRequestHttpException('赛事筛选格式无效。');
        }
        $this->year = $input['year'] ?? null;
        $this->name = $input['name'] ?? null;
        if ($this->name !== null && !is_string($this->name)) {
            throw new BadRequestHttpException('赛事名称筛选必须为字符串。');
        }
        if ($this->year !== null && $this->year !== ''
            && ((!is_int($this->year) && !is_string($this->year))
                || !preg_match('/^[0-9]+$/D', (string) $this->year))) {
            throw new BadRequestHttpException('赛事年份必须为非负整数。');
        }
        if (!$this->validate()) {
            throw new BadRequestHttpException('赛事筛选参数无效，请检查年份及名称长度。');
        }
        $page = $this->positivePage($params, 'page', 1, PHP_INT_MAX);
        $pageSize = $this->positivePage($params, 'per-page', 20, 100);
        if ($page - 1 > intdiv(PHP_INT_MAX, $pageSize)) {
            throw new BadRequestHttpException('分页偏移超出有效范围。');
        }
        $sort = $params['sort'] ?? null;
        if ($sort !== null && (!is_string($sort) || !in_array($sort,
            ['year', '-year', 'name', '-name', 'id', '-id'], true))) {
            throw new BadRequestHttpException('赛事排序参数无效。');
        }

        $query = Tournament::find()->andFilterWhere(['year' => $this->year])
            ->andFilterWhere(['like', 'name', $this->name]);
        $order = $sort === null ? ['year' => SORT_DESC, 'id' => SORT_DESC]
            : [ltrim($sort, '-') => str_starts_with($sort, '-') ? SORT_DESC : SORT_ASC];
        if (!isset($order['id'])) {
            $order['id'] = SORT_DESC;
        }
        $provider = new ActiveDataProvider([
            'query' => $query->orderBy($order),
            // 排序已经由白名单设置到查询，避免另一套请求解析覆盖校验结果。
            'sort' => false,
            'pagination' => [
                'page' => $page - 1, 'pageSize' => $pageSize, 'pageSizeLimit' => [1, 100],
                'defaultPageSize' => 20, 'validatePage' => false, 'params' => $params,
            ],
        ]);
        return $provider;
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
