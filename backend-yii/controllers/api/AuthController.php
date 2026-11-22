<?php

declare(strict_types=1);

namespace app\controllers\api;

use Yii;
use app\models\User;
use app\services\JwtService;
use yii\filters\auth\HttpBearerAuth;
use yii\filters\VerbFilter;

class AuthController extends BaseApiController
{
    public function behaviors(): array
    {
        return array_merge(parent::behaviors(), [
            'authenticator' => [
                'class' => HttpBearerAuth::class,
                'only' => ['me'],
            ],
            'verbs' => [
                'class' => VerbFilter::class,
                'actions' => [
                    'login' => ['post'],
                    'me' => ['get'],
                    'options' => ['options'],
                ],
            ],
        ]);
    }

    public function actionLogin(): array
    {
        $body = Yii::$app->request->bodyParams;
        $username = trim((string) ($body['username'] ?? ''));
        $password = (string) ($body['password'] ?? '');

        if ($username === '' || $password === '') {
            return $this->failure('VALIDATION_ERROR', 'Username dan password wajib diisi', 422);
        }

        $user = User::findByUsername($username);

        if (!$user || !$user->validatePassword($password)) {
            return $this->failure('INVALID_CREDENTIALS', 'Username atau password salah', 401);
        }

        $jwt = new JwtService();

        return $this->success([
            'token' => $jwt->issue($user),
            'token_type' => 'Bearer',
            'expires_in' => $jwt->ttlSeconds(),
            'user' => $user->toArray(),
        ]);
    }

    public function actionMe(): array
    {
        return $this->success(Yii::$app->user->identity->toArray());
    }
}
