<?php
/**
 * WorldCup Journey - 首页、球队探索入口及保留的模板路由。
 * 模块：A 首页 / D 登录兼容；负责人：A、D。
 * 依赖：TeamDirectoryService、B 的 MatchQueryService。
 * 最近修改：2026-11-10。
 */

declare(strict_types=1);

namespace app\controllers;

use Yii;
use app\models\ContactForm;
use app\services\TeamDirectoryService;
use app\services\MatchQueryService;
use yii\web\BadRequestHttpException;
use yii\captcha\CaptchaAction;
use yii\filters\AccessControl;
use yii\filters\VerbFilter;
use yii\mail\MailerInterface;
use yii\web\Controller;
use yii\web\ErrorAction;
use yii\web\Response;

class SiteController extends Controller
{
    public function __construct(
        $id,
        $module,
        private readonly MailerInterface $mailer,
        $config = [],
    ) {
        parent::__construct($id, $module, $config);
    }

    /**
     * {@inheritdoc}
     */
    public function behaviors(): array
    {
        return [
            'access' => [
                'class' => AccessControl::class,
                'only' => ['logout'],
                'rules' => [
                    [
                        'actions' => ['logout'],
                        'allow' => true,
                        'roles' => ['@'],
                    ],
                ],
            ],
            'verbs' => [
                'class' => VerbFilter::class,
                'actions' => [
                    'logout' => ['post'],
                    'index' => ['GET', 'HEAD'], 'explore' => ['GET', 'HEAD'],
                ],
            ],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function actions(): array
    {
        return [
            'error' => [
                'class' => ErrorAction::class,
            ],
            'captcha' => [
                'class' => CaptchaAction::class,
                'fixedVerifyCode' => YII_ENV_TEST ? 'testme' : null,
                'transparent' => true,
            ],
        ];
    }

    /**
     * Displays homepage.
     *
     * @return string
     */
    public function actionIndex(): string
    {
        $teams = (new TeamDirectoryService())->getTeams();
        $matches = (new MatchQueryService())->getMatchList(['page_size' => 1]);
        return $this->render('index', ['teamCount' => count($teams),
            'matchCount' => $matches['pagination']['total_count']]);
    }

    /** C 的球队页未交付前提供只读入口；不实现 DNA、PK 或重复统计。 */
    public function actionExplore(mixed $q = ''): string|Response
    {
        if (!is_string($q) || mb_strlen($q) > 100) {
            throw new BadRequestHttpException('搜索词格式无效，请输入不超过 100 字的名称');
        }
        if (class_exists(__NAMESPACE__ . '\\NationalTeamController')) {
            return $this->redirect(['/national-team/index', 'q' => trim($q)]);
        }
        $q = trim($q);
        $teams = array_values(array_filter((new TeamDirectoryService())->getTeams(),
            static fn (array $team): bool => $q === '' || mb_stripos(implode(' ', [$team['name_zh'], $team['name_en'], $team['fifa_code']]), $q) !== false));
        return $this->render('explore', compact('teams', 'q'));
    }

    /**
     * Login action.
     *
     * @return Response|string
     */
    public function actionLogin(): Response
    {
        return $this->redirect(['/auth/login']);
    }

    /**
     * Logout action.
     *
     * @return Response
     */
    public function actionLogout(): Response
    {
        Yii::$app->user->logout();

        return $this->goHome();
    }

    /**
     * Displays contact page.
     *
     * @return Response|string
     */
    public function actionContact(): Response|string
    {
        $model = new ContactForm();

        $contact = $model->load($this->request->post()) && $model->contact(
            $this->mailer,
            Yii::$app->params['adminEmail'],
            Yii::$app->params['senderEmail'],
            Yii::$app->params['senderName'],
        );

        if ($contact) {
            Yii::$app->session->setFlash(
                'success',
                'Thank you for contacting us. We will respond to you as soon as possible.',
            );

            return $this->refresh();
        }

        return $this->render('contact', ['model' => $model]);
    }

    /**
     * Displays about page.
     *
     * @return string
     */
    public function actionAbout(): string
    {
        return $this->render('about');
    }
}
