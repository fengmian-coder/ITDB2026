<?php
/** WorldCup Journey - 球队图文档案及完整名单，现有赛事统计由原页面继续展示。 */
declare(strict_types=1);
use app\models\TeamProfileForm;
use yii\helpers\Html;

$photos = [];
if ($feature['coach_photo'] !== null) {
    $photos[] = $feature['coach_photo'];
}
foreach ($feature['highlighted'] as $highlighted) {
    if ($highlighted['photo'] !== null) {
        $photos[] = $highlighted['photo'];
    }
}
?>
<section class="team-profile team-profile-feature mb-5" aria-labelledby="team-profile-heading">
    <div class="team-profile-intro">
        <div class="team-profile-about">
            <h2 id="team-profile-heading">球队概况</h2>
            <dl class="team-profile-facts">
                <?php foreach ($feature['facts'] as $fact): ?>
                    <div><dt><?= Html::encode($fact['label']) ?></dt><dd><?= Html::encode($fact['value']) ?></dd></div>
                <?php endforeach ?>
            </dl>
            <?php if ($profile['summary'] !== $feature['base_summary']): ?><p><?= nl2br(Html::encode($profile['summary'])) ?></p><?php endif ?>
            <div class="team-roster-counts" aria-label="阵容位置构成">
                <?php foreach (TeamProfileForm::POSITIONS as $position => $label): ?>
                    <span><?= Html::encode($label) ?> <strong><?= (int) $feature['counts'][$position] ?></strong></span>
                <?php endforeach ?>
            </div>
            <?php if ($team->description && trim($team->description) !== trim($profile['summary'])): ?><p class="mt-3 mb-0"><?= nl2br(Html::encode($team->description)) ?></p><?php endif ?>
        </div>
        <aside class="team-profile-coach team-coach-with-photo" aria-labelledby="team-coach-heading">
            <?php if ($feature['coach_photo'] !== null): ?>
                <?= $this->render('_profile_photo', ['photo' => $feature['coach_photo'], 'coachPhoto' => true, 'displayName' => $feature['coach_name_zh']]) ?>
            <?php endif ?>
            <div><p class="project-eyebrow">本届主教练</p>
                <h3 id="team-coach-heading"><?= Html::encode($feature['coach_name_zh']) ?></h3>
                <p class="team-person-original"><?= Html::encode($profile['coach_name']) ?></p>
                <p class="team-coach-facts mb-0"><?= Html::encode($feature['coach_facts']) ?></p>
            </div>
        </aside>
    </div>
    <div class="d-flex flex-wrap justify-content-between align-items-baseline gap-2 mt-4 mb-3">
        <h2 class="h4 mb-0">代表球员</h2><span class="small text-body-secondary">完整阵容见下方名单</span>
    </div>
    <div class="team-profile-players">
        <?php foreach ($profile['players'] as $player): $highlighted = $feature['highlighted'][$player['shirt_number']] ?? null; ?>
            <article class="team-profile-player team-player-visual<?= ($highlighted['photo'] ?? null) === null ? ' team-player-text-only' : '' ?>">
                <?php if (($highlighted['photo'] ?? null) !== null): ?>
                    <?= $this->render('_profile_photo', ['photo' => $highlighted['photo'], 'displayName' => $highlighted['name_zh']]) ?>
                <?php endif ?>
                <div class="team-player-copy">
                    <div class="d-flex align-items-center justify-content-between gap-2 mb-2"><span class="team-player-number">#<?= (int) $player['shirt_number'] ?></span><span class="team-player-position"><?= Html::encode(TeamProfileForm::POSITIONS[$player['position']]) ?></span></div>
                    <h3><?= Html::encode($highlighted['name_zh'] ?? $player['name']) ?><?php if ($highlighted !== null): ?> <span class="team-name-original">（<?= Html::encode($player['name']) ?>）</span><?php endif ?></h3>
                    <?php if ($highlighted !== null): ?>
                        <p class="team-player-club"><?= Html::encode($highlighted['club_zh']) ?> <span class="team-name-original">（<?= Html::encode($highlighted['club']) ?>）</span></p>
                        <p class="team-player-caps mb-0">国家队累计 <?= (int) $highlighted['caps'] ?> 场 · <?= (int) $highlighted['goals'] ?> 球</p>
                    <?php else: ?><p class="mb-0"><?= Html::encode($player['description']) ?></p><?php endif ?>
                </div>
            </article>
        <?php endforeach ?>
    </div>
    <details class="team-roster mt-4">
        <summary><strong>完整参赛名单（<?= count($feature['roster']) ?>人）</strong><span class="team-roster-expand" aria-hidden="true">展开 ＋</span><span class="team-roster-collapse" aria-hidden="true">收起 −</span></summary>
        <div class="team-roster-body">
            <div class="table-responsive">
                <table class="table team-roster-table mb-0" id="team-roster-table">
                    <caption class="visually-hidden"><?= Html::encode($team->name_zh) ?>本届完整参赛名单</caption>
                    <thead><tr><th scope="col">号码</th><th scope="col">球员</th><th scope="col">俱乐部</th></tr></thead>
                    <?php foreach (TeamProfileForm::POSITIONS as $position => $label): ?>
                        <tbody><tr class="team-roster-group"><th colspan="3" scope="colgroup"><?= Html::encode($label) ?> · <?= (int) $feature['counts'][$position] ?> 人</th></tr>
                            <?php foreach ($feature['roster'] as $member): if ($member['position'] !== $position) continue; ?>
                                <tr><td class="team-roster-number">#<?= (int) $member['shirt_number'] ?></td><th scope="row"><?= Html::encode($member['name_zh']) ?> <span class="team-roster-original">（<?= Html::encode($member['name']) ?>）</span></th><td><?= Html::encode($member['club_zh']) ?> <span class="team-roster-original">（<?= Html::encode($member['club']) ?>）</span></td></tr>
                            <?php endforeach ?>
                        </tbody>
                    <?php endforeach ?>
                </table>
            </div>
        </div>
    </details>
    <details class="team-profile-source mt-3"><summary>名单与图片来源</summary>
        <p class="small mt-2 mb-1"><?= Html::a(Html::encode($profile['source_label']), $profile['source_url'], ['target' => '_blank', 'rel' => 'noopener noreferrer']) ?> · <?= Html::encode($profile['edition']) ?></p>
        <p class="small text-body-secondary">人物与俱乐部按本届名单版本展示，国家队累计数据不等于本届赛事统计</p>
        <?php foreach (['history_source', 'translation_source'] as $sourceKey): ?>
            <p class="small mb-2"><?= Html::a(Html::encode($feature[$sourceKey]['label']), $feature[$sourceKey]['url'], ['target' => '_blank', 'rel' => 'noopener noreferrer']) ?></p>
        <?php endforeach ?>
        <?php foreach ($photos as $photo): ?>
            <p class="team-photo-attribution"><?= Html::a(Html::encode($photo['name']), $photo['source_url'], ['target' => '_blank', 'rel' => 'noopener noreferrer']) ?> · <?= Html::encode((($photo['date_kind'] ?? '') === 'accessed' ? '收录日期 ' : '') . $photo['date']) ?> · <?= Html::encode($photo['author']) ?> · <?= !empty($photo['license_url']) ? Html::a(Html::encode($photo['license']), $photo['license_url'], ['target' => '_blank', 'rel' => 'noopener noreferrer']) : Html::encode($photo['license']) ?></p>
        <?php endforeach ?>
        <?php if ($photos !== []): ?><p class="small text-body-secondary mb-0">卡片内的照片保存在本站，页面按版式裁切显示。逐图来源和使用说明见上方。</p><?php endif ?>
    </details>
</section>
