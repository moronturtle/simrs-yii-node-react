<?php

declare(strict_types=1);

namespace app\controllers\api;

use Yii;
use app\filters\RoleAccessFilter;
use app\models\Visit;
use app\models\VisitAction;
use app\services\ServiceException;
use app\services\VisitActionService;
use yii\filters\auth\HttpBearerAuth;
use yii\filters\VerbFilter;

class VisitActionController extends BaseApiController
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
        $items = VisitAction::find()
            ->with(['medicalAction'])
            ->where(['visit_id' => $visit_id])
            ->orderBy(['id' => SORT_ASC])
            ->all();

        return $this->success([
            'items' => array_map(fn (VisitAction $item) => $this->serialize($item), $items),
        ]);
    }

    public function actionCreate(int $visit_id): array
    {
        $visit = Visit::findOne($visit_id);
        if (!$visit) {
            return $this->failure('NOT_FOUND', 'Kunjungan tidak ditemukan', 404);
        }

        try {
            $item = (new VisitActionService())->add($visit, Yii::$app->request->bodyParams);
        } catch (ServiceException $exception) {
            return $this->failure('VISIT_ACTION_ERROR', $exception->getMessage(), $exception->statusCode, $exception->details);
        }

        Yii::$app->response->statusCode = 201;
        return $this->success($this->serialize($item));
    }

    private function serialize(VisitAction $item): array
    {
        return array_merge($item->toArray(), [
            'medical_action' => $item->medicalAction?->toArray(),
        ]);
    }
}
