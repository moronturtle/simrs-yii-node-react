<?php

declare(strict_types=1);

namespace app\models;

use Yii;

class Patient extends BaseRecord
{
    private const TEMP_RM_PREFIX = 'TMP-RM-';

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

    public function beforeValidate(): bool
    {
        if ($this->isNewRecord && trim((string) $this->medical_record_no) === '') {
            $this->medical_record_no = self::TEMP_RM_PREFIX . Yii::$app->security->generateRandomString(16);
        }

        return parent::beforeValidate();
    }

    public function afterSave($insert, $changedAttributes): void
    {
        parent::afterSave($insert, $changedAttributes);

        if ($insert && str_starts_with((string) $this->medical_record_no, self::TEMP_RM_PREFIX)) {
            $number = sprintf('RM-%06d', $this->id);
            Yii::$app->db->createCommand()->update(
                self::tableName(),
                ['medical_record_no' => $number],
                ['id' => $this->id],
            )->execute();
            $this->medical_record_no = $number;
        }
    }
}
