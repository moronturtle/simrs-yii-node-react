<?php

declare(strict_types=1);

namespace app\filters;

use Yii;
use yii\base\ActionFilter;

class RoleAccessFilter extends ActionFilter
{
    public array $roles = [];

    public function beforeAction($action): bool
    {
        $user = Yii::$app->user;

        if ($user->isGuest) {
            Yii::$app->response->statusCode = 401;
            Yii::$app->response->data = [
                'success' => false,
                'data' => null,
                'error' => ['code' => 'UNAUTHORIZED', 'message' => 'Login diperlukan'],
            ];
            return false;
        }

        if ($this->roles !== [] && !in_array($user->identity->role, $this->roles, true)) {
            Yii::$app->response->statusCode = 403;
            Yii::$app->response->data = [
                'success' => false,
                'data' => null,
                'error' => ['code' => 'FORBIDDEN', 'message' => 'Role tidak punya akses'],
            ];
            return false;
        }

        return parent::beforeAction($action);
    }
}
