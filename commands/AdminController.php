<?php
/** WorldCup Journey | D | Create an administrator using a generated credential. */

namespace app\commands;

use app\models\AdminUser;
use Yii;
use yii\console\Controller;
use yii\console\ExitCode;
use yii\db\IntegrityException;

class AdminController extends Controller
{
    public function actionCreate(string $username): int
    {
        if (!preg_match('/\A[a-zA-Z0-9_.-]{1,60}\z/', $username)) {
            $this->stderr("Username must contain 1–60 letters, digits, dots, underscores or hyphens.\n");
            return ExitCode::USAGE;
        }

        $password = Yii::$app->security->generateRandomString(32);
        $admin = new AdminUser([
            'username' => $username,
            'password_hash' => Yii::$app->security->generatePasswordHash($password),
            'auth_key' => Yii::$app->security->generateRandomString(),
            'status' => 1,
        ]);

        try {
            $admin->insert(false);
        } catch (IntegrityException $exception) {
            $this->stderr("Could not create administrator. Check whether the username already exists.\n");
            return ExitCode::DATAERR;
        }

        $this->stdout("Generated password (shown once): $password\n");
        return ExitCode::OK;
    }
}
