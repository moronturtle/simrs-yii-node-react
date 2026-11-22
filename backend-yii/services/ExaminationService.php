<?php

declare(strict_types=1);

namespace app\services;

use Yii;
use app\models\Examination;
use app\models\Visit;
use Throwable;

class ExaminationService
{
    public function save(Visit $visit, array $data, int $userId): Examination
    {
        if (in_array($visit->status, ['PAID', 'CANCELLED'], true)) {
            throw new ServiceException('Kunjungan selesai tidak bisa diperiksa lagi', 409);
        }

        $vitalSigns = $data['vital_signs'] ?? null;
        if ($vitalSigns !== null && !is_array($vitalSigns)) {
            throw new ServiceException('vital_signs harus berupa object JSON', 422);
        }

        $examination = Examination::findOne(['visit_id' => $visit->id]) ?? new Examination([
            'visit_id' => $visit->id,
        ]);

        $examination->anamnesis = trim((string) ($data['anamnesis'] ?? '')) ?: null;
        $examination->vital_signs = $vitalSigns;
        $examination->physical_exam = trim((string) ($data['physical_exam'] ?? '')) ?: null;
        $examination->primary_diagnosis = trim((string) ($data['primary_diagnosis'] ?? ''));
        $examination->secondary_diagnosis = trim((string) ($data['secondary_diagnosis'] ?? '')) ?: null;
        $examination->examined_by = $userId;
        $examination->examined_at = date('Y-m-d H:i:s');

        $transaction = Yii::$app->db->beginTransaction();

        try {
            if (!$examination->save()) {
                throw new ServiceException('Data pemeriksaan tidak valid', 422, $examination->errors);
            }

            if ($visit->status === 'REGISTERED') {
                $visit->status = 'IN_EXAM';
                if (!$visit->save()) {
                    throw new ServiceException('Status kunjungan tidak bisa diubah', 422, $visit->errors);
                }
            }

            $transaction->commit();
            return $examination;
        } catch (Throwable $exception) {
            $transaction->rollBack();
            throw $exception;
        }
    }
}
