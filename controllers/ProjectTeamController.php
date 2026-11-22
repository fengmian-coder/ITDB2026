<?php
/**
 * WorldCup Journey - 关于我们、成员介绍与分类作业。
 * 模块：A 团队资料；负责人：A。
 * 依赖：ProjectTeam、ProjectMember、views/project-team。
 * 最近修改：2026-11-18。
 */
declare(strict_types=1);

namespace app\controllers;

use app\models\ProjectTeam;
use app\models\ProjectMember;
use yii\filters\VerbFilter;
use yii\web\BadRequestHttpException;
use yii\web\Controller;
use yii\web\NotFoundHttpException;

class ProjectTeamController extends Controller
{
    public function behaviors(): array
    {
        return ['verbs' => [
            'class' => VerbFilter::class,
            'actions' => ['index' => ['GET', 'HEAD'], 'member' => ['GET', 'HEAD']],
        ]];
    }

    public function actionIndex(): string
    {
        $teams = ProjectTeam::find()->with(['members.documents', 'documents'])->orderBy(['id' => SORT_ASC])->all();
        $profiles = json_decode(file_get_contents(\Yii::getAlias('@app/data/project-members.json')), true, 512, JSON_THROW_ON_ERROR);
        $studentNumbers = array_column($profiles, 'student_no', 'display_name');
        return $this->render('index', compact('teams', 'studentNumbers'));
    }

    public function actionMember(mixed $id): string
    {
        if ((!is_string($id) && !is_int($id)) || !preg_match('/\A[1-9][0-9]{0,18}\z/', (string) $id)
            || (strlen((string) $id) === 19 && strcmp((string) $id, (string) PHP_INT_MAX) > 0)) {
            throw new BadRequestHttpException('成员编号无效');
        }
        $member = ProjectMember::find()->where(['id' => $id])->with(['projectTeam', 'documents'])->one()
            ?? throw new NotFoundHttpException('找不到该成员');
        $documents = $member->documents;
        usort($documents, static fn ($left, $right): int => strnatcasecmp($left->title, $right->title)
            ?: ((int) $left->id <=> (int) $right->id));
        return $this->render('member', compact('member', 'documents'));
    }
}
