<?php

declare(strict_types=1);

namespace app\models;

class Medicine extends BaseRecord
{
    public static function tableName(): string
    {
        return 'medicines';
    }

    public function rules(): array
    {
        return [
            [['code', 'name', 'unit', 'price'], 'required'],
            [['code'], 'unique'],
            [['code', 'unit'], 'string', 'max' => 32],
            [['name'], 'string', 'max' => 128],
            [['price'], 'number', 'min' => 0],
            [['stock'], 'integer', 'min' => 0],
            [['is_active'], 'boolean'],
        ];
    }
}
