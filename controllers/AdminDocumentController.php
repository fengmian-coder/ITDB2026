<?php
/**
 * WorldCup Journey - 管理员上传、替换及删除课程作业。
 * 模块：A 作业下载；负责人：A。
 * 依赖：ProjectDocument、DocumentStorageService、D 的 AdminUser。
 * 最近修改：2026-11-10。
 */
declare(strict_types=1);
namespace app\controllers;

use app\models\AdminUser;
use app\models\ProjectDocument;
use app\models\ProjectMember;
use app\models\ProjectTeam;
use app\services\DocumentStorageService;
use Yii;
use yii\data\ActiveDataProvider;
use yii\filters\AccessControl;
use yii\filters\VerbFilter;
use yii\helpers\ArrayHelper;
use yii\web\BadRequestHttpException;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\web\Response;
use yii\web\UploadedFile;

class AdminDocumentController extends Controller
{
    public $layout = 'admin';

    public function behaviors(): array
    {
        return [
            'access' => ['class' => AccessControl::class, 'rules' => [[
                'allow' => true, 'roles' => ['@'],
                'matchCallback' => static fn () => AdminUser::canManage(Yii::$app->user->identity),
            ]]],
            'verbs' => ['class' => VerbFilter::class, 'actions' => [
                'index' => ['GET', 'HEAD'], 'create' => ['GET', 'HEAD', 'POST'],
                'update' => ['GET', 'HEAD', 'POST'], 'delete' => ['POST'],
            ]],
        ];
    }

    public function actionIndex(): string
    {
        $provider = new ActiveDataProvider([
            'query' => ProjectDocument::find()->with(['projectTeam', 'member'])->orderBy(['id' => SORT_DESC]),
            'pagination' => ['pageSize' => 20],
        ]);
        return $this->render('index', compact('provider'));
    }

    public function actionCreate(): string|Response
    {
        return $this->edit(new ProjectDocument(['document_type' => 'TEAM']));
    }

    public function actionUpdate(mixed $id): string|Response
    {
        return $this->edit($this->findDocument($id));
    }

    public function actionDelete(mixed $id): Response
    {
        if ($this->request->post('confirm_delete') !== '1') {
            throw new BadRequestHttpException('请先确认删除');
        }
        (new DocumentStorageService())->delete($this->findDocument($id));
        Yii::$app->session->setFlash('success', '作业文件已删除');
        return $this->redirect(['index'], 303);
    }

    private function edit(ProjectDocument $model): string|Response
    {
        if ($this->request->isPost) {
            $input = $this->request->post($model->formName());
            if (!is_array($input)) {
                throw new BadRequestHttpException('作业表单格式无效');
            }
            $input = array_intersect_key($input, array_flip(['project_team_id', 'member_id', 'document_type', 'title']));
            foreach ($input as $value) {
                if ($value !== null && !is_string($value) && !is_int($value)) {
                    throw new BadRequestHttpException('字段格式无效');
                }
            }
            $model->setAttributes($input);
            $model->uploadFile = UploadedFile::getInstance($model, 'uploadFile');
            if ((new DocumentStorageService())->save($model)) {
                Yii::$app->session->setFlash('success', '作业文件已保存，前台可下载');
                return $this->redirect(['index'], 303);
            }
            $this->response->statusCode = 422;
        }
        $teams = ArrayHelper::map(ProjectTeam::find()->orderBy(['id' => SORT_ASC])->all(), 'id', 'name');
        $members = [];
        foreach (ProjectMember::find()->with('projectTeam')->orderBy(['sort_order' => SORT_ASC, 'id' => SORT_ASC])->all() as $member) {
            $members[$member->projectTeam->name][$member->id] = $member->display_name;
        }
        return $this->render('form', compact('model', 'teams', 'members'));
    }

    private function findDocument(mixed $id): ProjectDocument
    {
        if ((!is_string($id) && !is_int($id)) || !preg_match('/\A[1-9][0-9]{0,18}\z/', (string) $id)) {
            throw new BadRequestHttpException('文件编号无效');
        }
        return ProjectDocument::findOne(['id' => $id]) ?? throw new NotFoundHttpException('找不到该文件');
    }
}
