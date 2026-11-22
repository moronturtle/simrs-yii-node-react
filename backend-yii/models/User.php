<?php

declare(strict_types=1);

namespace app\models;

use Yii;
use app\services\JwtService;
use yii\db\ActiveRecord;
use yii\web\IdentityInterface;

class User extends ActiveRecord implements IdentityInterface
{
    public static function tableName(): string
    {
        return 'users';
    }

    public static function findIdentity($id): ?IdentityInterface
    {
        return static::findOne(['id' => $id, 'is_active' => true]);
    }

    public static function findIdentityByAccessToken($token, $type = null): ?IdentityInterface
    {
        $payload = (new JwtService())->decode((string) $token);

        if (!isset($payload['sub'])) {
            return null;
        }

        return static::findIdentity($payload['sub']);
    }

    public static function findByUsername(string $username): ?self
    {
        return static::findOne(['username' => $username, 'is_active' => true]);
    }

    public function getId(): int|string
    {
        return $this->id;
    }

    public function getAuthKey(): string
    {
        return '';
    }

    public function validateAuthKey($authKey): bool
    {
        return false;
    }

    public function validatePassword(string $password): bool
    {
        return Yii::$app->security->validatePassword($password, $this->password_hash);
    }

    public function fields(): array
    {
        return ['id', 'username', 'name', 'role'];
    }
}
