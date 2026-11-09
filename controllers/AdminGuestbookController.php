<?php
/** WorldCup Journey | D | Authenticated moderation and administrator replies. */

namespace app\controllers;

use app\models\GuestbookMessage;
use app\models\GuestbookReply;
use Yii;
use yii\data\ActiveDataProvider;
use yii\filters\AccessControl;
use yii\filters\VerbFilter;
use yii\web\BadRequestHttpException;
use yii\web\ConflictHttpException;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\web\Response;

class AdminGuestbookController extends Controller
{
    public function behaviors(): array
    {
        return [
            'access' => ['class' => AccessControl::class, 'rules' => [['allow' => true, 'roles' => ['@']]]],
            'verbs' => [
                'class' => VerbFilter::class,
                'actions' => ['index' => ['GET', 'HEAD'], 'moderate' => ['POST'], 'reply' => ['POST']],
            ],
        ];
    }

    public function actionIndex(): string
    {
        $provider = new ActiveDataProvider([
            'query' => GuestbookMessage::find()->with('reply')->orderBy(['id' => SORT_DESC]),
            'pagination' => ['pageSize' => 20],
        ]);

        return $this->render('index', compact('provider'));
    }

    public function actionModerate($id): Response
    {
        $status = $this->request->post('status');
        if (!in_array($status, [GuestbookMessage::APPROVED, GuestbookMessage::REJECTED], true)) {
            throw new BadRequestHttpException('无效的审核状态。');
        }

        $message = $this->findMessage($id);
        $message->updateAttributes(['status' => $status]);
        Yii::$app->session->setFlash('success', '审核状态已更新。');
        return $this->redirect(['index'], 303);
    }

    public function actionReply($id): Response
    {
        $message = $this->findMessage($id);
        $reply = new GuestbookReply(['content' => $this->request->post('content')]);
        if (!$reply->validate()) {
            throw new BadRequestHttpException('请输入 1 至 2000 字的回复。');
        }

        Yii::$app->db->transaction(function ($db) use ($message, $reply): void {
            // Serialize reply creation and moderation on the same message row.
            $status = $db->createCommand(
                'SELECT status FROM {{guestbook_message}} WHERE id = :id FOR UPDATE',
                [':id' => $message->id],
            )->queryScalar();

            if ($status !== GuestbookMessage::APPROVED) {
                throw new ConflictHttpException('只有已通过审核的留言可以回复。');
            }
            if (GuestbookReply::find()->where(['message_id' => $message->id])->exists()) {
                throw new ConflictHttpException('这条留言已有回复。');
            }

            $reply->message_id = $message->id;
            $reply->admin_user_id = Yii::$app->user->id;
            $reply->insert(false);
        });

        Yii::$app->session->setFlash('success', '回复已发布。');
        return $this->redirect(['index'], 303);
    }

    private function findMessage($id): GuestbookMessage
    {
        if (!is_scalar($id) || !preg_match('/\A[1-9][0-9]{0,19}\z/', (string) $id)) {
            throw new BadRequestHttpException('无效的留言编号。');
        }

        $message = GuestbookMessage::findOne(['id' => $id]);
        if (!$message) {
            throw new NotFoundHttpException('找不到该留言。');
        }

        return $message;
    }
}
