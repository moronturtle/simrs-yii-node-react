<?php

declare(strict_types=1);

namespace app\services;

use Yii;
use app\models\Medicine;
use app\models\Prescription;
use app\models\PrescriptionItem;
use app\models\Visit;
use Throwable;

class PrescriptionService
{
    public function create(Visit $visit, array $data, int $userId): Prescription
    {
        if (in_array($visit->status, ['REGISTERED', 'PAID', 'CANCELLED'], true)) {
            throw new ServiceException('Resep hanya bisa dibuat setelah pemeriksaan dimulai', 409);
        }

        $items = $data['items'] ?? null;
        if (!is_array($items) || $items === []) {
            throw new ServiceException('Resep wajib punya minimal satu item obat', 422);
        }

        $transaction = Yii::$app->db->beginTransaction();

        try {
            $prescription = new Prescription([
                'visit_id' => $visit->id,
                'doctor_id' => $userId,
                'status' => 'PRESCRIBED',
                'notes' => trim((string) ($data['notes'] ?? '')) ?: null,
            ]);

            if (!$prescription->save()) {
                throw new ServiceException('Data resep tidak valid', 422, $prescription->errors);
            }

            foreach ($items as $item) {
                $this->createItem($prescription, $item);
            }

            if (in_array($visit->status, ['IN_EXAM', 'LAB'], true)) {
                $visit->status = 'PHARMACY';
                if (!$visit->save()) {
                    throw new ServiceException('Status kunjungan tidak bisa diubah', 422, $visit->errors);
                }
            }

            $transaction->commit();
            return $prescription;
        } catch (Throwable $exception) {
            $transaction->rollBack();
            throw $exception;
        }
    }

    public function dispense(Prescription $prescription, int $userId): Prescription
    {
        $transaction = Yii::$app->db->beginTransaction();

        try {
            $status = Yii::$app->db->createCommand(
                'SELECT status FROM prescriptions WHERE id = :id FOR UPDATE'
            )->bindValue(':id', $prescription->id)->queryScalar();

            if ($status === false) {
                throw new ServiceException('Resep tidak ditemukan', 404);
            }

            if ($status !== 'PRESCRIBED') {
                throw new ServiceException('Resep sudah diproses atau dibatalkan', 409);
            }

            $prescription->populateRelation('items', $prescription->getItems()->with('medicine')->all());
            $visit = $prescription->visit;

            foreach ($prescription->items as $item) {
                $this->reduceStock($item);
                $item->dispensed_at = date('Y-m-d H:i:s');
                $item->dispensed_by = $userId;

                if (!$item->save()) {
                    throw new ServiceException('Data penyerahan obat tidak valid', 422, $item->errors);
                }
            }

            $prescription->status = 'DISPENSED';
            if (!$prescription->save()) {
                throw new ServiceException('Status resep tidak valid', 422, $prescription->errors);
            }

            if (in_array($visit->status, ['IN_EXAM', 'PHARMACY', 'LAB'], true)) {
                $visit->status = 'BILLING';
                if (!$visit->save()) {
                    throw new ServiceException('Status kunjungan tidak bisa diubah', 422, $visit->errors);
                }
            }

            (new AuditService())->record('prescriptions', (int) $prescription->id, 'UPDATE', ['status' => $status], $prescription->getAttributes(), $userId);
            (new BillingService())->addMedicineItems($visit, $prescription, $userId);
            $transaction->commit();
            return $prescription;
        } catch (Throwable $exception) {
            $transaction->rollBack();
            throw $exception;
        }
    }

    private function createItem(Prescription $prescription, array $data): void
    {
        $medicine = Medicine::findOne([
            'id' => (int) ($data['medicine_id'] ?? 0),
            'is_active' => true,
        ]);

        if (!$medicine) {
            throw new ServiceException('Obat tidak ditemukan atau tidak aktif', 422);
        }

        $quantity = (int) ($data['quantity'] ?? 0);
        if ($quantity < 1) {
            throw new ServiceException('Jumlah obat minimal 1', 422);
        }

        if ((int) $medicine->stock < $quantity) {
            throw new ServiceException("Stok {$medicine->name} tidak cukup", 409);
        }

        $item = new PrescriptionItem([
            'prescription_id' => $prescription->id,
            'medicine_id' => $medicine->id,
            'dose' => trim((string) ($data['dose'] ?? '')),
            'frequency' => trim((string) ($data['frequency'] ?? '')),
            'quantity' => $quantity,
            'price' => (float) $medicine->price,
        ]);

        if (!$item->save()) {
            throw new ServiceException('Item resep tidak valid', 422, $item->errors);
        }
    }

    private function reduceStock(PrescriptionItem $item): void
    {
        $affected = Yii::$app->db->createCommand(
            'UPDATE medicines
             SET stock = stock - :qty, updated_at = CURRENT_TIMESTAMP
             WHERE id = :id AND stock >= :qty'
        )->bindValues([
            ':id' => $item->medicine_id,
            ':qty' => $item->quantity,
        ])->execute();

        if ($affected === 0) {
            throw new ServiceException("Stok {$item->medicine->name} tidak cukup", 409);
        }
    }
}
