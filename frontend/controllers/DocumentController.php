<?php
/**
 * WorldCup Journey - 旧作业入口兼容跳转及受控附件下载。
 * 模块：A 作业下载；负责人：A。
 * 依赖：ProjectDocument、DocumentStorageService。
 * 最近修改：2026-11-18，A 将作业目录整合到关于我们，下载逻辑保留。
 */
declare(strict_types=1);
namespace frontend\controllers;

use app\models\ProjectDocument;
use app\services\DocumentStorageService;
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

    public function actionIndex(): Response
    {
        return $this->redirect(['/project-team/index']);
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
