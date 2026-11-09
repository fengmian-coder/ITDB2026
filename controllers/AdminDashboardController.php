<?php
/** WorldCup Journey | D | Shared administrator dashboard. */

namespace app\controllers;

use yii\filters\AccessControl;
use yii\filters\VerbFilter;
use yii\web\Controller;

class AdminDashboardController extends Controller
{
    public function behaviors(): array
    {
        return [
            'access' => ['class' => AccessControl::class, 'rules' => [['allow' => true, 'roles' => ['@']]]],
            'verbs' => ['class' => VerbFilter::class, 'actions' => ['index' => ['GET', 'HEAD']]],
        ];
    }

    public function actionIndex(): string
    {
        return $this->render('index');
    }
}
