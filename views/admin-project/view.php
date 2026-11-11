<?php
/**
 * WorldCup Journey - 团队详情、成员管理与删除确认。
 * 模块：A 团队资料；负责人：A。
 * 依赖：AdminProjectController、ProjectTeam / ProjectMember。
 * 最近修改：2026-11-10。
 */
declare(strict_types=1);
use yii\helpers\Html;

$this->title = $team->name;
$this->params['breadcrumbs'][] = ['label' => '团队与成员', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="project-admin">
    <div class="project-section-heading mb-4">
        <div><p class="project-eyebrow">团队资料</p><h1><?= Html::encode($team->name) ?></h1></div>
        <?= Html::a('编辑团队', ['update', 'id' => $team->id], ['class' => 'btn btn-success']) ?>
    </div>
    <div class="project-admin-panel mb-4">
        <h2>团队简介</h2>
        <p class="mb-0"><?= nl2br(Html::encode($team->description ?: '尚未填写团队简介')) ?></p>
    </div>
    <div class="project-section-heading mb-3">
        <h2>成员资料</h2>
        <?= Html::a('新增成员', ['create-member', 'team_id' => $team->id], ['class' => 'btn btn-outline-success']) ?>
    </div>
    <div class="table-responsive project-table-wrap">
        <table class="table align-middle mb-0">
            <thead><tr><th scope="col">顺序</th><th scope="col">成员</th><th scope="col">负责方向</th><th scope="col">操作</th></tr></thead>
            <tbody>
            <?php foreach ($team->members as $member): ?>
                <tr>
                    <td><?= Html::encode((string) $member->sort_order) ?></td>
                    <td><?= Html::encode($member->display_name) ?></td><td><?= Html::encode($member->role_title) ?></td>
                    <td>
                        <div class="d-flex flex-wrap gap-2 align-items-center">
                            <?= Html::a('编辑', ['update-member', 'id' => $member->id], ['class' => 'btn btn-sm btn-outline-success']) ?>
                            <?= Html::beginForm(['delete-member', 'id' => $member->id], 'post', ['class' => 'm-0']) ?>
                            <label class="visually-hidden" for="confirm-member-<?= Html::encode((string) $member->id) ?>">确认删除 <?= Html::encode($member->display_name) ?></label>
                            <label class="admin-delete-confirm"><?= Html::checkbox('confirm_delete', false, ['value' => '1', 'required' => true, 'id' => 'confirm-member-' . $member->id, 'aria-label' => '确认删除 ' . $member->display_name]) ?> 确认</label>
                            <?= Html::submitButton('删除', ['class' => 'btn btn-sm btn-outline-danger']) ?>
                            <?= Html::endForm() ?>
                        </div>
                    </td>
                </tr>
            <?php endforeach ?>
            <?php if ($team->members === []): ?><tr><td colspan="4" class="text-body-secondary">暂无成员，请新增</td></tr><?php endif ?>
            </tbody>
        </table>
    </div>
    <p class="text-body-secondary mt-2">成员按显示顺序从小到大排列；删除前请勾选确认框，已关联作业的成员无法删除</p>
    <details class="project-danger-zone mt-4">
        <summary>删除此团队</summary>
        <p class="mt-3">只有未关联成员及作业文件的团队可以删除</p>
        <?= Html::beginForm(['delete', 'id' => $team->id], 'post') ?>
        <label class="me-3"><?= Html::checkbox('confirm_delete', false, ['value' => '1', 'required' => true]) ?> 我确认删除此团队</label>
        <?= Html::submitButton('删除团队', ['class' => 'btn btn-outline-danger']) ?>
        <?= Html::endForm() ?>
    </details>
</div>
