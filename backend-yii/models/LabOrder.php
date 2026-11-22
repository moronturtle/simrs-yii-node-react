<?php

declare(strict_types=1);

namespace app\models;

use yii\db\ActiveQuery;

class LabOrder extends BaseRecord
{
    public const STATUSES = ['REQUESTED', 'IN_PROGRESS', 'COMPLETED', 'FAILED'];

    public static function tableName(): string
    {
        return 'lab_orders';
    }

    public function rules(): array
    {
        return [
            [['visit_id', 'order_number', 'test_name', 'requested_by'], 'required'],
            [['visit_id', 'requested_by'], 'integer'],
            [['order_number'], 'unique'],
            [['order_number'], 'string', 'max' => 32],
            [['test_name'], 'string', 'max' => 128],
            [['price'], 'number', 'min' => 0],
            [['status'], 'in', 'range' => self::STATUSES],
            [['visit_id'], 'exist', 'targetRelation' => 'visit'],
            [['requested_by'], 'exist', 'targetRelation' => 'requestedBy'],
        ];
    }

    public function getVisit(): ActiveQuery
    {
        return $this->hasOne(Visit::class, ['id' => 'visit_id']);
    }

    public function getRequestedBy(): ActiveQuery
    {
        return $this->hasOne(User::class, ['id' => 'requested_by']);
    }

    public function getResult(): ActiveQuery
    {
        return $this->hasOne(LabResult::class, ['lab_order_id' => 'id']);
    }
}
