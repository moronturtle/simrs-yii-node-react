<?php

declare(strict_types=1);

namespace app\models;

use yii\db\ActiveQuery;

class InvoiceItem extends BaseRecord
{
    public const TYPES = ['REGISTRATION', 'ACTION', 'LAB', 'MEDICINE', 'ADJUSTMENT'];

    public static function tableName(): string
    {
        return 'invoice_items';
    }

    public function rules(): array
    {
        return [
            [['invoice_id', 'item_type', 'description', 'quantity', 'unit_price', 'total_price'], 'required'],
            [['invoice_id', 'reference_id'], 'integer'],
            [['invoice_id'], 'exist', 'targetRelation' => 'invoice'],
            [['item_type'], 'in', 'range' => self::TYPES],
            [['description'], 'string', 'max' => 255],
            [['quantity'], 'integer', 'min' => 1],
            [['unit_price', 'total_price'], 'number', 'min' => 0],
        ];
    }

    public function getInvoice(): ActiveQuery
    {
        return $this->hasOne(Invoice::class, ['id' => 'invoice_id']);
    }
}
