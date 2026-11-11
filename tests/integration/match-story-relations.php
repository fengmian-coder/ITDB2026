<?php
/**
 * WorldCup Journey - 已发布故事标签预加载回归检查
 * 模块：B；负责人：B；依赖：MatchQueryService、MatchStory、Yii 2.0
 * 最近修改：2026-11-10
 */
declare(strict_types=1);

require dirname(__DIR__, 2) . '/vendor/autoload.php';
require dirname(__DIR__, 2) . '/vendor/yiisoft/yii2/Yii.php';
Yii::setAlias('@app', dirname(__DIR__, 2));

// 重现 Yii 在关联预加载阶段清空 primaryModel 的状态，无需数据库连接。
$method = new ReflectionMethod(app\services\MatchQueryService::class, 'publishedStoryQuery');
$query = $method->invoke(new app\services\MatchQueryService(), 1);
$relation = (new app\models\MatchStory())->getTags();
$junction = $relation->via;
$relation->primaryModel = null;

$assertions = 0;
$check = static function (bool $condition, string $message) use (&$assertions): void {
    if (!$condition) {
        throw new RuntimeException($message);
    }
    ++$assertions;
};

($query->with['tags'])($relation);
$check($relation->via === $junction, '预加载回调必须保留 Model 已定义的关联');
$check($relation->via[0] === 'matchStoryTags', '必须通过故事标签关联表读取');
$check($relation->orderBy === ['match_tag.id' => SORT_ASC], '标签顺序须稳定');
$check(array_values($relation->select) === ['match_tag.id', 'match_tag.code', 'match_tag.name'], '仅读取已约定标签字段');
$check($query->where === ['match_id' => 1, 'is_published' => 1], '公开查询须保留比赛和发布状态条件');
echo "PASS {$assertions} assertions; no database connection opened.\n";
