<?php
/**
 * WorldCup Journey - 后台精选故事 CRUD 与发布管理
 * 模块：B 赛事与比赛后端；负责人：B
 * 依赖：MatchStory、MatchStorySearch、MatchStoryTag、D 的管理员授权配置
 * 最近修改：2026-11-05
 */
declare(strict_types=1);

namespace app\controllers;

use app\models\MatchStory;
use app\models\MatchStorySearch;
use app\models\MatchStoryTag;
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

class AdminStoryController extends Controller
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
                    throw new ForbiddenHttpException('无权访问精选故事管理。');
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
        $searchModel = new MatchStorySearch();
        $dataProvider = $searchModel->search($this->request->getQueryParams());
        try {
            $pagination = $dataProvider->getPagination();
            $pagination->totalCount = $dataProvider->getTotalCount();
            if ($pagination->getPage() >= max(1, $pagination->getPageCount())) {
                throw new BadRequestHttpException('请求页码超出精选故事列表范围。');
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
            throw new BadRequestHttpException('请先确认删除该精选故事。');
        }
        $model = $this->findModel($this->positiveId($id));
        try {
            $deleted = $this->deleteStory($model);
            if ($deleted === false) {
                Yii::$app->session->setFlash('error', '精选故事未删除，请检查后重试。');
                return $this->redirect(['view', 'id' => $model->id]);
            }
            if ($deleted === 0) {
                throw new NotFoundHttpException('精选故事已不存在。');
            }
            Yii::$app->session->setFlash('success', '精选故事已删除。');
            return $this->redirect(['index']);
        } catch (IntegrityException $exception) {
            Yii::warning(['operation' => 'delete', 'exception' => $exception], self::class);
            Yii::$app->session->setFlash('warning', '该精选故事仍被其他记录引用，不能删除。');
            return $this->redirect(['view', 'id' => $model->id]);
        } catch (DatabaseException $exception) {
            throw $this->databaseFailure($exception);
        }
    }

    private function edit(MatchStory $model, string $view): string|Response
    {
        if ($this->request->isPost) {
            $input = $this->request->post($model->formName());
            if (!is_array($input)) {
                throw new BadRequestHttpException('精选故事表单格式无效。');
            }
            // 只写 V1.1 已明确的业务字段，不接受 id、审计列或其他模块字段。
            $values = array_intersect_key($input,
                array_flip(['match_id', 'title', 'summary', 'body', 'cover_path', 'source_url', 'is_published']));
            foreach ($values as $value) {
                if ($value !== null && (!is_scalar($value) || is_bool($value))) {
                    throw new BadRequestHttpException('精选故事字段格式无效。');
                }
            }
            foreach (['cover_path', 'source_url'] as $optional) {
                if (array_key_exists($optional, $values) && $values[$optional] === '') {
                    $values[$optional] = null;
                }
            }
            if (array_key_exists('is_published', $values)
                && !in_array($values['is_published'], [0, 1, '0', '1'], true)) {
                throw new BadRequestHttpException('发布状态只能为 0 或 1。');
            }
            $model->setAttributes($values);
            if ($model->isPublished() && $this->request->post('confirm_publish') !== '1') {
                $model->addError('is_published', '发布前请确认已核验事实及素材使用许可。');
                return $this->render($view, ['model' => $model]);
            }
            try {
                if ($model->save()) {
                    Yii::$app->session->setFlash('success', '精选故事已保存。');
                    return $this->redirect(['view', 'id' => $model->id]);
                }
            } catch (IntegrityException $exception) {
                Yii::warning(['operation' => $view, 'exception' => $exception], self::class);
                // 一场比赛最多一篇故事；并发冲突不能仅靠 Model 的 unique 预检查。
                $model->addError('match_id', '保存失败，请检查比赛是否已有故事及数据约束。');
            } catch (DatabaseException $exception) {
                throw $this->databaseFailure($exception);
            }
        }
        return $this->render($view, ['model' => $model]);
    }

    protected function newModel(): MatchStory
    {
        $model = new MatchStory();
        $model->is_published = 0;
        return $model;
    }

    protected function findModel(int $id): MatchStory
    {
        try {
            $model = MatchStory::findOne($id);
        } catch (DatabaseException $exception) {
            throw $this->databaseFailure($exception);
        }
        if ($model === null) {
            throw new NotFoundHttpException('精选故事不存在。');
        }
        return $model;
    }

    /**
     * 仅清理本故事的标签关联与故事；不删除比赛、事件或标签字典。
     * 在同一事务内执行，删除被取消/失败时标签关联也必须保留。
     */
    protected function deleteStory(MatchStory $model): int|false
    {
        $transaction = MatchStory::getDb()->beginTransaction();
        try {
            MatchStoryTag::deleteAll(['story_id' => $model->id]);
            $deleted = $model->delete();
            if ($deleted === false || $deleted === 0) {
                $transaction->rollBack();
            } else {
                $transaction->commit();
            }
            return $deleted;
        } catch (\Throwable $exception) {
            if ($transaction->isActive) {
                $transaction->rollBack();
            }
            throw $exception;
        }
    }

    private function positiveId(mixed $value): int
    {
        if ((!is_int($value) && !is_string($value)) || !preg_match('/^[0-9]+$/D', (string) $value)) {
            throw new BadRequestHttpException('精选故事 ID 必须为正整数。');
        }
        $digits = ltrim((string) $value, '0');
        $max = (string) PHP_INT_MAX;
        if ($digits === '' || strlen($digits) > strlen($max)
            || (strlen($digits) === strlen($max) && strcmp($digits, $max) > 0)) {
            throw new BadRequestHttpException('精选故事 ID 超出有效范围。');
        }
        return (int) $digits;
    }

    private function databaseFailure(DatabaseException $exception): ServerErrorHttpException
    {
        Yii::error(['exception' => $exception], self::class);
        return new ServerErrorHttpException('精选故事数据暂时不可用，请稍后重试。', 0, $exception);
    }
}
