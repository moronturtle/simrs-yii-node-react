<?php

declare(strict_types=1);

namespace app\controllers\api;

use Yii;
use app\filters\RoleAccessFilter;
use app\models\Prescription;
use app\models\Visit;
use app\services\PrescriptionService;
use app\services\ServiceException;
use yii\data\ActiveDataProvider;
use yii\filters\auth\HttpBearerAuth;
use yii\filters\VerbFilter;

class PrescriptionController extends BaseApiController
{
    public function behaviors(): array
    {
        return array_merge(parent::behaviors(), [
            'authenticator' => ['class' => HttpBearerAuth::class],
            'roles' => [
                'class' => RoleAccessFilter::class,
                'readRoles' => ['ADMIN', 'PENDAFTARAN', 'DOKTER', 'FARMASI', 'LAB', 'KASIR'],
                'actionRoles' => [
                    'create' => ['DOKTER'],
                    'dispense' => ['FARMASI'],
                ],
            ],
            'verbs' => [
                'class' => VerbFilter::class,
                'actions' => [
                    'index' => ['get'],
                    'view' => ['get'],
                    'create' => ['post'],
                    'dispense' => ['post'],
                    'options' => ['options'],
                ],
            ],
        ]);
    }

    public function actionIndex(): array
    {
        $request = Yii::$app->request;
        $query = Prescription::find()->with(['visit.patient', 'doctor', 'items.medicine']);
        $query->andFilterWhere(['visit_id' => $request->get('visit_id')]);
        $query->andFilterWhere(['status' => $request->get('status')]);

        $page = max(1, (int) $request->get('page', 1));
        $pageSize = min(100, max(1, (int) $request->get('per-page', 20)));
        $provider = new ActiveDataProvider([
            'query' => $query,
            'pagination' => ['page' => $page - 1, 'pageSize' => $pageSize],
            'sort' => ['defaultOrder' => ['id' => SORT_DESC]],
        ]);

        return $this->success([
            'items' => array_map(fn (Prescription $item) => $this->serialize($item), $provider->getModels()),
            'pagination' => [
                'page' => $page,
                'page_size' => $pageSize,
                'total' => $provider->getTotalCount(),
            ],
        ]);
    }

    public function actionView(int $id): array
    {
        return $this->success($this->serialize($this->findModel($id)));
    }

    public function actionCreate(int $visit_id): array
    {
        $visit = Visit::findOne($visit_id);
        if (!$visit) {
            return $this->failure('NOT_FOUND', 'Kunjungan tidak ditemukan', 404);
        }

        try {
            $prescription = (new PrescriptionService())->create(
                $visit,
                Yii::$app->request->bodyParams,
                (int) Yii::$app->user->id,
            );
        } catch (ServiceException $exception) {
            return $this->failure('PRESCRIPTION_ERROR', $exception->getMessage(), $exception->statusCode, $exception->details);
        }

        Yii::$app->response->statusCode = 201;
        return $this->success($this->serialize($this->findModel($prescription->id)));
    }

    public function actionDispense(int $id): array
    {
        try {
            $prescription = (new PrescriptionService())->dispense(
                $this->findModel($id),
                (int) Yii::$app->user->id,
            );
        } catch (ServiceException $exception) {
            return $this->failure('DISPENSE_ERROR', $exception->getMessage(), $exception->statusCode, $exception->details);
        }

        return $this->success($this->serialize($this->findModel($prescription->id)));
    }

    private function serialize(Prescription $prescription): array
    {
        return array_merge($prescription->toArray(), [
            'visit' => $prescription->visit ? array_merge($prescription->visit->toArray(), [
                'patient' => $prescription->visit->patient?->toArray(),
            ]) : null,
            'doctor' => $prescription->doctor?->toArray(),
            'items' => array_map(
                fn ($item) => array_merge($item->toArray(), ['medicine' => $item->medicine?->toArray()]),
                $prescription->items,
            ),
        ]);
    }

    private function findModel(int $id): Prescription
    {
        $model = Prescription::find()->with(['visit.patient', 'doctor', 'items.medicine'])->where(['id' => $id])->one();

        if (!$model) {
            Yii::$app->response->statusCode = 404;
            Yii::$app->response->data = [
                'success' => false,
                'data' => null,
                'error' => ['code' => 'NOT_FOUND', 'message' => 'Resep tidak ditemukan'],
            ];
            Yii::$app->response->send();
            Yii::$app->end();
        }

        return $model;
    }
}
