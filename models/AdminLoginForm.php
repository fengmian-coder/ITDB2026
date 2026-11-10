<?php
/** WorldCup Journey | D | Administrator sign-in validation. */

namespace app\models;

use Yii;
use yii\base\Model;

class AdminLoginForm extends Model
{
    public $username;
    public $password;

    public function rules(): array
    {
        return [
            [['username', 'password'], 'required'],
            ['username', 'string', 'max' => 60],
            ['password', 'string', 'max' => 72],
        ];
    }

    public function login(): bool
    {
        if (!$this->validate()) {
            return false;
        }

        $admin = AdminUser::findOne(['username' => $this->username, 'status' => 1]);
        if (!$admin || !$admin->verifyPassword($this->password) || !Yii::$app->user->login($admin)) {
            $this->addError('password', '账号或密码不正确');
            return false;
        }

        $admin->updateAttributes(['last_login_at' => gmdate('Y-m-d H:i:s')]);
        return true;
    }
}
