<?php
/** WorldCup Journey | D | Anonymous guestbook submissions and public listing. */

namespace app\controllers;

use app\models\GuestbookMessage;
use Yii;
use yii\data\ActiveDataProvider;
use yii\filters\VerbFilter;
use yii\web\BadRequestHttpException;
use yii\web\Controller;
use yii\web\Response;

class GuestbookController extends Controller
{
    public function behaviors(): array
    {
        return [
            'verbs' => [
                'class' => VerbFilter::class,
                'actions' => ['index' => ['GET', 'HEAD'], 'create' => ['POST']],
            ],
        ];
    }

    public function actionIndex(): string
    {
        $model = new GuestbookMessage(['related_match_id' => $this->request->get('match_id')]);
        return $this->renderIndex($model);
    }

    public function actionCreate(): string|Response
    {
        $model = new GuestbookMessage(['status' => GuestbookMessage::PENDING]);
        $data = array_intersect_key($this->request->post(), array_flip(['nickname', 'content', 'related_match_id']));
        foreach ($data as $value) {
            if (!is_string($value) && $value !== null) {
                throw new BadRequestHttpException('无效的留言字段。');
            }
        }

        if ($model->load($data, '') && $model->save()) {
            Yii::$app->session->setFlash('success', '留言已提交，审核通过后将公开显示。');
            return $this->redirect(['index'], 303);
        }

        $this->response->statusCode = 422;
        return $this->renderIndex($model);
    }

    private function renderIndex(GuestbookMessage $model): string
    {
        $provider = new ActiveDataProvider([
            'query' => GuestbookMessage::find()
                ->where(['status' => GuestbookMessage::APPROVED])
                ->with('reply')
                ->orderBy(['id' => SORT_DESC]),
            'pagination' => ['pageSize' => 20],
        ]);

        return $this->render('index', compact('model', 'provider'));
    }
}
