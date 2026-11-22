<?php

declare(strict_types=1);

namespace app\models;

use yii\db\ActiveQuery;

class PrescriptionItem extends BaseRecord
{
    public static function tableName(): string
    {
        return 'prescription_items';
    }

    public function rules(): array
    {
        return [
            [['prescription_id', 'medicine_id', 'dose', 'frequency', 'quantity', 'price'], 'required'],
            [['prescription_id', 'medicine_id', 'dispensed_by'], 'integer'],
            [['prescription_id'], 'exist', 'targetRelation' => 'prescription'],
            [['medicine_id'], 'exist', 'targetRelation' => 'medicine'],
            [['dispensed_by'], 'exist', 'targetRelation' => 'dispensedBy'],
            [['dose', 'frequency'], 'string', 'max' => 128],
            [['quantity'], 'integer', 'min' => 1],
            [['price'], 'number', 'min' => 0],
            [['dispensed_at'], 'safe'],
        ];
    }

    public function getPrescription(): ActiveQuery
    {
        return $this->hasOne(Prescription::class, ['id' => 'prescription_id']);
    }

    public function getMedicine(): ActiveQuery
    {
        return $this->hasOne(Medicine::class, ['id' => 'medicine_id']);
    }

    public function getDispensedBy(): ActiveQuery
    {
        return $this->hasOne(User::class, ['id' => 'dispensed_by']);
    }
}
