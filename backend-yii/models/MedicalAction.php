<?php

declare(strict_types=1);

namespace app\models;

class MedicalAction extends BaseRecord
{
    public static function tableName(): string
    {
        return 'medical_actions';
    }

    public function rules(): array
    {
        return [
            [['code', 'name', 'price'], 'required'],
            [['code'], 'unique'],
            [['code'], 'string', 'max' => 32],
            [['name'], 'string', 'max' => 128],
            [['price'], 'number', 'min' => 0],
            [['is_active'], 'boolean'],
        ];
    }
}
