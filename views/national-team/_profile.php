<?php
/**
 * WorldCup Journey - 本届球队简介、主教练与代表球员
 * 模块：A/C 前台联调；负责人：A；依赖：TeamProfileService
 * 最近修改：2026-11-12
 */
declare(strict_types=1);
use yii\helpers\Html;
use app\models\TeamProfileForm;
?>
<section class="team-profile mb-5" aria-labelledby="team-profile-heading">
    <div class="team-profile-intro">
        <div class="team-profile-about">
            <p class="project-eyebrow">2026 · 球队档案</p>
            <h2 id="team-profile-heading">认识这支球队</h2>
            <p class="mb-0"><?= nl2br(Html::encode($profile['summary'])) ?></p>
            <?php if ($team->description && trim($team->description) !== trim($profile['summary'])): ?><p class="mt-3 mb-0"><?= nl2br(Html::encode($team->description)) ?></p><?php endif; ?>
        </div>
        <aside class="team-profile-coach" aria-labelledby="team-coach-heading">
            <p class="project-eyebrow">本届主教练</p>
            <h3 id="team-coach-heading"><?= Html::encode($profile['coach_name']) ?></h3>
            <p class="mb-0"><?= Html::encode($profile['coach_description']) ?></p>
        </aside>
    </div>
    <div class="d-flex flex-wrap justify-content-between align-items-baseline gap-2 mt-4 mb-3">
        <h2 class="h4 mb-0">代表球员</h2><span class="small text-body-secondary">本届名单节选，非完整阵容</span>
    </div>
    <div class="team-profile-players">
        <?php foreach ($profile['players'] as $player): ?>
        <article class="team-profile-player">
            <div class="d-flex align-items-center justify-content-between gap-2 mb-3"><span class="team-player-number">#<?= (int) $player['shirt_number'] ?></span><span class="team-player-position"><?= Html::encode(TeamProfileForm::POSITIONS[$player['position']]) ?></span></div>
            <h3><?= Html::encode($player['name']) ?></h3>
            <p class="mb-0"><?= Html::encode($player['description']) ?></p>
        </article>
        <?php endforeach; ?>
    </div>
    <details class="team-profile-source mt-3"><summary>资料来源与时点</summary>
        <p class="small mt-2 mb-1"><?= Html::a(Html::encode($profile['source_label']), $profile['source_url'], ['target' => '_blank', 'rel' => 'noopener noreferrer']) ?> · <?= Html::encode($profile['edition']) ?></p>
        <p class="small text-body-secondary mb-0">人物及所属俱乐部按本届名单版本展示，国家队累计出场和进球数据不等于本届赛事统计</p>
    </details>
</section>
