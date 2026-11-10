<?php
/**
 * WorldCup Journey - 管理员会话路由，接入 A 的独立登录布局。
 * 模块：D 认证 / A 视觉接入；负责人：D、A。
 * 依赖：AdminLoginForm、D 的会话逻辑；最近修改：2026-11-10。
 */

namespace app\controllers;

use app\models\AdminLoginForm;
use Yii;
use yii\filters\VerbFilter;
use yii\web\Controller;
use yii\web\Response;

class AuthController extends Controller
{
    public $layout = 'auth';

    public function behaviors(): array
    {
        return [
            'verbs' => [
                'class' => VerbFilter::class,
                'actions' => ['login' => ['GET', 'POST'], 'logout' => ['POST']],
            ],
        ];
    }

    public function actionLogin(): string|Response
    {
        if (!Yii::$app->user->isGuest) {
            return $this->redirect(['/admin-dashboard/index']);
        }

        $model = new AdminLoginForm();
        if ($model->load($this->request->post()) && $model->login()) {
            return $this->goBack(['/admin-dashboard/index']);
        }

        $model->password = null;
        return $this->render('login', compact('model'));
    }

    public function actionLogout(): Response
    {
        Yii::$app->user->logout();
        return $this->goHome();
    }
}
