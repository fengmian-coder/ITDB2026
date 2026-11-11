<?php
/**
 * WorldCup Journey - 公开团队与成员介绍。
 * 模块：A 团队资料；负责人：A。
 * 依赖：ProjectTeam、ProjectMember、views/project-team。
 * 最近修改：2026-11-10。
 */
declare(strict_types=1);

namespace app\controllers;

use app\models\ProjectTeam;
use yii\filters\VerbFilter;
use yii\web\Controller;

class ProjectTeamController extends Controller
{
    public function behaviors(): array
    {
        return ['verbs' => [
            'class' => VerbFilter::class,
            'actions' => ['index' => ['GET', 'HEAD']],
        ]];
    }

    public function actionIndex(): string
    {
        $teams = ProjectTeam::find()->with('members')->orderBy(['id' => SORT_ASC])->all();
        return $this->render('index', compact('teams'));
    }
}
