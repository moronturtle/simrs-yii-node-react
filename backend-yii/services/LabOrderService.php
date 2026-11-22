<?php

declare(strict_types=1);

namespace app\services;

use Yii;
use app\models\LabOrder;
use app\models\Visit;
use Throwable;

class LabOrderService
{
    public function create(Visit $visit, array $data, int $userId): LabOrder
    {
        if (!in_array($visit->status, ['IN_EXAM', 'LAB'], true)) {
            throw new ServiceException('Order lab hanya bisa dibuat saat pemeriksaan berjalan', 409);
        }

        $testName = trim((string) ($data['test_name'] ?? ''));
        if ($testName === '') {
            throw new ServiceException('Nama pemeriksaan lab wajib diisi', 422);
        }

        $price = (float) ($data['price'] ?? 0);
        if ($price < 0) {
            throw new ServiceException('Biaya lab tidak boleh negatif', 422);
        }

        $transaction = Yii::$app->db->beginTransaction();

        try {
            $order = new LabOrder([
                'visit_id' => $visit->id,
                'order_number' => sprintf('LAB%s-%d-%s', date('Ymd'), $visit->id, substr(uniqid(), -6)),
                'test_name' => $testName,
                'status' => 'REQUESTED',
                'price' => $price,
                'requested_by' => $userId,
            ]);

            if (!$order->save()) {
                throw new ServiceException('Order lab tidak valid', 422, $order->errors);
            }

            if ($visit->status === 'IN_EXAM') {
                $visit->status = 'LAB';
                if (!$visit->save()) {
                    throw new ServiceException('Status kunjungan tidak bisa diubah', 422, $visit->errors);
                }
            }

            (new BillingService())->addLabItem($visit, $order);
            $transaction->commit();
            return $order;
        } catch (Throwable $exception) {
            $transaction->rollBack();
            throw $exception;
        }
    }
}
