<?php

declare(strict_types=1);

namespace app\services;

use Yii;
use app\models\Invoice;
use app\models\Payment;
use app\models\Visit;
use Throwable;
use yii\db\IntegrityException;

class PaymentService
{
    /**
     * @return array{0: Payment, 1: bool} payment dan flag apakah row baru dibuat
     */
    public function pay(Invoice $invoice, array $data, int $userId): array
    {
        $idempotencyKey = trim((string) ($data['idempotency_key'] ?? ''));
        if ($idempotencyKey === '') {
            throw new ServiceException('idempotency_key wajib diisi', 422);
        }

        $existing = Payment::findOne(['idempotency_key' => $idempotencyKey]);
        if ($existing) {
            if ((int) $existing->invoice_id !== (int) $invoice->id) {
                throw new ServiceException('idempotency_key sudah dipakai invoice lain', 409);
            }

            return [$existing, false];
        }

        $method = strtoupper(trim((string) ($data['method'] ?? 'CASH')));
        $amount = round((float) ($data['amount'] ?? 0), 2);

        if (!in_array($method, Payment::METHODS, true)) {
            throw new ServiceException('Metode pembayaran tidak dikenal', 422);
        }

        if ($amount <= 0) {
            throw new ServiceException('Nominal pembayaran harus lebih dari 0', 422);
        }

        $transaction = Yii::$app->db->beginTransaction();

        try {
            $locked = Yii::$app->db->createCommand(
                'SELECT id, status, total_amount, paid_amount, visit_id
                 FROM invoices
                 WHERE id = :id
                 FOR UPDATE'
            )->bindValue(':id', $invoice->id)->queryOne();

            if (!$locked) {
                throw new ServiceException('Invoice tidak ditemukan', 404);
            }

            $existingInTransaction = Payment::findOne(['idempotency_key' => $idempotencyKey]);
            if ($existingInTransaction) {
                if ((int) $existingInTransaction->invoice_id !== (int) $invoice->id) {
                    $transaction->rollBack();
                    throw new ServiceException('idempotency_key sudah dipakai invoice lain', 409);
                }

                $transaction->commit();
                return [$existingInTransaction, false];
            }

            if (in_array($locked['status'], ['PAID', 'CANCELLED'], true)) {
                throw new ServiceException('Invoice sudah selesai', 409);
            }

            $paidAmount = (float) $locked['paid_amount'];
            $totalAmount = (float) $locked['total_amount'];
            $remaining = round($totalAmount - $paidAmount, 2);

            if ($amount > $remaining) {
                throw new ServiceException('Nominal pembayaran melebihi sisa tagihan', 422);
            }

            $payment = new Payment([
                'invoice_id' => $invoice->id,
                'payment_number' => sprintf('PAY%s-%04d-%s', date('Ymd'), $invoice->id, substr(uniqid(), -6)),
                'amount' => $amount,
                'method' => $method,
                'idempotency_key' => $idempotencyKey,
                'paid_by' => $userId,
            ]);

            if (!$payment->save()) {
                throw new ServiceException('Pembayaran tidak valid', 422, $payment->errors);
            }

            $invoice->total_amount = $totalAmount;
            $invoice->paid_amount = round($paidAmount + $amount, 2);
            (new BillingService())->recalculate($invoice);

            if ($invoice->status === 'PAID') {
                $visit = Visit::findOne((int) $locked['visit_id']);
                if ($visit) {
                    $visit->status = 'PAID';
                    if (!$visit->save()) {
                        throw new ServiceException('Status kunjungan tidak bisa diubah', 422, $visit->errors);
                    }
                }
            }

            $transaction->commit();
            return [$payment, true];
        } catch (IntegrityException $exception) {
            $transaction->rollBack();
            $existing = Payment::findOne(['idempotency_key' => $idempotencyKey]);

            if ($existing && (int) $existing->invoice_id === (int) $invoice->id) {
                return [$existing, false];
            }

            throw new ServiceException('Idempotency key sudah dipakai', 409);
        } catch (Throwable $exception) {
            $transaction->rollBack();
            throw $exception;
        }
    }
}
