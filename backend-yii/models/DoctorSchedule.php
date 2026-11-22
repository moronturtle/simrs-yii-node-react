<?php

declare(strict_types=1);

namespace app\models;

use yii\db\ActiveQuery;

class DoctorSchedule extends BaseRecord
{
    public static function tableName(): string
    {
        return 'doctor_schedules';
    }

    public function rules(): array
    {
        return [
            [['doctor_id', 'poli_id', 'day_of_week', 'start_time', 'end_time'], 'required'],
            [['doctor_id', 'poli_id'], 'integer'],
            [['doctor_id'], 'exist', 'targetRelation' => 'doctor'],
            [['poli_id'], 'exist', 'targetRelation' => 'poli'],
            [['day_of_week'], 'integer', 'min' => 0, 'max' => 6],
            [['start_time', 'end_time'], 'time', 'format' => 'php:H:i'],
            [['end_time'], 'compare', 'compareAttribute' => 'start_time', 'operator' => '>'],
            [['doctor_id'], 'unique', 'targetAttribute' => ['doctor_id', 'poli_id', 'day_of_week']],
            [['is_active'], 'boolean'],
        ];
    }

    public function getDoctor(): ActiveQuery
    {
        return $this->hasOne(Doctor::class, ['id' => 'doctor_id']);
    }

    public function getPoli(): ActiveQuery
    {
        return $this->hasOne(Poli::class, ['id' => 'poli_id']);
    }
}
