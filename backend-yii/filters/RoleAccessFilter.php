<?php

declare(strict_types=1);

namespace app\filters;

use Yii;
use yii\base\ActionFilter;

class RoleAccessFilter extends ActionFilter
{
    public array $roles = [];
    public array $readRoles = [];
    public array $writeRoles = [];

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

        $requiredRoles = $this->requiredRoles();

        if ($requiredRoles !== [] && !in_array($user->identity->role, $requiredRoles, true)) {
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

    private function requiredRoles(): array
    {
        if ($this->roles !== []) {
            return $this->roles;
        }

        $request = Yii::$app->request;

        return ($request->isGet || $request->isHead || $request->isOptions)
            ? $this->readRoles
            : $this->writeRoles;
    }
}
