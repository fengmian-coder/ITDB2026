<?php
/**
 * WorldCup Journey - 后台精选故事筛选
 * 模块：B 赛事与比赛后端；负责人：B
 * 依赖：MatchStory、Yii ActiveDataProvider
 * 最近修改：2026-11-05
 */
declare(strict_types=1);

namespace app\models;

use yii\base\Model;
use yii\data\ActiveDataProvider;
use yii\web\BadRequestHttpException;

class MatchStorySearch extends Model
{
    public $title;
    public $match_id;
    public $is_published;

    public function rules(): array
    {
        return [
            ['title', 'filter', 'filter' => static fn ($value) => is_string($value) ? trim($value) : $value],
            ['title', 'string', 'max' => 150],
            ['match_id', 'safe'],
            ['is_published', 'in', 'range' => [0, 1, '0', '1'], 'strict' => true],
        ];
    }

    public function attributeLabels(): array
    {
        return ['title' => '故事标题', 'match_id' => '比赛 ID', 'is_published' => '发布状态'];
    }

    public function search(array $params = []): ActiveDataProvider
    {
        $input = $params[$this->formName()] ?? [];
        if (!is_array($input)) {
            throw new BadRequestHttpException('故事筛选格式无效。');
        }
        $this->title = $input['title'] ?? null;
        if ($this->title !== null && !is_string($this->title)) {
            throw new BadRequestHttpException('故事标题筛选必须为字符串。');
        }
        $matchId = $input['match_id'] ?? null;
        $this->match_id = $matchId === null || $matchId === '' ? null
            : $this->positiveDigits($matchId, '18446744073709551615');
        $this->is_published = $input['is_published'] ?? null;
        if (is_bool($this->is_published) || is_array($this->is_published) || !$this->validate()) {
            throw new BadRequestHttpException('故事筛选参数无效，请检查标题长度和发布状态。');
        }
        $page = array_key_exists('page', $params) ? (int) $this->positiveDigits($params['page'], (string) PHP_INT_MAX) : 1;
        $size = array_key_exists('per-page', $params) ? (int) $this->positiveDigits($params['per-page'], '100') : 20;
        if ($page - 1 > intdiv(PHP_INT_MAX, $size)) {
            throw new BadRequestHttpException('分页偏移超出有效范围。');
        }
        $sort = $params['sort'] ?? null;
        if ($sort !== null && (!is_string($sort) || !in_array($sort,
            ['id', '-id', 'title', '-title', 'match_id', '-match_id', 'is_published', '-is_published'], true))) {
            throw new BadRequestHttpException('故事排序参数无效。');
        }
        $order = $sort === null ? ['id' => SORT_DESC]
            : [ltrim($sort, '-') => str_starts_with($sort, '-') ? SORT_DESC : SORT_ASC];
        if (!isset($order['id'])) {
            $order['id'] = SORT_DESC;
        }
        // 管理员必须看得到草稿；公开故事过滤仍由 MatchQueryService 负责。
        return new ActiveDataProvider([
            'query' => MatchStory::find()->andFilterWhere(['match_id' => $this->match_id])
                ->andFilterWhere(['is_published' => $this->is_published])
                ->andFilterWhere(['like', 'title', $this->title])->orderBy($order),
            'sort' => false,
            'pagination' => ['page' => $page - 1, 'pageSize' => $size, 'defaultPageSize' => 20,
                'pageSizeLimit' => [1, 100], 'validatePage' => false, 'params' => $params],
        ]);
    }

    private function positiveDigits(mixed $value, string $maximum): string
    {
        if ((!is_int($value) && !is_string($value)) || !preg_match('/^[0-9]+$/D', (string) $value)) {
            throw new BadRequestHttpException('比赛 ID 或分页参数必须为正整数。');
        }
        $digits = ltrim((string) $value, '0');
        if ($digits === '' || strlen($digits) > strlen($maximum)
            || (strlen($digits) === strlen($maximum) && strcmp($digits, $maximum) > 0)) {
            throw new BadRequestHttpException('比赛 ID 或分页参数超出有效范围。');
        }
        return $digits;
    }
}
