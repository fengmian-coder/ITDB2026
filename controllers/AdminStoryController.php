<?php
/**
 * WorldCup Journey - 后台精选故事 CRUD 与发布管理
 * 模块：B 赛事与比赛后端；负责人：B
 * 依赖：MatchStory、MatchStorySearch、MatchStoryTag、MatchEvent、MatchTag、D 的管理员授权配置
 * 最近修改：2026-11-10（A 接入统一后台布局）
 */
declare(strict_types=1);

namespace app\controllers;

use app\models\MatchStory;
use app\models\MatchStorySearch;
use app\models\MatchStoryTag;
use app\models\MatchEvent;
use app\models\MatchTag;
use yii\data\ActiveDataProvider;
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
                    throw new ForbiddenHttpException('无权访问精选故事管理');
                },
            ],
            'verbs' => [
                'class' => VerbFilter::class,
                'actions' => [
                    'index' => ['GET', 'HEAD'], 'view' => ['GET', 'HEAD'],
                    'create' => ['GET', 'HEAD', 'POST'], 'update' => ['GET', 'HEAD', 'POST'],
                    'delete' => ['POST'],
                    'events' => ['GET', 'HEAD'],
                    'event-create' => ['GET', 'HEAD', 'POST'],
                    'event-update' => ['GET', 'HEAD', 'POST'],
                    'event-delete' => ['POST'],
                    'tags' => ['GET', 'HEAD'],
                    'tag-create' => ['GET', 'HEAD', 'POST'],
                    'tag-update' => ['GET', 'HEAD', 'POST'],
                    'tag-delete' => ['POST'],
                    'tag-add' => ['POST'], 'tag-remove' => ['POST'],
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
                throw new BadRequestHttpException('请求页码超出精选故事列表范围');
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
            throw new BadRequestHttpException('请先确认删除该精选故事');
        }
        $model = $this->findModel($this->positiveId($id));
        try {
            $deleted = $this->deleteStory($model);
            if ($deleted === false) {
                Yii::$app->session->setFlash('error', '精选故事未删除，请检查后重试');
                return $this->redirect(['view', 'id' => $model->id]);
            }
            if ($deleted === 0) {
                throw new NotFoundHttpException('精选故事已不存在');
            }
            Yii::$app->session->setFlash('success', '精选故事已删除');
            return $this->redirect(['index']);
        } catch (IntegrityException $exception) {
            Yii::warning(['operation' => 'delete', 'exception' => $exception], self::class);
            Yii::$app->session->setFlash('warning', '该精选故事仍被其他记录引用，不能删除');
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
                throw new BadRequestHttpException('精选故事表单格式无效');
            }
            // 只写 V1.1 已明确的业务字段，不接受 id、审计列或其他模块字段。
            $values = array_intersect_key($input,
                array_flip(['match_id', 'title', 'summary', 'body', 'cover_path', 'source_url', 'is_published']));
            foreach ($values as $value) {
                if ($value !== null && (!is_scalar($value) || is_bool($value))) {
                    throw new BadRequestHttpException('精选故事字段格式无效');
                }
            }
            foreach (['cover_path', 'source_url'] as $optional) {
                if (array_key_exists($optional, $values) && $values[$optional] === '') {
                    $values[$optional] = null;
                }
            }
            if (array_key_exists('is_published', $values)
                && !in_array($values['is_published'], [0, 1, '0', '1'], true)) {
                throw new BadRequestHttpException('发布状态只能为 0 或 1');
            }
            $model->setAttributes($values);
            if ($model->isPublished() && $this->request->post('confirm_publish') !== '1') {
                $model->addError('is_published', '发布前请确认已核验事实及素材使用许可');
                return $this->render($view, ['model' => $model]);
            }
            try {
                if ($model->save()) {
                    Yii::$app->session->setFlash('success', '精选故事已保存');
                    return $this->redirect(['view', 'id' => $model->id]);
                }
            } catch (IntegrityException $exception) {
                Yii::warning(['operation' => $view, 'exception' => $exception], self::class);
                // 一场比赛最多一篇故事；并发冲突不能仅靠 Model 的 unique 预检查。
                $model->addError('match_id', '保存失败，请检查比赛是否已有故事及数据约束');
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

    /** 事件属于比赛，故事仅作为精选内容管理入口；排序与前台 Service 一致。 */
    public function actionEvents(mixed $id): string
    {
        $story = $this->findModel($this->positiveId($id));
        $page = $this->positiveId($this->request->get('page', '1'));
        $size = $this->positiveId($this->request->get('per-page', '20'));
        if ($size > 100 || $page - 1 > intdiv(PHP_INT_MAX, $size)) {
            throw new BadRequestHttpException('事件分页参数超出范围');
        }
        $dataProvider = new ActiveDataProvider([
            'query' => MatchEvent::find()->where(['match_id' => $story->match_id])
                ->orderBy(['sort_order' => SORT_ASC, 'id' => SORT_ASC]),
            'sort' => false,
            'pagination' => ['page' => $page - 1, 'pageSize' => $size, 'pageSizeLimit' => [1, 100],
                'validatePage' => false, 'params' => ['id' => $story->id, 'page' => $page, 'per-page' => $size]],
        ]);
        try {
            $pagination = $dataProvider->getPagination();
            $pagination->totalCount = $dataProvider->getTotalCount();
            if ($pagination->getPage() >= max(1, $pagination->getPageCount())) {
                throw new BadRequestHttpException('请求页码超出事件列表范围');
            }
            return $this->render('events', compact('story', 'dataProvider'));
        } catch (DatabaseException $exception) {
            throw $this->databaseFailure($exception);
        }
    }

    public function actionEventCreate(mixed $id): string|Response
    {
        $story = $this->findModel($this->positiveId($id));
        $event = $this->newEvent();
        $event->match_id = $story->match_id;
        return $this->editEvent($story, $event);
    }

    public function actionEventUpdate(mixed $id, mixed $event_id): string|Response
    {
        $story = $this->findModel($this->positiveId($id));
        return $this->editEvent($story, $this->findEvent($story, $this->positiveId($event_id)));
    }

    public function actionEventDelete(mixed $id, mixed $event_id): Response
    {
        $story = $this->findModel($this->positiveId($id));
        $event = $this->findEvent($story, $this->positiveId($event_id));
        if ($this->request->post('confirm_delete') !== '1'
            || ($story->isPublished() && $this->request->post('confirm_publish') !== '1')) {
            throw new BadRequestHttpException('请确认删除；已发布故事还须确认对前台时间轴的影响');
        }
        try {
            $deleted = $event->delete();
            if ($deleted === 0) {
                throw new NotFoundHttpException('比赛事件已不存在');
            }
            Yii::$app->session->setFlash($deleted === false ? 'error' : 'success',
                $deleted === false ? '事件未删除，请检查后重试' : '比赛事件已删除');
            return $this->redirect(['events', 'id' => $story->id]);
        } catch (IntegrityException $exception) {
            Yii::warning(['operation' => 'event-delete', 'exception' => $exception], self::class);
            Yii::$app->session->setFlash('warning', '事件仍被引用，不能删除');
            return $this->redirect(['events', 'id' => $story->id]);
        } catch (DatabaseException $exception) {
            throw $this->databaseFailure($exception);
        }
    }

    private function editEvent(MatchStory $story, MatchEvent $event): string|Response
    {
        if ($this->request->isPost) {
            $input = $this->request->post($event->formName());
            if (!is_array($input)) {
                throw new BadRequestHttpException('事件表单格式无效');
            }
            $values = array_intersect_key($input, array_flip(['team_id', 'player_name', 'minute',
                'added_minute', 'period_code', 'event_type', 'description', 'sort_order']));
            foreach ($values as $key => $value) {
                if ($value !== null && (!is_scalar($value) || is_bool($value))) {
                    throw new BadRequestHttpException('事件字段格式无效');
                }
                if ($value === '' && in_array($key,
                    ['team_id', 'player_name', 'minute', 'added_minute', 'description'], true)) {
                    $values[$key] = null;
                }
            }
            $event->setAttributes($values);
            // 请求不能篡改事件主键或比赛归属；保持现有 Model 的字段和待确认规则。
            $event->match_id = $story->match_id;
            if ($story->isPublished() && $this->request->post('confirm_publish') !== '1') {
                $event->addError('description', '此故事已发布，请确认事件事实与前台展示影响');
                return $this->render('event-form', compact('story', 'event'));
            }
            try {
                if ($event->save()) {
                    Yii::$app->session->setFlash('success', '比赛事件已保存');
                    return $this->redirect(['events', 'id' => $story->id]);
                }
            } catch (IntegrityException $exception) {
                Yii::warning(['operation' => 'event-save', 'exception' => $exception], self::class);
                $event->addError('team_id', '保存失败，请检查比赛、球队及正式数据库约束');
            } catch (DatabaseException $exception) {
                throw $this->databaseFailure($exception);
            }
        }
        return $this->render('event-form', compact('story', 'event'));
    }

    protected function newEvent(): MatchEvent
    {
        return new MatchEvent();
    }

    /** 统一标签字典与本故事的关联分开维护；不将标签名称复制进关联表。 */
    public function actionTags(mixed $id): string
    {
        $story = $this->findModel($this->positiveId($id));
        $page = $this->positiveId($this->request->get('page', '1'));
        if ($page - 1 > intdiv(PHP_INT_MAX, 20)) {
            throw new BadRequestHttpException('标签分页参数超出范围');
        }
        $dataProvider = new ActiveDataProvider([
            'query' => MatchTag::find()->orderBy(['code' => SORT_ASC, 'id' => SORT_ASC]),
            'sort' => false,
            'pagination' => ['page' => $page - 1, 'pageSize' => 20, 'validatePage' => false,
                'params' => ['id' => $story->id, 'page' => $page]],
        ]);
        try {
            $pagination = $dataProvider->getPagination();
            $pagination->totalCount = $dataProvider->getTotalCount();
            if ($pagination->getPage() >= max(1, $pagination->getPageCount())) {
                throw new BadRequestHttpException('请求页码超出标签列表范围');
            }
            $selectedIds = $story->getMatchStoryTags()->select('tag_id')->column();
            return $this->render('tags', compact('story', 'dataProvider', 'selectedIds'));
        } catch (DatabaseException $exception) {
            throw $this->databaseFailure($exception);
        }
    }

    public function actionTagCreate(mixed $id): string|Response
    {
        return $this->editTag($this->findModel($this->positiveId($id)), $this->newTag());
    }

    public function actionTagUpdate(mixed $id, mixed $tag_id): string|Response
    {
        return $this->editTag($this->findModel($this->positiveId($id)), $this->findTag($this->positiveId($tag_id)));
    }

    public function actionTagDelete(mixed $id, mixed $tag_id): Response
    {
        $story = $this->findModel($this->positiveId($id));
        $tag = $this->findTag($this->positiveId($tag_id));
        if ($this->request->post('confirm_delete') !== '1' || $this->request->post('confirm_shared') !== '1') {
            throw new BadRequestHttpException('请确认删除共享标签字典项');
        }
        try {
            if ($this->tagIsUsed($tag)) {
                Yii::$app->session->setFlash('warning', '标签仍被故事使用，请先解除关联；不会自动清理其他故事');
                return $this->redirect(['tags', 'id' => $story->id]);
            }
            $deleted = $tag->delete();
            if ($deleted === 0) {
                throw new NotFoundHttpException('标签已不存在');
            }
            Yii::$app->session->setFlash($deleted === false ? 'error' : 'success',
                $deleted === false ? '标签未删除，请检查后重试' : '标签已删除');
        } catch (IntegrityException $exception) {
            Yii::warning(['operation' => 'tag-delete', 'exception' => $exception], self::class);
            Yii::$app->session->setFlash('warning', '标签存在关联，不能删除');
        } catch (DatabaseException $exception) {
            throw $this->databaseFailure($exception);
        }
        return $this->redirect(['tags', 'id' => $story->id]);
    }

    public function actionTagAdd(mixed $id, mixed $tag_id): Response
    {
        return $this->changeTag($id, $tag_id, true);
    }

    public function actionTagRemove(mixed $id, mixed $tag_id): Response
    {
        return $this->changeTag($id, $tag_id, false);
    }

    private function changeTag(mixed $id, mixed $tagId, bool $add): Response
    {
        $story = $this->findModel($this->positiveId($id));
        $tag = $this->findTag($this->positiveId($tagId));
        if ($story->isPublished() && $this->request->post('confirm_publish') !== '1') {
            throw new BadRequestHttpException('请确认对已发布故事标签的修改');
        }
        try {
            // 服务端生成两个外键，不从表单加载 story_id/tag_id 或额外字段。
            $this->writeTagLink($story, $tag, $add);
            Yii::$app->session->setFlash('success', $add ? '故事标签已关联' : '故事标签已解除关联');
        } catch (IntegrityException $exception) {
            Yii::warning(['operation' => 'story-tag', 'exception' => $exception], self::class);
            Yii::$app->session->setFlash('warning', '关联未保存，请检查标签、故事及并发修改');
        } catch (DatabaseException $exception) {
            throw $this->databaseFailure($exception);
        }
        return $this->redirect(['tags', 'id' => $story->id]);
    }

    protected function writeTagLink(MatchStory $story, MatchTag $tag, bool $add): void
    {
        $condition = ['story_id' => $story->id, 'tag_id' => $tag->id];
        if (!$add) {
            MatchStoryTag::deleteAll($condition);
            return;
        }
        // 重复添加为幂等操作；正式联合主键仍负责并发唯一性保护。
        if (!MatchStoryTag::find()->where($condition)->exists()) {
            $link = new MatchStoryTag();
            $link->setAttributes($condition);
            if (!$link->save()) {
                throw new DatabaseException('故事标签关联验证或保存失败');
            }
        }
    }

    private function editTag(MatchStory $story, MatchTag $tag): string|Response
    {
        if ($this->request->isPost) {
            $input = $this->request->post($tag->formName());
            if (!is_array($input)) {
                throw new BadRequestHttpException('标签表单格式无效');
            }
            $values = array_intersect_key($input, array_flip(['code', 'name']));
            foreach ($values as $value) {
                if ($value !== null && !is_string($value)) {
                    throw new BadRequestHttpException('标签字段必须为字符串');
                }
            }
            $tag->setAttributes($values);
            if (!$tag->isNewRecord && $this->request->post('confirm_shared') !== '1') {
                $tag->addError('name', '标签由所有故事共享，请确认全站展示影响');
                return $this->render('tag-form', compact('story', 'tag'));
            }
            try {
                if ($tag->save()) {
                    Yii::$app->session->setFlash('success', '标签字典已保存；新标签需另行关联到故事');
                    return $this->redirect(['tags', 'id' => $story->id]);
                }
            } catch (IntegrityException $exception) {
                Yii::warning(['operation' => 'tag-save', 'exception' => $exception], self::class);
                $tag->addError('code', '标签代码重复或不满足正式数据库约束');
            } catch (DatabaseException $exception) {
                throw $this->databaseFailure($exception);
            }
        }
        return $this->render('tag-form', compact('story', 'tag'));
    }

    protected function newTag(): MatchTag
    {
        return new MatchTag();
    }

    protected function findTag(int $tagId): MatchTag
    {
        try {
            $tag = MatchTag::findOne($tagId);
        } catch (DatabaseException $exception) {
            throw $this->databaseFailure($exception);
        }
        if ($tag === null) {
            throw new NotFoundHttpException('标签不存在');
        }
        return $tag;
    }

    protected function tagIsUsed(MatchTag $tag): bool
    {
        return $tag->getMatchStoryTags()->exists();
    }

    protected function findEvent(MatchStory $story, int $eventId): MatchEvent
    {
        try {
            // 两个条件同时查询，避免通过其他故事入口修改不属于该比赛的事件。
            $event = MatchEvent::find()->where(['id' => $eventId, 'match_id' => $story->match_id])->one();
        } catch (DatabaseException $exception) {
            throw $this->databaseFailure($exception);
        }
        if ($event === null) {
            throw new NotFoundHttpException('该比赛下不存在此事件');
        }
        return $event;
    }

    protected function findModel(int $id): MatchStory
    {
        try {
            $model = MatchStory::findOne($id);
        } catch (DatabaseException $exception) {
            throw $this->databaseFailure($exception);
        }
        if ($model === null) {
            throw new NotFoundHttpException('精选故事不存在');
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
            throw new BadRequestHttpException('精选故事 ID 必须为正整数');
        }
        $digits = ltrim((string) $value, '0');
        $max = (string) PHP_INT_MAX;
        if ($digits === '' || strlen($digits) > strlen($max)
            || (strlen($digits) === strlen($max) && strcmp($digits, $max) > 0)) {
            throw new BadRequestHttpException('精选故事 ID 超出有效范围');
        }
        return (int) $digits;
    }

    private function databaseFailure(DatabaseException $exception): ServerErrorHttpException
    {
        Yii::error(['exception' => $exception], self::class);
        return new ServerErrorHttpException('精选故事数据暂时不可用，请稍后重试', 0, $exception);
    }
}
