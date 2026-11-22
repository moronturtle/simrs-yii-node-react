<?php

declare(strict_types=1);

namespace app\models;

use yii\db\ActiveQuery;

class VisitAction extends BaseRecord
{
    public static function tableName(): string
    {
        return 'visit_actions';
    }

    public function rules(): array
    {
        return [
            [['visit_id', 'medical_action_id', 'quantity', 'price'], 'required'],
            [['visit_id', 'medical_action_id'], 'integer'],
            [['visit_id'], 'exist', 'targetRelation' => 'visit'],
            [['medical_action_id'], 'exist', 'targetRelation' => 'medicalAction'],
            [['quantity'], 'integer', 'min' => 1],
            [['price'], 'number', 'min' => 0],
            [['notes'], 'string'],
        ];
    }

    public function getVisit(): ActiveQuery
    {
        return $this->hasOne(Visit::class, ['id' => 'visit_id']);
    }

    public function getMedicalAction(): ActiveQuery
    {
        return $this->hasOne(MedicalAction::class, ['id' => 'medical_action_id']);
    }
}
