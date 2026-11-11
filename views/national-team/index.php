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
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-end gap-3 mb-4">
        <div>
            <h1 class="h2 mb-1">2026 参赛球队</h1>
            <p class="text-body-secondary mb-0">共 <?= (int) $pagination->totalCount ?> 支球队，点击查看球队 DNA。</p>
        </div>
        <?= Html::beginForm(['/national-team/index'], 'get', ['class' => 'd-flex gap-2', 'role' => 'search']) ?>
            <label for="team-q" class="visually-hidden">搜索球队</label>
            <input id="team-q" type="search" name="q" value="<?= Html::encode($q) ?>"
                   class="form-control" placeholder="中文名 / 英文名 / FIFA 代码" maxlength="80">
            <button type="submit" class="btn btn-primary">搜索</button>
            <?php if ($q !== ''): ?>
                <a class="btn btn-outline-secondary" href="<?= Url::to(['/national-team/index']) ?>">清除</a>
            <?php endif; ?>
        <?= Html::endForm() ?>
    </div>

    <?php if ($teams === []): ?>
        <div class="border rounded p-4 text-body-secondary">
            没有匹配的球队。请尝试其他关键词。
        </div>
    <?php else: ?>
        <div class="row g-3">
            <?php foreach ($teams as $team): ?>
                <div class="col-6 col-md-4 col-lg-3">
                    <div class="card h-100 border-0 shadow-sm">
                        <div class="card-body text-center">
                            <?= $this->render('_flag', ['team' => $team, 'size' => 'sm']) ?>
                            <h2 class="h5 mt-3 mb-1">
                                <?= Html::a(
                                    Html::encode($team->name_zh),
                                    ['/national-team/view', 'id' => $team->id],
                                    ['class' => 'text-decoration-none'],
                                ) ?>
                            </h2>
                            <p class="small text-body-secondary mb-2"><?= Html::encode($team->name_en) ?></p>
                            <span class="badge text-bg-dark"><?= Html::encode($team->fifa_code) ?></span>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <div class="mt-4">
            <?= LinkPager::widget(['pagination' => $pagination]) ?>
        </div>
    <?php endif; ?>
</div>
