<?php
/**
 * WorldCup Journey - 后台场馆信息 CRUD
 * 模块：B 赛事与比赛后端；负责人：B
 * 依赖：Stadium、StadiumSearch、WcMatch、D 的管理员授权配置
 * 最近修改：2026-11-05
 */
declare(strict_types=1);

namespace app\controllers;

use app\models\Stadium;
use app\models\StadiumSearch;
use app\models\WcMatch;
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

class AdminStadiumController extends Controller
{
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
                    throw new ForbiddenHttpException('无权访问场馆管理。');
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
        $searchModel = new StadiumSearch();
        $dataProvider = $searchModel->search($this->request->getQueryParams());
        try {
            $pagination = $dataProvider->getPagination();
            $pagination->totalCount = $dataProvider->getTotalCount();
            if ($pagination->getPage() >= max(1, $pagination->getPageCount())) {
                throw new BadRequestHttpException('请求页码超出场馆列表范围。');
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
            throw new BadRequestHttpException('请先确认删除该场馆。');
        }
        $model = $this->findModel($this->positiveId($id));
        try {
            // B 的比赛依赖先检查；正式 FK 同样必须保护比赛引用，避免并发新增引用后误删。
            // 预检查不是事务锁，后续 delete 的 IntegrityException 同样必须处理。
            if ($this->hasMatches($model)) {
                Yii::$app->session->setFlash('warning', '该场馆已有比赛记录，不能删除。');
                return $this->redirect(['view', 'id' => $model->id]);
            }
            $deleted = $model->delete();
            if ($deleted === false) {
                Yii::$app->session->setFlash('error', '场馆未删除，请检查后重试。');
                return $this->redirect(['view', 'id' => $model->id]);
            }
            if ($deleted === 0) {
                throw new NotFoundHttpException('场馆已不存在。');
            }
            Yii::$app->session->setFlash('success', '场馆已删除。');
            return $this->redirect(['index']);
        } catch (IntegrityException $exception) {
            Yii::warning(['operation' => 'delete', 'exception' => $exception], self::class);
            Yii::$app->session->setFlash('warning', '该场馆仍被其他记录引用，不能删除。');
            return $this->redirect(['view', 'id' => $model->id]);
        } catch (DatabaseException $exception) {
            throw $this->databaseFailure($exception);
        }
    }

    private function edit(Stadium $model, string $view): string|Response
    {
        if ($this->request->isPost) {
            $input = $this->request->post($model->formName());
            if (!is_array($input)) {
                throw new BadRequestHttpException('场馆表单格式无效。');
            }
            // 只写 V1.1 已明确的业务字段，不接受 id、审计列或其他模块字段。
            $values = array_intersect_key($input,
                array_flip(['name', 'city', 'country_code', 'timezone_name']));
            foreach ($values as $value) {
                if ($value !== null && (!is_scalar($value) || is_bool($value))) {
                    throw new BadRequestHttpException('场馆字段格式无效。');
                }
            }
            if (array_key_exists('timezone_name', $values) && $values['timezone_name'] === '') {
                $values['timezone_name'] = null;
            }
            $model->setAttributes($values);
            try {
                if ($model->save()) {
                    Yii::$app->session->setFlash('success', '场馆已保存。');
                    return $this->redirect(['view', 'id' => $model->id]);
                }
            } catch (IntegrityException $exception) {
                Yii::warning(['operation' => $view, 'exception' => $exception], self::class);
                // 不推定场馆名称唯一；完整性错误保留为通用表单提示。
                $model->addError('name', '保存失败，请检查场馆数据及数据库约束。');
            } catch (DatabaseException $exception) {
                throw $this->databaseFailure($exception);
            }
        }
        return $this->render($view, ['model' => $model]);
    }

    protected function newModel(): Stadium
    {
        return new Stadium();
    }

    protected function findModel(int $id): Stadium
    {
        try {
            $model = Stadium::findOne($id);
        } catch (DatabaseException $exception) {
            throw $this->databaseFailure($exception);
        }
        if ($model === null) {
            throw new NotFoundHttpException('场馆不存在。');
        }
        return $model;
    }

    protected function hasMatches(Stadium $model): bool
    {
        return WcMatch::find()->where(['stadium_id' => $model->id])->exists();
    }

    private function positiveId(mixed $value): int
    {
        if ((!is_int($value) && !is_string($value)) || !preg_match('/^[0-9]+$/D', (string) $value)) {
            throw new BadRequestHttpException('场馆 ID 必须为正整数。');
        }
        $digits = ltrim((string) $value, '0');
        $max = (string) PHP_INT_MAX;
        if ($digits === '' || strlen($digits) > strlen($max)
            || (strlen($digits) === strlen($max) && strcmp($digits, $max) > 0)) {
            throw new BadRequestHttpException('场馆 ID 超出有效范围。');
        }
        return (int) $digits;
    }

    private function databaseFailure(DatabaseException $exception): ServerErrorHttpException
    {
        Yii::error(['exception' => $exception], self::class);
        return new ServerErrorHttpException('场馆数据暂时不可用，请稍后重试。', 0, $exception);
    }
}
