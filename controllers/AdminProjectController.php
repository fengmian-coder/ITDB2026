<?php
/**
 * WorldCup Journey - 管理员维护团队和成员资料。
 * 模块：A 团队资料；负责人：A。
 * 依赖：ProjectTeam、ProjectMember、D 的 AdminUser 与管理员会话。
 * 最近修改：2026-11-10。
 */
declare(strict_types=1);

namespace app\controllers;

use app\models\AdminUser;
use app\models\ProjectMember;
use app\models\ProjectTeam;
use Yii;
use yii\db\ActiveRecord;
use yii\db\IntegrityException;
use yii\db\Query;
use yii\filters\AccessControl;
use yii\filters\VerbFilter;
use yii\web\BadRequestHttpException;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\web\Response;

class AdminProjectController extends Controller
{
    public $layout = 'admin';

    public function behaviors(): array
    {
        return [
            'access' => [
                'class' => AccessControl::class,
                'rules' => [[
                    'allow' => true, 'roles' => ['@'],
                    'matchCallback' => static fn () => AdminUser::canManage(Yii::$app->user->identity),
                ]],
            ],
            'verbs' => [
                'class' => VerbFilter::class,
                'actions' => [
                    'index' => ['GET', 'HEAD'], 'view' => ['GET', 'HEAD'],
                    'create' => ['GET', 'HEAD', 'POST'], 'update' => ['GET', 'HEAD', 'POST'],
                    'create-member' => ['GET', 'HEAD', 'POST'], 'update-member' => ['GET', 'HEAD', 'POST'],
                    'delete' => ['POST'], 'delete-member' => ['POST'],
                ],
            ],
        ];
    }

    public function actionIndex(): string
    {
        $teams = ProjectTeam::find()->with('members')->orderBy(['id' => SORT_ASC])->all();
        return $this->render('index', compact('teams'));
    }

    public function actionView(mixed $id): string
    {
        return $this->render('view', ['team' => $this->findTeam($id)]);
    }

    public function actionCreate(): string|Response
    {
        return $this->editTeam(new ProjectTeam());
    }

    public function actionUpdate(mixed $id): string|Response
    {
        return $this->editTeam($this->findTeam($id));
    }

    public function actionCreateMember(mixed $team_id): string|Response
    {
        $team = $this->findTeam($team_id);
        $member = new ProjectMember(['project_team_id' => $team->id, 'sort_order' => 0]);
        return $this->editMember($member, $team);
    }

    public function actionUpdateMember(mixed $id): string|Response
    {
        $member = $this->findMember($id);
        return $this->editMember($member, $member->projectTeam);
    }

    public function actionDelete(mixed $id): Response
    {
        $this->requireConfirmation();
        $team = $this->findTeam($id);
        // 预检查用于友好反馈；数据库外键同时保护并发写入和其他模块的引用。
        $hasDocuments = (new Query())->from('project_document')->where(['project_team_id' => $team->id])->exists();
        if ($team->getMembers()->exists() || $hasDocuments) {
            Yii::$app->session->setFlash('warning', '该团队仍有成员或作业文件，请先处理关联资料');
            return $this->redirect(['view', 'id' => $team->id], 303);
        }
        try {
            $team->delete();
            Yii::$app->session->setFlash('success', '团队已删除');
        } catch (IntegrityException $exception) {
            Yii::warning(['operation' => 'delete-team', 'team_id' => $team->id], self::class);
            Yii::$app->session->setFlash('warning', '团队仍被其他资料引用，无法删除');
        }
        return $this->redirect(['index'], 303);
    }

    public function actionDeleteMember(mixed $id): Response
    {
        $this->requireConfirmation();
        $member = $this->findMember($id);
        $teamId = $member->project_team_id;
        try {
            $member->delete();
            Yii::$app->session->setFlash('success', '成员已删除');
        } catch (IntegrityException $exception) {
            Yii::warning(['operation' => 'delete-member', 'member_id' => $member->id], self::class);
            Yii::$app->session->setFlash('warning', '该成员已关联个人作业，无法删除');
        }
        return $this->redirect(['view', 'id' => $teamId], 303);
    }

    private function editTeam(ProjectTeam $model): string|Response
    {
        if ($this->request->isPost) {
            $this->loadFields($model, ['name', 'description', 'logo_path']);
            if ($model->save()) {
                Yii::$app->session->setFlash('success', '团队资料已保存，前台已同步更新');
                return $this->redirect(['view', 'id' => $model->id], 303);
            }
            $this->response->statusCode = 422;
        }
        return $this->render('team-form', compact('model'));
    }

    private function editMember(ProjectMember $model, ProjectTeam $team): string|Response
    {
        if ($this->request->isPost) {
            // 团队归属来自已查询的记录，不接受 POST 中的 id / project_team_id / updated_at。
            $this->loadFields($model, ['display_name', 'role_title', 'bio', 'sort_order']);
            try {
                if ($model->save()) {
                    Yii::$app->session->setFlash('success', '成员资料已保存，前台已同步更新');
                    return $this->redirect(['view', 'id' => $team->id], 303);
                }
            } catch (IntegrityException $exception) {
                $model->addError('display_name', '所属团队已变更，请刷新页面后重试');
            }
            $this->response->statusCode = 422;
        }
        return $this->render('member-form', compact('model', 'team'));
    }

    private function loadFields(ActiveRecord $model, array $fields): void
    {
        $input = $this->request->post($model->formName());
        if (!is_array($input)) {
            throw new BadRequestHttpException('表单格式无效');
        }
        $input = array_intersect_key($input, array_flip($fields));
        foreach ($input as $value) {
            if ($value !== null && !is_string($value) && !is_int($value)) {
                throw new BadRequestHttpException('字段格式无效');
            }
        }
        $model->setAttributes($input);
    }

    private function requireConfirmation(): void
    {
        if ($this->request->post('confirm_delete') !== '1') {
            throw new BadRequestHttpException('请先确认删除');
        }
    }

    private function positiveId(mixed $id): string
    {
        if ((!is_string($id) && !is_int($id)) || !preg_match('/\A[1-9][0-9]{0,19}\z/', (string) $id)) {
            throw new BadRequestHttpException('编号必须为正整数');
        }
        $id = (string) $id;
        if (strlen($id) === 20 && strcmp($id, '18446744073709551615') > 0) {
            throw new BadRequestHttpException('编号超出有效范围');
        }
        return $id;
    }

    private function findTeam(mixed $id): ProjectTeam
    {
        return ProjectTeam::findOne(['id' => $this->positiveId($id)])
            ?? throw new NotFoundHttpException('找不到该团队');
    }

    private function findMember(mixed $id): ProjectMember
    {
        return ProjectMember::findOne(['id' => $this->positiveId($id)])
            ?? throw new NotFoundHttpException('找不到该成员');
    }
}
