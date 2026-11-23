<?php
/** WorldCup Journey | D | Anonymous guestbook submissions and public listing. */

namespace frontend\controllers;

use app\models\GuestbookMessage;
use app\services\MatchQueryService;
use DateTimeImmutable;
use DateTimeZone;
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
        $matchId = $this->request->get('match_id');
        if ($matchId !== null && !is_string($matchId)) {
            throw new BadRequestHttpException('关联比赛格式无效');
        }
        $model = new GuestbookMessage(['related_match_id' => $matchId]);
        return $this->renderIndex($model);
    }

    public function actionCreate(): string|Response
    {
        $model = new GuestbookMessage(['status' => GuestbookMessage::PENDING]);
        $data = array_intersect_key($this->request->post(), array_flip(['nickname', 'content', 'related_match_id']));
        foreach ($data as $value) {
            if (!is_string($value) && $value !== null) {
                throw new BadRequestHttpException('无效的留言字段');
            }
        }

        if ($model->load($data, '') && $model->save()) {
            Yii::$app->session->setFlash('success', '留言已提交，审核通过后将公开显示');
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

        // Reuse the public 2026 match directory, including its pagination and identity rules.
        $service = new MatchQueryService();
        $first = $service->getMatchList(['page_size' => 100]);
        $matches = $first['items'];
        for ($page = 2; $page <= $first['pagination']['page_count']; ++$page) {
            $matches = array_merge($matches, $service->getMatchList(['page' => $page, 'page_size' => 100])['items']);
        }
        $matchOptions = [];
        $matchOptionAttributes = [];
        foreach ($matches as $match) {
            $time = $match['kickoff_utc'] === null ? null : DateTimeImmutable::createFromFormat(
                '!Y-m-d H:i:s', $match['kickoff_utc'], new DateTimeZone('UTC')
            );
            $time = $time === false || $time === null ? null : $time->setTimezone(new DateTimeZone('Asia/Shanghai'));
            $matchOptions[$match['id']] = ($match['home_team']['name_zh'] ?? '待定球队') . ' — '
                . ($match['away_team']['name_zh'] ?? '待定球队') . ' · ' . ($time?->format('m-d H:i') ?? '时间待定');
            $matchOptionAttributes[$match['id']] = ['data-date' => $time?->format('Y-m-d') ?? ''];
        }
        return $this->render('index', compact('model', 'provider', 'matchOptions', 'matchOptionAttributes'));
    }
}
