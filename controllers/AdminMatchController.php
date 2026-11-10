<?php
/**
 * WorldCup Journey - 后台比赛赛程与赛果 CRUD
 * 模块：B 赛事与比赛后端；负责人：B
 * 依赖：WcMatch、WcMatchSearch、MatchStory、MatchEvent、D 的管理员授权配置
 * 最近修改：2026-11-10（A 接入统一后台布局）
 */
declare(strict_types=1);

namespace app\controllers;

use app\models\WcMatch;
use app\models\WcMatchSearch;
use app\models\MatchStory;
use app\models\MatchEvent;
use Yii;
use yii\db\Exception as DatabaseException;
use yii\db\IntegrityException;
use yii\filters\AccessControl;
use yii\filters\VerbFilter;
use yii\web\BadRequestHttpException;
use yii\web\Controller;
use yii\web\ForbiddenHttpException;
use yii\web\NotFoundHttpException;
use yii\web\Response;
use yii\web\ServerErrorHttpException;

class AdminMatchController extends Controller
{
    /** A：接入统一后台布局；业务校验及权限保持由 B/D 维护。 */
    public $layout = 'admin';

    /**
     * D 后续通过服务器端 controllerMap 配置授权回调 callable(IdentityInterface): bool。
     * 未配置时一律拒绝，包括 Yii 示例 admin/demo；不猜测 D 的类名或权限方法。
     * 只有已登录且回调严格返回 true 的身份可访问。本属性不从请求加载。
     */
    public mixed $adminAccessChecker = null;

    public function behaviors(): array
    {
        return [
            'access' => [
                'class' => AccessControl::class,
                'rules' => [[
                    'allow' => true, 'roles' => ['@'],
                    'matchCallback' => fn () => is_callable($this->adminAccessChecker)
                        && ($this->adminAccessChecker)(Yii::$app->user->identity) === true,
                ]],
                'denyCallback' => static function (): void {
                    throw new ForbiddenHttpException('无权访问比赛管理');
                },
            ],
            'verbs' => [
                'class' => VerbFilter::class,
                'actions' => [
                    'index' => ['GET', 'HEAD'], 'view' => ['GET', 'HEAD'],
                    'create' => ['GET', 'HEAD', 'POST'], 'update' => ['GET', 'HEAD', 'POST'],
                    'delete' => ['POST'],
                ],
            ],
        ];
    }

    public function actionIndex(): string
    {
        $searchModel = new WcMatchSearch();
        $dataProvider = $searchModel->search($this->request->getQueryParams());
        try {
            $pagination = $dataProvider->getPagination();
            $pagination->totalCount = $dataProvider->getTotalCount();
            if ($pagination->getPage() >= max(1, $pagination->getPageCount())) {
                throw new BadRequestHttpException('请求页码超出比赛列表范围');
            }
            return $this->render('index', compact('searchModel', 'dataProvider'));
        } catch (DatabaseException $exception) {
            throw $this->databaseFailure($exception);
        }
    }

    public function actionView(mixed $id): string
    {
        return $this->render('view', ['model' => $this->findModel($this->positiveId($id))]);
    }

    public function actionCreate(): string|Response
    {
        return $this->edit($this->newModel(), 'create');
    }

    public function actionUpdate(mixed $id): string|Response
    {
        return $this->edit($this->findModel($this->positiveId($id)), 'update');
    }

    public function actionDelete(mixed $id): Response
    {
        if ($this->request->post('confirm_delete') !== '1') {
            throw new BadRequestHttpException('请先确认删除该比赛');
        }
        $model = $this->findModel($this->positiveId($id));
        try {
            // 先保护 B 的故事和事件；其他模块引用由正式 FK 保护，不猜测其 Model。
            // 预检查不是事务锁，后续 delete 的 IntegrityException 同样必须处理。
            if ($this->hasDependents($model)) {
                Yii::$app->session->setFlash('warning', '该比赛已有故事或事件，不能直接删除');
                return $this->redirect(['view', 'id' => $model->id]);
            }
            $deleted = $model->delete();
            if ($deleted === false) {
                Yii::$app->session->setFlash('error', '比赛未删除，请检查后重试');
                return $this->redirect(['view', 'id' => $model->id]);
            }
            if ($deleted === 0) {
                throw new NotFoundHttpException('比赛已不存在');
            }
            Yii::$app->session->setFlash('success', '比赛已删除');
            return $this->redirect(['index']);
        } catch (IntegrityException $exception) {
            Yii::warning(['operation' => 'delete', 'exception' => $exception], self::class);
            Yii::$app->session->setFlash('warning', '该比赛仍被其他记录引用，不能删除');
            return $this->redirect(['view', 'id' => $model->id]);
        } catch (DatabaseException $exception) {
            throw $this->databaseFailure($exception);
        }
    }

