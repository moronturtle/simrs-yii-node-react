<?php

declare(strict_types=1);

namespace app\controllers\api;

use Yii;
use app\filters\RoleAccessFilter;
use app\models\Visit;
use app\services\ServiceException;
use app\services\VisitService;
use yii\data\ActiveDataProvider;
use yii\db\ActiveRecord;
use yii\filters\auth\HttpBearerAuth;
use yii\filters\VerbFilter;

class VisitController extends BaseApiController
{
    public function behaviors(): array
    {
        return array_merge(parent::behaviors(), [
            'authenticator' => [
                'class' => HttpBearerAuth::class,
            ],
            'roles' => [
                'class' => RoleAccessFilter::class,
                'readRoles' => ['ADMIN', 'PENDAFTARAN', 'DOKTER', 'FARMASI', 'LAB', 'KASIR'],
                'actionRoles' => [
                    'create' => ['ADMIN', 'PENDAFTARAN'],
                    'update-status' => ['ADMIN', 'PENDAFTARAN', 'DOKTER'],
                ],
            ],
            'verbs' => [
                'class' => VerbFilter::class,
                'actions' => [
                    'index' => ['get'],
                    'view' => ['get'],
                    'create' => ['post'],
                    'update-status' => ['patch'],
                    'options' => ['options'],
                ],
            ],
        ]);
    }

    public function actionIndex(): array
    {
        $request = Yii::$app->request;
        $query = Visit::find()->joinWith(['patient', 'doctor', 'poli', 'schedule']);

        $query->andFilterWhere(['visits.status' => $request->get('status')]);
        $query->andFilterWhere(['visits.visit_date' => $request->get('visit_date')]);
        $query->andFilterWhere(['visits.patient_id' => $request->get('patient_id')]);
        $query->andFilterWhere(['visits.poli_id' => $request->get('poli_id')]);
        $query->andFilterWhere(['visits.doctor_id' => $request->get('doctor_id')]);

        $search = trim((string) $request->get('q', ''));
        if ($search !== '') {
            $query->andFilterWhere(['or',
                ['ilike', 'visits.visit_number', $search],
                ['ilike', 'patients.name', $search],
                ['ilike', 'patients.medical_record_no', $search],
            ]);
        }

        $page = max(1, (int) $request->get('page', 1));
        $pageSize = min(100, max(1, (int) $request->get('per-page', 20)));
        $provider = new ActiveDataProvider([
            'query' => $query,
            'pagination' => ['page' => $page - 1, 'pageSize' => $pageSize],
            'sort' => [
                'defaultOrder' => ['visit_date' => SORT_DESC, 'queue_number' => SORT_ASC],
            ],
        ]);

        return $this->success([
            'items' => array_map(fn (Visit $visit) => $this->serialize($visit), $provider->getModels()),
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

    public function actionCreate(): array
    {
        try {
            $visit = (new VisitService())->create(
                Yii::$app->request->bodyParams,
                (int) Yii::$app->user->id,
            );
        } catch (ServiceException $exception) {
            return $this->failure(
                'VISIT_ERROR',
                $exception->getMessage(),
                $exception->statusCode,
                $exception->details,
            );
        }

        Yii::$app->response->statusCode = 201;
        return $this->success($this->serialize($visit));
    }

    public function actionUpdateStatus(int $id): array
    {
        $visit = $this->findModel($id);
        $status = (string) (Yii::$app->request->bodyParams['status'] ?? '');

        try {
            $visit = (new VisitService())->updateStatus($visit, $status);
        } catch (ServiceException $exception) {
            return $this->failure(
                'VISIT_STATUS_ERROR',
                $exception->getMessage(),
                $exception->statusCode,
            );
        }

        return $this->success($this->serialize($visit));
    }

    private function serialize(Visit $visit): array
    {
        return array_merge($visit->toArray(), [
            'patient' => $visit->patient?->toArray(),
            'poli' => $visit->poli?->toArray(),
            'doctor' => $visit->doctor?->toArray(),
            'schedule' => $visit->schedule?->toArray(),
        ]);
    }

    private function findModel(int $id): ActiveRecord
    {
        $model = Visit::find()->with(['patient', 'doctor', 'poli', 'schedule'])->where(['id' => $id])->one();

        if (!$model) {
            Yii::$app->response->statusCode = 404;
            Yii::$app->response->data = [
                'success' => false,
                'data' => null,
                'error' => ['code' => 'NOT_FOUND', 'message' => 'Kunjungan tidak ditemukan'],
            ];
            Yii::$app->response->send();
            Yii::$app->end();
        }

        return $model;
    }
}
