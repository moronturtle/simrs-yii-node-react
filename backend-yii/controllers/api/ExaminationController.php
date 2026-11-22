<?php

declare(strict_types=1);

namespace app\controllers\api;

use Yii;
use app\filters\RoleAccessFilter;
use app\models\Examination;
use app\models\Visit;
use app\services\ExaminationService;
use app\services\ServiceException;
use yii\filters\auth\HttpBearerAuth;
use yii\filters\VerbFilter;

class ExaminationController extends BaseApiController
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
                    'view' => ['get'],
                    'create' => ['post'],
                    'options' => ['options'],
                ],
            ],
        ]);
    }

    public function actionView(int $visit_id): array
    {
        $examination = Examination::find()->with(['examinedBy'])->where(['visit_id' => $visit_id])->one();

        if (!$examination) {
            return $this->failure('NOT_FOUND', 'Pemeriksaan belum diisi', 404);
        }

        return $this->success($this->serialize($examination));
    }

    public function actionCreate(int $visit_id): array
    {
        $visit = Visit::findOne($visit_id);
        if (!$visit) {
            return $this->failure('NOT_FOUND', 'Kunjungan tidak ditemukan', 404);
        }

        try {
            $examination = (new ExaminationService())->save(
                $visit,
                Yii::$app->request->bodyParams,
                (int) Yii::$app->user->id,
            );
        } catch (ServiceException $exception) {
            return $this->failure('EXAMINATION_ERROR', $exception->getMessage(), $exception->statusCode, $exception->details);
        }

        Yii::$app->response->statusCode = 201;
        return $this->success($this->serialize($examination));
    }

    private function serialize(Examination $examination): array
    {
        return array_merge($examination->toArray(), [
            'examined_by_user' => $examination->examinedBy?->toArray(),
        ]);
    }
}
