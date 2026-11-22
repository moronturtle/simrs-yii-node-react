<?php

declare(strict_types=1);

namespace app\controllers\api;

use Yii;
use app\filters\RoleAccessFilter;
use yii\data\ActiveDataProvider;
use yii\db\ActiveQuery;
use yii\db\ActiveRecord;
use yii\db\IntegrityException;
use yii\filters\auth\HttpBearerAuth;
use yii\filters\VerbFilter;

abstract class BaseCrudController extends BaseApiController
{
    protected array $searchableFields = [];

    abstract protected function modelClass(): string;

    public function behaviors(): array
    {
        return array_merge(parent::behaviors(), [
            'authenticator' => [
                'class' => HttpBearerAuth::class,
            ],
            'roles' => [
                'class' => RoleAccessFilter::class,
                'readRoles' => $this->readRoles(),
                'writeRoles' => $this->writeRoles(),
            ],
            'verbs' => [
                'class' => VerbFilter::class,
                'actions' => [
                    'index' => ['get'],
                    'view' => ['get'],
                    'create' => ['post'],
                    'update' => ['put', 'patch'],
                    'delete' => ['delete'],
                    'options' => ['options'],
                ],
            ],
        ]);
    }

    public function actionIndex(): array
    {
        $query = $this->prepareIndexQuery();
        $this->applyIndexFilters($query);

        $page = max(1, (int) Yii::$app->request->get('page', 1));
        $pageSize = min(100, max(1, (int) Yii::$app->request->get('per-page', 20)));

        $provider = new ActiveDataProvider([
            'query' => $query,
            'pagination' => [
                'page' => $page - 1,
                'pageSize' => $pageSize,
            ],
            'sort' => [
                'defaultOrder' => ['id' => SORT_DESC],
            ],
        ]);

        return $this->success([
            'items' => array_map(fn (ActiveRecord $model) => $this->serializeModel($model), $provider->getModels()),
            'pagination' => [
                'page' => $page,
                'page_size' => $pageSize,
                'total' => $provider->getTotalCount(),
            ],
        ]);
    }

    public function actionView(int $id): array
    {
        return $this->success($this->serializeModel($this->findModel($id)));
    }

    public function actionCreate(): array
    {
        $class = $this->modelClass();
        $model = new $class();

        return $this->saveModel($model, 201);
    }

    public function actionUpdate(int $id): array
    {
        return $this->saveModel($this->findModel($id));
    }

    public function actionDelete(int $id): array
    {
        try {
            $this->findModel($id)->delete();
            return $this->success(['deleted' => true]);
        } catch (IntegrityException) {
            return $this->failure('CONFLICT', 'Data masih dipakai data lain', 409);
        }
    }

    protected function readRoles(): array
    {
        return ['ADMIN', 'PENDAFTARAN', 'DOKTER', 'FARMASI', 'LAB', 'KASIR'];
    }

    protected function writeRoles(): array
    {
        return ['ADMIN'];
    }

    protected function prepareIndexQuery(): ActiveQuery
    {
        $class = $this->modelClass();
        return $class::find();
    }

    protected function applyIndexFilters(ActiveQuery $query): void
    {
        $search = trim((string) Yii::$app->request->get('q', ''));

        if ($search === '' || $this->searchableFields === []) {
            return;
        }

        $conditions = ['or'];
        foreach ($this->searchableFields as $field) {
            $conditions[] = ['ilike', $field, $search];
        }

        $query->andFilterWhere($conditions);
    }

    protected function serializeModel(ActiveRecord $model): array
    {
        return $model->toArray();
    }

    protected function findModel(int $id): ActiveRecord
    {
        $class = $this->modelClass();
        $model = $class::findOne($id);

        if (!$model) {
            Yii::$app->response->statusCode = 404;
            Yii::$app->response->data = [
                'success' => false,
                'data' => null,
                'error' => ['code' => 'NOT_FOUND', 'message' => 'Data tidak ditemukan'],
            ];
            Yii::$app->response->send();
            Yii::$app->end();
        }

        return $model;
    }

    private function saveModel(ActiveRecord $model, int $createdStatus = 200): array
    {
        $model->load(Yii::$app->request->bodyParams, '');

        try {
            if (!$model->save()) {
                return $this->failure('VALIDATION_ERROR', 'Data tidak valid', 422, $model->errors);
            }
        } catch (IntegrityException) {
            return $this->failure('CONFLICT', 'Data melanggar aturan unik atau relasi', 409, $model->errors);
        }

        if ($createdStatus === 201) {
            Yii::$app->response->statusCode = 201;
        }

        return $this->success($this->serializeModel($model));
    }
}
