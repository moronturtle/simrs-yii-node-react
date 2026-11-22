<?php

declare(strict_types=1);

namespace app\controllers\api;

use Yii;
use app\filters\RoleAccessFilter;
use app\models\LabOrder;
use app\models\Visit;
use app\services\LabOrderService;
use app\services\ServiceException;
use yii\filters\auth\HttpBearerAuth;
use yii\filters\VerbFilter;

class LabOrderController extends BaseApiController
{
    public function behaviors(): array
    {
        return array_merge(parent::behaviors(), [
            'authenticator' => ['class' => HttpBearerAuth::class],
            'roles' => [
                'class' => RoleAccessFilter::class,
                'readRoles' => ['ADMIN', 'PENDAFTARAN', 'DOKTER', 'FARMASI', 'LAB', 'KASIR'],
                'actionRoles' => ['create' => ['DOKTER']],
            ],
            'verbs' => [
                'class' => VerbFilter::class,
                'actions' => [
                    'index' => ['get'],
                    'create' => ['post'],
                    'options' => ['options'],
                ],
            ],
        ]);
    }

    public function actionIndex(int $visit_id): array
    {
        $orders = LabOrder::find()
            ->with(['requestedBy', 'result.resultedBy'])
            ->where(['visit_id' => $visit_id])
            ->orderBy(['id' => SORT_DESC])
            ->all();

        return $this->success([
            'items' => array_map(fn (LabOrder $order) => $this->serialize($order), $orders),
        ]);
    }

    public function actionCreate(int $visit_id): array
    {
        $visit = Visit::findOne($visit_id);
        if (!$visit) {
            return $this->failure('NOT_FOUND', 'Kunjungan tidak ditemukan', 404);
        }

        try {
            $order = (new LabOrderService())->create(
                $visit,
                Yii::$app->request->bodyParams,
                (int) Yii::$app->user->id,
            );
        } catch (ServiceException $exception) {
            return $this->failure('LAB_ORDER_ERROR', $exception->getMessage(), $exception->statusCode, $exception->details);
        }

        Yii::$app->response->statusCode = 201;
        return $this->success($this->serialize($this->findModel($order->id)));
    }

    private function serialize(LabOrder $order): array
    {
        return array_merge($order->toArray(), [
            'requested_by_user' => $order->requestedBy?->toArray(),
            'result' => $order->result ? array_merge($order->result->toArray(), [
                'resulted_by_user' => $order->result->resultedBy?->toArray(),
            ]) : null,
        ]);
    }

    private function findModel(int $id): LabOrder
    {
        $model = LabOrder::find()
            ->with(['requestedBy', 'result.resultedBy'])
            ->where(['id' => $id])
            ->one();

        if (!$model) {
            Yii::$app->response->statusCode = 404;
            Yii::$app->end();
        }

        return $model;
    }
}
