<?php
/**
 * WorldCup Journey - 48 支球队列表与搜索
 * 模块：C 球队与统计
 * 负责人：C
 * 依赖：NationalTeamController、ActiveDataProvider
 * 最近修改：2026-11-11，B/C 联调修复 GET 搜索路由参数
 */

declare(strict_types=1);

use yii\bootstrap5\LinkPager;
use yii\helpers\Html;
use yii\helpers\Url;

/** @var yii\web\View $this */
/** @var yii\data\ActiveDataProvider $dataProvider */
/** @var string $q */

$this->title = '球队探索 | WorldCup Journey';
$this->params['breadcrumbs'][] = '球队列表';

$teams = $dataProvider->getModels();
$pagination = $dataProvider->pagination;
?>
<div class="national-team-index">
    <div class="mb-4">
        <div class="d-flex flex-wrap justify-content-between align-items-start gap-3">
        <div>
            <h1 class="h2 mb-1">探索每一支球队</h1>
            <p class="text-body-secondary mb-0">搜索球队，查看比赛历程和相关比赛</p>
        </div>
        <?= Html::a('球队对比', ['/national-team/compare'], ['class' => 'btn btn-outline-primary text-nowrap']) ?>
        </div>
        <?= Html::beginForm(['/national-team/index'], 'get', ['class' => 'd-flex gap-2 w-100 mt-4', 'role' => 'search']) ?>
            <label for="team-q" class="visually-hidden">搜索球队</label>
            <input id="team-q" type="search" name="q" value="<?= Html::encode($q) ?>"
                   class="form-control" placeholder="中文名 / 英文名 / FIFA 代码" maxlength="80">
            <button type="submit" class="btn btn-primary text-nowrap">搜索</button>
            <?php if ($q !== ''): ?>
                <a class="btn btn-outline-secondary text-nowrap" href="<?= Url::to(['/national-team/index']) ?>">清除</a>
            <?php endif; ?>
        <?= Html::endForm() ?>
        <p class="small text-body-secondary mt-2 mb-0"><?= $q === '' ? '2026 参赛球队' : '匹配结果' ?>：<?= (int) $pagination->totalCount ?> 支</p>
    </div>

    <?php if ($teams === []): ?>
        <div class="border rounded p-4 text-body-secondary">
            <?= $q === '' ? '暂无可展示的参赛球队' : '没有匹配的球队，请尝试其他关键词' ?>
        </div>
    <?php else: ?>
        <div class="row g-3">
            <?php foreach ($teams as $team): ?>
                <div class="col-12 col-sm-6 col-lg-3">
                    <a class="wc-team-card" href="<?= Url::to(['/national-team/view', 'id' => $team->id]) ?>">
                        <?= $this->render('_flag', ['team' => $team, 'size' => 'sm']) ?>
                        <div class="wc-team-card-copy">
                            <h2><?= Html::encode($team->name_zh) ?></h2>
                            <p><?= Html::encode($team->name_en) ?></p>
                        </div>
                    </a>
                </div>
            <?php endforeach; ?>
        </div>

        <nav class="wc-team-pagination mt-4" aria-label="球队列表分页">
            <?= LinkPager::widget(['pagination' => $pagination, 'prevPageLabel' => '上一页', 'nextPageLabel' => '下一页']) ?>
        </nav>
    <?php endif; ?>
</div>
