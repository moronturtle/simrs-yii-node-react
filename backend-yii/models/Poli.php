<?php

declare(strict_types=1);

namespace app\models;

class Poli extends BaseRecord
{
    public static function tableName(): string
    {
        return 'polis';
    }

    public function rules(): array
    {
        return [
            [['code', 'name'], 'required'],
            [['code'], 'unique'],
            [['code'], 'string', 'max' => 32],
            [['name'], 'string', 'max' => 128],
            [['is_active'], 'boolean'],
        ];
    }
}
