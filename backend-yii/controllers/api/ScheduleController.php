<?php

declare(strict_types=1);

namespace app\controllers\api;

use Yii;
use app\models\DoctorSchedule;
use yii\db\ActiveQuery;
use yii\db\ActiveRecord;

class ScheduleController extends BaseCrudController
{
    protected function modelClass(): string
    {
        return DoctorSchedule::class;
    }

    protected function prepareIndexQuery(): ActiveQuery
    {
        return DoctorSchedule::find()->with(['doctor', 'poli']);
    }

    protected function applyIndexFilters(ActiveQuery $query): void
    {
        $request = Yii::$app->request;
        $query->andFilterWhere(['doctor_id' => $request->get('doctor_id')]);
        $query->andFilterWhere(['poli_id' => $request->get('poli_id')]);
        $query->andFilterWhere(['day_of_week' => $request->get('day_of_week')]);
        $query->andFilterWhere(['is_active' => $request->get('is_active')]);
    }

    protected function serializeModel(ActiveRecord $model): array
    {
        return array_merge($model->toArray(), [
            'doctor' => $model->doctor?->toArray(),
            'poli' => $model->poli?->toArray(),
        ]);
    }
}
