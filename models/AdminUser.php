<?php
/** WorldCup Journey | D | Database-backed administrator identity. */

namespace app\models;

use Yii;
use yii\db\ActiveRecord;
use yii\web\IdentityInterface;

class AdminUser extends ActiveRecord implements IdentityInterface
{
    public static function tableName(): string
    {
        return 'admin_user';
    }

    public static function findIdentity($id): ?self
    {
        return self::findOne(['id' => $id, 'status' => 1]);
    }

    public static function findIdentityByAccessToken($token, $type = null): ?self
    {
        return null;
    }

    public function getId(): string
    {
        return (string) $this->id;
    }

    public function getAuthKey(): string
    {
        return $this->auth_key;
    }

    public function validateAuthKey($authKey): bool
    {
        return is_string($authKey) && hash_equals($this->auth_key, $authKey);
    }

    public static function canManage($identity): bool
    {
        return $identity instanceof self && (int) $identity->status === 1;
    }

    public function verifyPassword(string $password): bool
    {
        return Yii::$app->security->validatePassword($password, $this->password_hash);
    }
}
