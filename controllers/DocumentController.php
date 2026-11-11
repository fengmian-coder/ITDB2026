<?php
/**
 * WorldCup Journey - 公开作业列表及受控附件下载。
 * 模块：A 作业下载；负责人：A。
 * 依赖：ProjectDocument、DocumentStorageService。
 * 最近修改：2026-11-10。
 */
declare(strict_types=1);
namespace app\controllers;

use app\models\ProjectDocument;
use app\services\DocumentStorageService;
use yii\data\ActiveDataProvider;
use yii\filters\VerbFilter;
use yii\web\BadRequestHttpException;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\web\Response;

class DocumentController extends Controller
{
    public function behaviors(): array
    {
        return ['verbs' => ['class' => VerbFilter::class,
            'actions' => ['index' => ['GET', 'HEAD'], 'download' => ['GET', 'HEAD']]]];
    }

    public function actionIndex(): string
    {
        $provider = new ActiveDataProvider([
            'query' => ProjectDocument::find()->with(['projectTeam', 'member'])->orderBy(['uploaded_at' => SORT_DESC, 'id' => SORT_DESC]),
            'pagination' => ['pageSize' => 20],
        ]);
        return $this->render('index', compact('provider'));
    }

    public function actionDownload(mixed $id): Response
    {
        if ((!is_string($id) && !is_int($id)) || !preg_match('/\A[1-9][0-9]{0,18}\z/', (string) $id)
            || (strlen((string) $id) === 19 && strcmp((string) $id, (string) PHP_INT_MAX) > 0)) {
            throw new BadRequestHttpException('文件编号无效');
        }
        $document = ProjectDocument::findOne(['id' => $id]) ?? throw new NotFoundHttpException('找不到该文件');
        $path = (new DocumentStorageService())->resolve($document);
        $this->response->headers->set('X-Content-Type-Options', 'nosniff');
        return $this->response->sendFile($path, $document->original_name, [
            'mimeType' => 'application/octet-stream', 'inline' => false,
        ]);
    }
}