    private function edit(WcMatch $model, string $view): string|Response
    {
        if ($this->request->isPost) {
            $input = $this->request->post($model->formName());
            if (!is_array($input)) {
                throw new BadRequestHttpException('比赛表单格式无效');
            }
            // 只写 V1.1 已明确的业务字段，不接受 id、审计列或其他模块字段。
            $values = array_intersect_key($input,
                array_flip(['tournament_id', 'match_no', 'home_team_id', 'away_team_id', 'stadium_id',
                    'kickoff_utc', 'stage_code', 'group_code', 'status', 'home_score_90', 'away_score_90',
                    'home_score_120', 'away_score_120', 'home_penalties', 'away_penalties', 'winner_team_id', 'summary']));
            foreach ($values as $value) {
                if ($value !== null && (!is_scalar($value) || is_bool($value))) {
                    throw new BadRequestHttpException('比赛字段格式无效');
                }
            }
            foreach (['group_code', 'home_score_90', 'away_score_90', 'home_score_120', 'away_score_120',
                'home_penalties', 'away_penalties', 'winner_team_id', 'summary'] as $optional) {
                if (array_key_exists($optional, $values) && $values[$optional] === '') {
                    $values[$optional] = null;
                }
            }
            $model->setAttributes($values);
            try {
                if ($model->save()) {
                    Yii::$app->session->setFlash('success', '比赛已保存');
                    return $this->redirect(['view', 'id' => $model->id]);
                }
            } catch (IntegrityException $exception) {
                Yii::warning(['operation' => $view, 'exception' => $exception], self::class);
                // 唯一编号、B/C 外键和 CHECK 均可能失败，不将所有冲突当成编号重复。
                $model->addError('match_no', '保存失败，请检查赛事内编号是否重复、关联 ID 及比分约束');
            } catch (DatabaseException $exception) {
                throw $this->databaseFailure($exception);
            }
        }
        return $this->render($view, ['model' => $model]);
    }

    protected function newModel(): WcMatch
    {
        $model = new WcMatch();
        $model->status = 'SCHEDULED';
        return $model;
    }

    protected function findModel(int $id): WcMatch
    {
        try {
            $model = WcMatch::findOne($id);
        } catch (DatabaseException $exception) {
            throw $this->databaseFailure($exception);
        }
        if ($model === null) {
            throw new NotFoundHttpException('比赛不存在');
        }
        return $model;
    }

    protected function hasDependents(WcMatch $model): bool
    {
        return MatchStory::find()->where(['match_id' => $model->id])->exists()
            || MatchEvent::find()->where(['match_id' => $model->id])->exists();
    }

    private function positiveId(mixed $value): int
    {
        if ((!is_int($value) && !is_string($value)) || !preg_match('/^[0-9]+$/D', (string) $value)) {
            throw new BadRequestHttpException('比赛 ID 必须为正整数');
        }
        $digits = ltrim((string) $value, '0');
        $max = (string) PHP_INT_MAX;
        if ($digits === '' || strlen($digits) > strlen($max)
            || (strlen($digits) === strlen($max) && strcmp($digits, $max) > 0)) {
            throw new BadRequestHttpException('比赛 ID 超出有效范围');
        }
        return (int) $digits;
    }

    private function databaseFailure(DatabaseException $exception): ServerErrorHttpException
    {
        Yii::error(['exception' => $exception], self::class);
        return new ServerErrorHttpException('比赛数据暂时不可用，请稍后重试', 0, $exception);
    }
}
