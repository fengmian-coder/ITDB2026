<?php
/**
 * WorldCup Journey - 团队管理列表。
 * 模块：A 团队资料；负责人：A。
 * 依赖：AdminProjectController、ProjectTeam。
 * 最近修改：2026-11-10。
 */
declare(strict_types=1);
use yii\helpers\Html;

$this->title = '团队与成员';
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="project-admin">
    <div class="project-section-heading mb-4">
        <div><h1>团队与成员</h1><p class="text-body-secondary mb-0">维护团队介绍、成员资料与实际分工，保存后前台同步更新</p></div>
        <?= Html::a('新增团队', ['create'], ['class' => 'btn btn-success']) ?>
    </div>
    <?php if ($teams === []): ?>
        <div class="project-empty"><h2>还没有团队资料</h2><p>点击“新增团队”，建立团队的第一份介绍</p></div>
    <?php else: ?>
        <div class="table-responsive project-table-wrap">
            <table class="table align-middle mb-0">
                <thead><tr><th scope="col">团队名称</th><th scope="col">成员数</th><th scope="col">操作</th></tr></thead>
                <tbody>
                <?php foreach ($teams as $team): ?>
                    <tr>
                        <td><?= Html::encode($team->name) ?></td><td><?= count($team->members) ?></td>
                        <td><?= Html::a('管理资料', ['view', 'id' => $team->id], ['class' => 'btn btn-sm btn-outline-success']) ?></td>
                    </tr>
                <?php endforeach ?>
                </tbody>
            </table>
        </div>
    <?php endif ?>
</div>
