<?php

declare(strict_types=1);

namespace app\models;

use yii\db\ActiveQuery;

class Visit extends BaseRecord
{
    public const STATUSES = [
        'REGISTERED',
        'IN_EXAM',
        'PHARMACY',
        'LAB',
        'BILLING',
        'PAID',
        'CANCELLED',
    ];

    public static function tableName(): string
    {
        return 'visits';
    }

    public function rules(): array
    {
        return [
            [['visit_number', 'patient_id', 'poli_id', 'doctor_id', 'queue_number', 'registered_by'], 'required'],
            [['visit_number'], 'unique'],
            [['visit_number'], 'string', 'max' => 32],
            [['patient_id', 'poli_id', 'doctor_id', 'schedule_id', 'registered_by'], 'integer'],
            [['visit_date'], 'date', 'format' => 'php:Y-m-d'],
            [['queue_number'], 'integer', 'min' => 1],
            [['status'], 'in', 'range' => self::STATUSES],
            [['chief_complaint'], 'string'],
            [['patient_id'], 'exist', 'targetRelation' => 'patient'],
            [['poli_id'], 'exist', 'targetRelation' => 'poli'],
            [['doctor_id'], 'exist', 'targetRelation' => 'doctor'],
            [['schedule_id'], 'exist', 'targetRelation' => 'schedule'],
            [['registered_by'], 'exist', 'targetRelation' => 'registeredBy'],
        ];
    }

    public function getPatient(): ActiveQuery
    {
        return $this->hasOne(Patient::class, ['id' => 'patient_id']);
    }

    public function getPoli(): ActiveQuery
    {
        return $this->hasOne(Poli::class, ['id' => 'poli_id']);
    }

    public function getDoctor(): ActiveQuery
    {
        return $this->hasOne(Doctor::class, ['id' => 'doctor_id']);
    }

    public function getSchedule(): ActiveQuery
    {
        return $this->hasOne(DoctorSchedule::class, ['id' => 'schedule_id']);
    }

    public function getRegisteredBy(): ActiveQuery
    {
        return $this->hasOne(User::class, ['id' => 'registered_by']);
    }
}
