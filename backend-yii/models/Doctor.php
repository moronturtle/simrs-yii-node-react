<?php

declare(strict_types=1);

namespace app\models;

class Doctor extends BaseRecord
{
    public static function tableName(): string
    {
        return 'doctors';
    }

    public function rules(): array
    {
        return [
            [['name'], 'required'],
            [['name', 'specialization'], 'string', 'max' => 128],
            [['phone'], 'string', 'max' => 32],
            [['is_active'], 'boolean'],
        ];
    }
}
