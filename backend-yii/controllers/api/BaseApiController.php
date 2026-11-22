<?php

declare(strict_types=1);

namespace app\controllers\api;

use Yii;
use yii\filters\ContentNegotiator;
use yii\filters\Cors;
use yii\web\Controller;
use yii\web\Response;

class BaseApiController extends Controller
{
    public $enableCsrfValidation = false;

    public function behaviors(): array
    {
        $origins = array_map('trim', explode(',', getenv('CORS_ORIGIN') ?: 'http://localhost:5173'));

        return [
            'cors' => [
                'class' => Cors::class,
                'cors' => [
                    'Origin' => $origins,
                    'Access-Control-Request-Method' => ['GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'OPTIONS'],
                    'Access-Control-Request-Headers' => ['*'],
                    'Access-Control-Max-Age' => 86400,
                ],
            ],
            'contentNegotiator' => [
                'class' => ContentNegotiator::class,
                'formats' => ['application/json' => Response::FORMAT_JSON],
            ],
        ];
    }

    public function actionOptions(): null
    {
        Yii::$app->response->statusCode = 204;
        return null;
    }

    protected function success(mixed $data = null): array
    {
        return [
            'success' => true,
            'data' => $data,
            'error' => null,
        ];
    }

    protected function failure(string $code, string $message, int $status): array
    {
        Yii::$app->response->statusCode = $status;

        return [
            'success' => false,
            'data' => null,
            'error' => compact('code', 'message'),
        ];
    }
}
