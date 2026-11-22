<?php

declare(strict_types=1);

namespace app\models;

use yii\db\ActiveQuery;

class Invoice extends BaseRecord
{
    public const STATUSES = ['UNPAID', 'PARTIAL', 'PAID', 'CANCELLED'];

    public static function tableName(): string
    {
        return 'invoices';
    }

    public function rules(): array
    {
        return [
            [['visit_id', 'invoice_number'], 'required'],
            [['visit_id', 'invoice_number'], 'unique'],
            [['visit_id'], 'integer'],
            [['visit_id'], 'exist', 'targetRelation' => 'visit'],
            [['invoice_number'], 'string', 'max' => 32],
            [['status'], 'in', 'range' => self::STATUSES],
            [['total_amount', 'paid_amount'], 'number', 'min' => 0],
        ];
    }

    public function getVisit(): ActiveQuery
    {
        return $this->hasOne(Visit::class, ['id' => 'visit_id']);
    }

    public function getItems(): ActiveQuery
    {
        return $this->hasMany(InvoiceItem::class, ['invoice_id' => 'id']);
    }

    public function getPayments(): ActiveQuery
    {
        return $this->hasMany(Payment::class, ['invoice_id' => 'id']);
    }
}
