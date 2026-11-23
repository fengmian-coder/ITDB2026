<?php
/** Existing match actions, available before and after the story. */
declare(strict_types=1);
use yii\helpers\Html;
?>
<nav class="d-flex flex-wrap gap-2 my-3" aria-label="比赛互动">
    <?php if ($contextTeamId !== null): ?>
        <?= Html::a('制作这场比赛的纪念卡', ['/memory/card', 'team_id' => $contextTeamId, 'match_id' => $match['id']], ['class' => 'btn btn-primary']) ?>
    <?php else: ?>
        <?php foreach (['home', 'away'] as $side): ?>
            <?= Html::a(Html::encode('为' . ($match[$side . '_team']['name_zh'] ?? '球队 #' . $match[$side . '_team_id']) . '制作纪念卡'),
                ['/memory/card', 'team_id' => $match[$side . '_team_id'], 'match_id' => $match['id']], ['class' => 'btn btn-primary']) ?>
        <?php endforeach ?>
    <?php endif ?>
    <?= Html::a('为这场比赛留言', ['/guestbook/index', 'match_id' => $match['id']], ['class' => 'btn btn-outline-primary']) ?>
</nav>
