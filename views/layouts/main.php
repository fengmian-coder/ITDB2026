<?php

/**
 * WorldCup Journey - 公共前台布局，统一中文页面语言。
 * 模块：A 公共界面；负责人：A。
 * 依赖：公共资源、页头页脚与各成员业务 View；最近修改：2026-11-10。
 */

declare(strict_types=1);

/** @var yii\web\View $this */
/** @var string $content */

use app\widgets\Alert;
use yii\bootstrap5\Breadcrumbs;
use yii\helpers\Html;

$this->render('_head');
?>
<?php $this->beginPage() ?>
<!DOCTYPE html>
<html lang="zh-CN" class="h-100" data-bs-theme="light">
<head>
    <?php $this->head() ?>
    <title><?= Html::encode($this->title) ?></title>
</head>
<body class="d-flex flex-column h-100<?= !empty($this->params['wideLayout']) ? ' wc-homepage' : '' ?>">
<?php $this->beginBody() ?>

<?= $this->render('_header') ?>

<main id="main" class="flex-grow-1" role="main">
    <div class="container<?= !empty($this->params['wideLayout']) ? ' wc-home-container' : '' ?>">
        <?php if (!empty($this->params['breadcrumbs'])): ?>
            <?= Breadcrumbs::widget([
                'homeLink' => ['label' => '首页', 'url' => ['/site/index']],
                'links' => $this->params['breadcrumbs'],
            ]) ?>
        <?php endif ?>
        <?= Alert::widget() ?>
        <?= $content ?>
    </div>
</main>

<?= $this->render('_footer') ?>

<?php $this->endBody() ?>
</body>
</html>
<?php $this->endPage() ?>
