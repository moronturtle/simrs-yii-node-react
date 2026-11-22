<?php

declare(strict_types=1);

namespace app\models;

use yii\db\ActiveQuery;

class Payment extends BaseRecord
{
    public const METHODS = ['CASH', 'NON_CASH'];

    public static function tableName(): string
    {
        return 'payments';
    }

    public function rules(): array
    {
        return [
            [['invoice_id', 'payment_number', 'amount', 'method', 'idempotency_key', 'paid_by'], 'required'],
            [['invoice_id', 'paid_by'], 'integer'],
            [['payment_number'], 'unique'],
            [['idempotency_key'], 'unique'],
            [['payment_number'], 'string', 'max' => 32],
            [['idempotency_key'], 'string', 'max' => 128],
            [['amount'], 'number', 'min' => 0.01],
            [['method'], 'in', 'range' => self::METHODS],
            [['invoice_id'], 'exist', 'targetRelation' => 'invoice'],
            [['paid_by'], 'exist', 'targetRelation' => 'paidBy'],
        ];
    }

    public function getInvoice(): ActiveQuery
    {
        return $this->hasOne(Invoice::class, ['id' => 'invoice_id']);
    }

    public function getPaidBy(): ActiveQuery
    {
        return $this->hasOne(User::class, ['id' => 'paid_by']);
    }
}
