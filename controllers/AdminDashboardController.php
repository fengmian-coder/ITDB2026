<?php
/**
 * WorldCup Journey - 管理工作台与真实内容摘要。
 * 模块：D 后台入口 / A 视觉统一；负责人：D、A。
 * 依赖：D 的管理员会话、现有业务表；最近修改：2026-11-10。
 */

namespace app\controllers;

use yii\filters\AccessControl;
use yii\filters\VerbFilter;
use yii\web\Controller;
use yii\db\Query;
use app\models\GuestbookMessage;

class AdminDashboardController extends Controller
{
    public $layout = 'admin';

    public function behaviors(): array
    {
        return [
            'access' => ['class' => AccessControl::class, 'rules' => [['allow' => true, 'roles' => ['@']]]],
            'verbs' => ['class' => VerbFilter::class, 'actions' => ['index' => ['GET', 'HEAD']]],
        ];
    }

    public function actionIndex(): string
    {
        $overview = [
            'pending' => (int) (new Query())->from('guestbook_message')->where(['status' => GuestbookMessage::PENDING])->count(),
            'matches' => (int) (new Query())->from('wc_match')->count(),
            'members' => (int) (new Query())->from('project_member')->count(),
            'documents' => (int) (new Query())->from('project_document')->count(),
        ];
        return $this->render('index', compact('overview'));
    }
}
