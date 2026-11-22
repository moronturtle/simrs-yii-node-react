<?php

declare(strict_types=1);

namespace app\models;

class Patient extends BaseRecord
{
    public static function tableName(): string
    {
        return 'patients';
    }

    public function rules(): array
    {
        return [
            [['medical_record_no', 'name'], 'required'],
            [['medical_record_no', 'nik'], 'unique'],
            [['medical_record_no', 'nik', 'phone'], 'string', 'max' => 32],
            [['name'], 'string', 'max' => 128],
            [['date_of_birth'], 'date', 'format' => 'php:Y-m-d'],
            [['gender'], 'in', 'range' => ['L', 'P']],
            [['address'], 'string'],
        ];
    }
}
