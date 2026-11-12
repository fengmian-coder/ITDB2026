<?php
/**
 * WorldCup Journey - 后台球队信息 CRUD
 * 模块：C 球队管理；负责人：C，B 协助实现
 * 依赖：NationalTeam、NationalTeamSearch、WcMatch、D 的管理员授权配置
 * 最近修改：2026-11-11
 */
declare(strict_types=1);

namespace app\controllers;

use app\models\NationalTeam;
use app\models\NationalTeamSearch;
use app\models\WcMatch;
use app\models\TournamentTeam;
use app\models\MatchEvent;
use app\models\AdminUser;
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

class AdminTeamController extends Controller
{
    /** 使用 A 的统一后台布局及 D 的真实管理员身份。 */
    public $layout = 'admin';

    /**
     * 默认调用 D 的 AdminUser::canManage，支持服务器端 DI 调整授权。
     * 只有已登录且回调严格返回 true 的身份可访问。本属性不从请求加载。
     */
    public mixed $adminAccessChecker = [AdminUser::class, 'canManage'];

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
                    throw new ForbiddenHttpException('无权访问球队管理');
                },
            ],
            'verbs' => [
                'class' => VerbFilter::class,
                'actions' => [
                    'index' => ['GET', 'HEAD'], 'view' => ['GET', 'HEAD'],
                    'create' => ['GET', 'HEAD', 'POST'], 'update' => ['GET', 'HEAD', 'POST'],
                    'delete' => ['POST'], 'participation' => ['POST'],
                    'profile' => ['GET', 'HEAD', 'POST'],
                ],
            ],
        ];
    }

    public function actionIndex(): string
    {
        $searchModel = new NationalTeamSearch();
        $dataProvider = $searchModel->search($this->request->getQueryParams());
        try {
            $pagination = $dataProvider->getPagination();
            $pagination->totalCount = $dataProvider->getTotalCount();
            if ($pagination->getPage() >= max(1, $pagination->getPageCount())) {
                throw new BadRequestHttpException('请求页码超出球队列表范围');
            }
            return $this->render('index', compact('searchModel', 'dataProvider'));
        } catch (DatabaseException $exception) {
            throw $this->databaseFailure($exception);
        }
    }

    public function actionView(mixed $id): string
    {
        return $this->render('view', ['model' => $this->findModel($this->positiveId($id)),
            'tournaments' => \app\models\Tournament::find()->orderBy(['year' => SORT_DESC])->all()]);
    }

    public function actionCreate(): string|Response
    {
        return $this->edit($this->newModel(), 'create');
    }

    /** 通过球队详情维护参赛关系，主键由服务器指定。 */
    public function actionParticipation(mixed $id): Response
    {
        $team = $this->findModel($this->positiveId($id));
        $input = $this->request->post('TournamentTeam');
        if (!is_array($input)) { throw new BadRequestHttpException('参赛表单格式无效'); }
        $tournamentId = $this->positiveId($input['tournament_id'] ?? null);
        $values = array_intersect_key($input, array_flip(['group_code', 'final_result_code']));
        foreach ($values as $value) {
            if ($value !== null && !is_string($value)) { throw new BadRequestHttpException('参赛字段格式无效'); }
        }
        if (($values['final_result_code'] ?? null) === '') { $values['final_result_code'] = null; }
        $model = TournamentTeam::findOne(['tournament_id' => $tournamentId, 'team_id' => $team->id])
            ?? new TournamentTeam(['tournament_id' => $tournamentId, 'team_id' => $team->id]);
        $model->setAttributes($values);
        try {
            if ($model->save()) {
                Yii::$app->session->setFlash('success', '参赛信息已保存');
            } else {
                Yii::$app->session->setFlash('error', implode('；', $model->getFirstErrors()));
            }
        } catch (IntegrityException $exception) {
            Yii::warning($exception, self::class);
            Yii::$app->session->setFlash('error', '参赛信息保存失败，请核对赛事和参赛关系');
        }
        return $this->redirect(['view', 'id' => $team->id]);
    }

    public function actionUpdate(mixed $id): string|Response
    {
        return $this->edit($this->findModel($this->positiveId($id)), 'update');
    }

    /** 本届介绍与人物资料独立维护，沿用管理员授权、POST 及 CSRF。 */
    public function actionProfile(mixed $id): string|Response
    {
        $team = $this->findModel($this->positiveId($id));
        $participates = TournamentTeam::find()->alias('tt')->innerJoin(['t' => 'tournament'], '[[t.id]] = [[tt.tournament_id]]')
            ->where(['tt.team_id' => $team->id, 't.year' => 2026])->exists();
        if (!$participates) throw new NotFoundHttpException('请先登记该球队的 2026 届参赛信息');
        $service = new \app\services\TeamProfileService();
        $form = new \app\models\TeamProfileForm();
        $profile = $service->get($team);
        if ($profile !== null) $form->setAttributes($profile);
        if ($this->request->isPost) {
            if (!is_array($this->request->post($form->formName()))) throw new BadRequestHttpException('球队档案表单格式无效');
            $form->load($this->request->post());
            if ($form->validate()) {
                try {
                    $service->save((int) $team->id, $form);
                    Yii::$app->session->setFlash('success', '2026 球队档案已保存');
                    return $this->redirect(['view', 'id' => $team->id]);
                } catch (\RuntimeException $error) { $form->addError('summary', $error->getMessage()); }
            }
        }
        return $this->render('profile', ['team' => $team, 'form' => $form, 'schemaReady' => $service->hasColumn()]);
    }

    public function actionDelete(mixed $id): Response
    {
        if ($this->request->post('confirm_delete') !== '1') {
            throw new BadRequestHttpException('请先确认删除该球队');
        }
        $model = $this->findModel($this->positiveId($id));
        try {
            // 先检查参赛、比赛和事件引用，正式 FK 继续保护并发新增的引用。
            // 预检查不是事务锁，后续 delete 的 IntegrityException 同样必须处理。
            if ($this->hasReferences($model)) {
                Yii::$app->session->setFlash('warning', '该球队已有参赛、比赛或事件引用，可通过修改页面停用');
                return $this->redirect(['view', 'id' => $model->id]);
            }
            $deleted = $model->delete();
            if ($deleted === false) {
                Yii::$app->session->setFlash('error', '球队未删除，请检查后重试');
                return $this->redirect(['view', 'id' => $model->id]);
            }
            if ($deleted === 0) {
                throw new NotFoundHttpException('球队已不存在');
            }
            Yii::$app->session->setFlash('success', '球队已删除');
            return $this->redirect(['index']);
        } catch (IntegrityException $exception) {
            Yii::warning(['operation' => 'delete', 'exception' => $exception], self::class);
            Yii::$app->session->setFlash('warning', '该球队仍被其他记录引用，不能删除');
            return $this->redirect(['view', 'id' => $model->id]);
        } catch (DatabaseException $exception) {
            throw $this->databaseFailure($exception);
        }
    }

    private function edit(NationalTeam $model, string $view): string|Response
    {
        if ($this->request->isPost) {
            $input = $this->request->post($model->formName());
            if (!is_array($input)) {
                throw new BadRequestHttpException('球队表单格式无效');
            }
            // 只写 V1.1 已明确的业务字段，不接受 id、审计列或其他模块字段。
            $values = array_intersect_key($input,
                array_flip(['fifa_code', 'name_zh', 'name_en', 'flag_path', 'description', 'is_active']));
            foreach ($values as $value) {
                if ($value !== null && (!is_scalar($value) || is_bool($value))) {
                    throw new BadRequestHttpException('球队字段格式无效');
                }
            }
            if (array_key_exists('is_active', $values) && !in_array($values['is_active'], ['0', '1', 0, 1], true)) {
                throw new BadRequestHttpException('启用状态须为 0 或 1');
            }
            foreach (['flag_path', 'description'] as $attribute) {
                if (($values[$attribute] ?? null) === '') { $values[$attribute] = null; }
            }
            $model->setAttributes($values);
            try {
                if ($model->save()) {
                    Yii::$app->session->setFlash('success', '球队已保存');
                    return $this->redirect(['view', 'id' => $model->id]);
                }
            } catch (IntegrityException $exception) {
                Yii::warning(['operation' => $view, 'exception' => $exception], self::class);
                // 不推定球队名称唯一；完整性错误保留为通用表单提示。
                $model->addError('fifa_code', '保存失败，请检查球队数据及数据库约束');
            } catch (DatabaseException $exception) {
                throw $this->databaseFailure($exception);
            }
        }
        return $this->render($view, ['model' => $model]);
    }

    protected function newModel(): NationalTeam
    {
        return new NationalTeam();
    }

    protected function findModel(int $id): NationalTeam
    {
        try {
            $model = NationalTeam::find()->with('tournamentTeams')->where(['id' => $id])->one();
        } catch (DatabaseException $exception) {
            throw $this->databaseFailure($exception);
        }
        if ($model === null) {
            throw new NotFoundHttpException('球队不存在');
        }
        return $model;
    }

    protected function hasReferences(NationalTeam $model): bool
    {
        return TournamentTeam::find()->where(['team_id' => $model->id])->exists()
            || WcMatch::find()->where(['or', ['home_team_id' => $model->id], ['away_team_id' => $model->id], ['winner_team_id' => $model->id]])->exists()
            || MatchEvent::find()->where(['team_id' => $model->id])->exists();
    }

    private function positiveId(mixed $value): int
    {
        if ((!is_int($value) && !is_string($value)) || !preg_match('/^[0-9]+$/D', (string) $value)) {
            throw new BadRequestHttpException('球队 ID 必须为正整数');
        }
        $digits = ltrim((string) $value, '0');
        $max = (string) PHP_INT_MAX;
        if ($digits === '' || strlen($digits) > strlen($max)
            || (strlen($digits) === strlen($max) && strcmp($digits, $max) > 0)) {
            throw new BadRequestHttpException('球队 ID 超出有效范围');
        }
        return (int) $digits;
    }

    private function databaseFailure(DatabaseException $exception): ServerErrorHttpException
    {
        Yii::error(['exception' => $exception], self::class);
        return new ServerErrorHttpException('球队数据暂时不可用，请稍后重试', 0, $exception);
    }
}
