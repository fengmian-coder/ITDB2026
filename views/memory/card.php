<?php
/**
 * WorldCup Journey - 球队比赛选择、个人感想与纪念卡图片预览。
 * 模块：A 无注册纪念卡；负责人：A。
 * 依赖：MemoryCardService、memory-card.js、浏览器 Canvas。
 * 最近修改：2026-11-10。
 */
declare(strict_types=1);
use yii\helpers\Html;
use yii\helpers\Json;
use yii\web\View;

$this->title = '制作世界杯纪念卡';
$this->params['breadcrumbs'][] = '纪念卡片';
$this->registerJsFile('@web/js/memory-card.js', ['position' => View::POS_END, 'depends' => [app\assets\AppAsset::class]]);
if ($card !== null) {
    $selectedTeam = array_column($teams, null, 'id')[$card['team_id']] ?? null;
    $card['flag_url'] = \app\components\TeamFlag::url($selectedTeam['fifa_code'] ?? null);
}
?>
<div class="project-page">
    <header class="project-intro"><h1>制作世界杯纪念卡</h1><p class="project-lead">选择球队和比赛，写下感想，把这一刻留在纪念卡里</p></header>
    <div class="memory-workspace">
        <section class="memory-controls" aria-label="纪念卡编辑">
            <h2>01 / 选择球队和比赛</h2>
            <?= Html::beginForm(['/memory/card'], 'get', ['id' => 'memory-selection']) ?>
            <label for="memory-team">选择球队</label>
            <?= Html::dropDownList('team_id', $selectedTeamId, array_column($teams, 'name_zh', 'id'), ['prompt' => '请选择球队', 'id' => 'memory-team', 'class' => 'form-select mb-3']) ?>
            <label for="memory-match">选择比赛</label>
            <?= Html::dropDownList('match_id', $selectedMatchId, $matches, ['prompt' => '请选择比赛（北京时间）', 'id' => 'memory-match', 'class' => 'form-select mb-3']) ?>
            <?= Html::submitButton('确认选择', ['class' => 'btn btn-outline-success w-100']) ?>
            <?= Html::endForm() ?>
            <p class="memory-hint mt-2">选择球队后点击“确认选择”，再选择比赛并再次确认</p>
            <h2 class="mt-4">02 / 填写感想与添加照片</h2>
            <label for="memory-thought">一句感想</label>
            <textarea id="memory-thought" class="form-control" maxlength="100" rows="3" placeholder="写下你的观赛感想"></textarea>
            <p class="memory-hint">最多 100 字，感想和照片不会上传到服务器</p>
            <label for="memory-photo">添加照片（可选）</label>
            <input id="memory-photo" class="form-control" type="file" accept="image/jpeg,image/png,image/webp">
            <p class="memory-hint">支持 JPG、PNG、WebP，不超过 5 MB</p>
            <button id="memory-download" type="button" class="btn btn-success w-100 mt-3" <?= $card === null ? 'disabled' : '' ?>>下载 PNG 纪念卡 ↓</button>
            <p id="memory-status" class="memory-hint mt-2" role="status" aria-live="polite"></p>
        </section>
        <section class="memory-preview" aria-label="纪念卡预览">
            <?php if ($card !== null): ?>
                <canvas id="memory-canvas" width="1200" height="800" role="img" aria-label="<?= Html::encode($card['team_name'] . '的世界杯纪念卡') ?>">纪念卡：<?= Html::encode($card['home_name'] . ' ' . $card['score'] . ' ' . $card['away_name']) ?></canvas>
                <script id="memory-data" type="application/json"><?= Json::htmlEncode($card) ?></script>
                <p class="memory-hint mt-3"><?= Html::a('查看这场比赛', ['/wc-match/view', 'id' => $card['match_id']]) ?> · <?= Html::a('查看球队旅程', ['/journey/view', 'team_id' => $card['team_id']]) ?></p>
            <?php else: ?>
                <div class="memory-placeholder"><h2>纪念卡预览</h2><p><?= $teams === [] ? '暂无可选球队和比赛数据' : '确认球队和比赛后，这里会显示纪念卡预览' ?></p></div>
            <?php endif ?>
        </section>
    </div>
    <noscript><p class="alert alert-info">请启用 JavaScript，以预览和下载纪念卡图片</p></noscript>
</div>
