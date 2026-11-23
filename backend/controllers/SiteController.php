<?php
/** WorldCup Journey - 后台的统一错误页面。 */
declare(strict_types=1);

namespace backend\controllers;

use yii\web\Controller;
use yii\web\ErrorAction;

class SiteController extends Controller
{
    public function actions(): array
    {
        return [
            'error' => ['class' => ErrorAction::class, 'view' => '@frontend/views/site/error'],
        ];
    }
}
