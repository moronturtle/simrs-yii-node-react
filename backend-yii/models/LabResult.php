<?php

declare(strict_types=1);

namespace app\models;

use yii\db\ActiveQuery;

class LabResult extends BaseRecord
{
    public static function tableName(): string
    {
        return 'lab_results';
    }

    public function rules(): array
    {
        return [
            [['lab_order_id', 'result_text'], 'required'],
            [['lab_order_id', 'resulted_by'], 'integer'],
            [['lab_order_id'], 'unique'],
            [['result_text'], 'string'],
            [['result_values'], 'safe'],
            [['lab_order_id'], 'exist', 'targetRelation' => 'labOrder'],
            [['resulted_by'], 'exist', 'targetRelation' => 'resultedBy'],
        ];
    }

    public function getLabOrder(): ActiveQuery
    {
        return $this->hasOne(LabOrder::class, ['id' => 'lab_order_id']);
    }

    public function getResultedBy(): ActiveQuery
    {
        return $this->hasOne(User::class, ['id' => 'resulted_by']);
    }
}
