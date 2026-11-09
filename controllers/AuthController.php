<?php
/** WorldCup Journey | D | Administrator session routes. */

namespace app\controllers;

use app\models\AdminLoginForm;
use Yii;
use yii\filters\VerbFilter;
use yii\web\Controller;
use yii\web\Response;

class AuthController extends Controller
{
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
