<?php

declare(strict_types=1);

namespace app\models;

use yii\db\ActiveQuery;

class Examination extends BaseRecord
{
    public static function tableName(): string
    {
        return 'examinations';
    }

    public function rules(): array
    {
        return [
            [['visit_id', 'primary_diagnosis', 'examined_by'], 'required'],
            [['visit_id', 'examined_by'], 'integer'],
            [['visit_id'], 'unique'],
            [['visit_id'], 'exist', 'targetRelation' => 'visit'],
            [['examined_by'], 'exist', 'targetRelation' => 'examinedBy'],
            [['anamnesis', 'physical_exam', 'secondary_diagnosis'], 'string'],
            [['primary_diagnosis'], 'string', 'max' => 255],
            [['vital_signs'], 'safe'],
            [['examined_at'], 'safe'],
        ];
    }

    public function getVisit(): ActiveQuery
    {
        return $this->hasOne(Visit::class, ['id' => 'visit_id']);
    }

    public function getExaminedBy(): ActiveQuery
    {
        return $this->hasOne(User::class, ['id' => 'examined_by']);
    }
}
