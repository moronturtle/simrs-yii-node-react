<?php

declare(strict_types=1);

namespace app\services;

use Yii;
use app\models\DoctorSchedule;
use app\models\Patient;
use app\models\Visit;
use Throwable;
use yii\db\IntegrityException;

class VisitService
{
    private const STATUS_ORDER = [
        'REGISTERED' => 0,
        'IN_EXAM' => 1,
        'PHARMACY' => 2,
        'LAB' => 2,
        'BILLING' => 3,
        'PAID' => 4,
        'CANCELLED' => 99,
    ];

    public function create(array $data, int $userId): Visit
    {
        $scheduleId = (int) ($data['schedule_id'] ?? 0);
        $patientId = (int) ($data['patient_id'] ?? 0);

        $schedule = DoctorSchedule::findOne(['id' => $scheduleId, 'is_active' => true]);
        if (!$schedule) {
            throw new ServiceException('Jadwal dokter tidak ditemukan atau tidak aktif', 422);
        }

        if (!Patient::findOne(['id' => $patientId])) {
            throw new ServiceException('Pasien tidak ditemukan', 422);
        }

        $visitDate = date('Y-m-d');
        $transaction = Yii::$app->db->beginTransaction();

        try {
            $queueNumber = $this->nextQueueNumber($schedule->poli_id, $visitDate);
            $visit = new Visit([
                'visit_number' => sprintf('V%s-%d-%04d', date('Ymd'), $schedule->poli_id, $queueNumber),
                'patient_id' => $patientId,
                'poli_id' => $schedule->poli_id,
                'doctor_id' => $schedule->doctor_id,
                'schedule_id' => $schedule->id,
                'visit_date' => $visitDate,
                'queue_number' => $queueNumber,
                'status' => 'REGISTERED',
                'chief_complaint' => trim((string) ($data['chief_complaint'] ?? '')) ?: null,
                'registered_by' => $userId,
            ]);

            if (!$visit->save()) {
                throw new ServiceException('Data kunjungan tidak valid', 422, $visit->errors);
            }

            $transaction->commit();
            $visit->refresh();
            return $visit;
        } catch (IntegrityException $exception) {
            $transaction->rollBack();
            throw new ServiceException('Pasien masih punya kunjungan aktif', 409);
        } catch (Throwable $exception) {
            $transaction->rollBack();
            throw $exception;
        }
    }

    public function updateStatus(Visit $visit, string $status): Visit
    {
        if (!in_array($status, Visit::STATUSES, true)) {
            throw new ServiceException('Status kunjungan tidak dikenal', 422);
        }

        if ($visit->status === $status) {
            return $visit;
        }

        if (in_array($visit->status, ['PAID', 'CANCELLED'], true)) {
            throw new ServiceException('Kunjungan yang sudah selesai tidak bisa diubah', 409);
        }

        $currentOrder = self::STATUS_ORDER[$visit->status];
        $targetOrder = self::STATUS_ORDER[$status];

        if ($status !== 'CANCELLED' && $targetOrder <= $currentOrder) {
            throw new ServiceException('Status kunjungan tidak bisa mundur', 409);
        }

        $visit->status = $status;

        if (!$visit->save()) {
            throw new ServiceException('Status kunjungan tidak valid', 422, $visit->errors);
        }

        return $visit;
    }

    private function nextQueueNumber(int $poliId, string $visitDate): int
    {
        return (int) Yii::$app->db->createCommand(
            'INSERT INTO queue_counters (poli_id, queue_date, last_number)
             VALUES (:poli_id, :queue_date, 1)
             ON CONFLICT (poli_id, queue_date)
             DO UPDATE SET last_number = queue_counters.last_number + 1
             RETURNING last_number'
        )->bindValues([
            ':poli_id' => $poliId,
            ':queue_date' => $visitDate,
        ])->queryScalar();
    }
}
