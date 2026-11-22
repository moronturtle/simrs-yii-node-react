<?php

declare(strict_types=1);

namespace app\controllers\api;

use Yii;
use app\filters\RoleAccessFilter;
use app\models\Payment;
use yii\filters\auth\HttpBearerAuth;
use yii\filters\VerbFilter;

class PaymentController extends BaseApiController
{
    public function behaviors(): array
    {
        return array_merge(parent::behaviors(), [
            'authenticator' => ['class' => HttpBearerAuth::class],
            'roles' => [
                'class' => RoleAccessFilter::class,
                'readRoles' => ['ADMIN', 'PENDAFTARAN', 'DOKTER', 'FARMASI', 'LAB', 'KASIR'],
            ],
            'verbs' => [
                'class' => VerbFilter::class,
                'actions' => [
                    'receipt' => ['get'],
                    'options' => ['options'],
                ],
            ],
        ]);
    }

    public function actionReceipt(int $id): array
    {
        $payment = Payment::find()
            ->with(['invoice.visit.patient', 'invoice.items', 'paidBy'])
            ->where(['id' => $id])
            ->one();

        if (!$payment) {
            return $this->failure('NOT_FOUND', 'Pembayaran tidak ditemukan', 404);
        }

        $invoice = $payment->invoice;
        $visit = $invoice?->visit;

        return $this->success([
            'payment' => array_merge($payment->toArray(), [
                'paid_by_user' => $payment->paidBy?->toArray(),
            ]),
            'invoice' => $invoice ? array_merge($invoice->toArray(), [
                'items' => array_map(fn ($item) => $item->toArray(), $invoice->items),
            ]) : null,
            'visit' => $visit ? array_merge($visit->toArray(), [
                'patient' => $visit->patient?->toArray(),
            ]) : null,
        ]);
    }
}
