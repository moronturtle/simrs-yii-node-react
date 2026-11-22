<?php

declare(strict_types=1);

namespace app\models;

use yii\db\ActiveQuery;

class Prescription extends BaseRecord
{
    public const STATUSES = ['PRESCRIBED', 'DISPENSED', 'CANCELLED'];

    public static function tableName(): string
    {
        return 'prescriptions';
    }

    public function rules(): array
    {
        return [
            [['visit_id', 'doctor_id'], 'required'],
            [['visit_id', 'doctor_id'], 'integer'],
            [['visit_id'], 'exist', 'targetRelation' => 'visit'],
            [['doctor_id'], 'exist', 'targetRelation' => 'doctor'],
            [['status'], 'in', 'range' => self::STATUSES],
            [['notes'], 'string'],
        ];
    }

    public function getVisit(): ActiveQuery
    {
        return $this->hasOne(Visit::class, ['id' => 'visit_id']);
    }

    public function getDoctor(): ActiveQuery
    {
        return $this->hasOne(User::class, ['id' => 'doctor_id']);
    }

    public function getItems(): ActiveQuery
    {
        return $this->hasMany(PrescriptionItem::class, ['prescription_id' => 'id']);
    }
}
