<?php

declare(strict_types=1);

namespace app\controllers\api;

use app\filters\RoleAccessFilter;
use yii\filters\auth\HttpBearerAuth;
use yii\filters\VerbFilter;

class AdminController extends BaseApiController
{
    public function behaviors(): array
    {
        return array_merge(parent::behaviors(), [
            'authenticator' => [
                'class' => HttpBearerAuth::class,
            ],
            'roles' => [
                'class' => RoleAccessFilter::class,
                'roles' => ['ADMIN'],
            ],
            'verbs' => [
                'class' => VerbFilter::class,
                'actions' => [
                    'ping' => ['get'],
                    'options' => ['options'],
                ],
            ],
        ]);
    }

    public function actionPing(): array
    {
        return $this->success([
            'service' => 'yii',
            'area' => 'admin',
            'status' => 'ok',
        ]);
    }
}
