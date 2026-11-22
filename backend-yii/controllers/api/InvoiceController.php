<?php

declare(strict_types=1);

namespace app\controllers\api;

use Yii;
use app\filters\RoleAccessFilter;
use app\models\Invoice;
use app\models\Payment;
use app\services\PaymentService;
use app\services\ServiceException;
use yii\data\ActiveDataProvider;
use yii\filters\auth\HttpBearerAuth;
use yii\filters\VerbFilter;

class InvoiceController extends BaseApiController
{
    public function behaviors(): array
    {
        return array_merge(parent::behaviors(), [
            'authenticator' => ['class' => HttpBearerAuth::class],
            'roles' => [
                'class' => RoleAccessFilter::class,
                'readRoles' => ['ADMIN', 'PENDAFTARAN', 'DOKTER', 'FARMASI', 'LAB', 'KASIR'],
                'actionRoles' => ['pay' => ['KASIR']],
            ],
            'verbs' => [
                'class' => VerbFilter::class,
                'actions' => [
                    'index' => ['get'],
                    'view' => ['get'],
                    'by-visit' => ['get'],
                    'pay' => ['post'],
                    'options' => ['options'],
                ],
            ],
        ]);
    }

    public function actionIndex(): array
    {
        $request = Yii::$app->request;
        $query = Invoice::find()
            ->with(['visit.patient', 'payments.paidBy'])
            ->alias('invoice')
            ->joinWith(['visit.patient'])
            ->andFilterWhere(['invoice.status' => $request->get('status')]);

        $search = trim((string) $request->get('q', ''));
        if ($search !== '') {
            $query->andWhere([
                'or',
                ['ilike', 'invoice.invoice_number', $search],
                ['ilike', 'visits.visit_number', $search],
                ['ilike', 'patients.name', $search],
            ]);
        }

        $page = max(1, (int) $request->get('page', 1));
        $pageSize = min(100, max(1, (int) $request->get('per-page', 20)));
        $provider = new ActiveDataProvider([
            'query' => $query,
            'pagination' => ['page' => $page - 1, 'pageSize' => $pageSize],
            'sort' => ['defaultOrder' => ['id' => SORT_DESC]],
        ]);

        return $this->success([
            'items' => array_map(fn (Invoice $invoice) => $this->serialize($invoice), $provider->getModels()),
            'pagination' => [
                'page' => $page,
                'page_size' => $pageSize,
                'total' => $provider->getTotalCount(),
            ],
        ]);
    }

    public function actionView(int $id): array
    {
        return $this->success($this->serialize($this->findModel($id)));
    }

    public function actionByVisit(int $visit_id): array
    {
        $invoice = Invoice::find()
            ->with(['visit.patient', 'items', 'payments.paidBy'])
            ->where(['visit_id' => $visit_id])
            ->one();

        if (!$invoice) {
            return $this->failure('NOT_FOUND', 'Invoice belum tersedia', 404);
        }

        return $this->success($this->serialize($invoice));
    }

    public function actionPay(int $id): array
    {
        try {
            [$payment, $created] = (new PaymentService())->pay(
                $this->findModel($id),
                Yii::$app->request->bodyParams,
                (int) Yii::$app->user->id,
            );
        } catch (ServiceException $exception) {
            return $this->failure('PAYMENT_ERROR', $exception->getMessage(), $exception->statusCode, $exception->details);
        }

        Yii::$app->response->statusCode = $created ? 201 : 200;
        return $this->success($this->serializePayment($payment));
    }

    private function serialize(Invoice $invoice): array
    {
        return array_merge($invoice->toArray(), [
            'visit' => $invoice->visit ? array_merge($invoice->visit->toArray(), [
                'patient' => $invoice->visit->patient?->toArray(),
            ]) : null,
            'items' => array_map(fn ($item) => $item->toArray(), $invoice->items),
            'payments' => array_map(fn (Payment $payment) => $this->serializePayment($payment), $invoice->payments),
        ]);
    }

    private function serializePayment(Payment $payment): array
    {
        return array_merge($payment->toArray(), [
            'paid_by_user' => $payment->paidBy?->toArray(),
        ]);
    }

    private function findModel(int $id): Invoice
    {
        $model = Invoice::find()
            ->with(['visit.patient', 'items', 'payments.paidBy'])
            ->where(['invoice.id' => $id])
            ->alias('invoice')
            ->one();

        if (!$model) {
            Yii::$app->response->statusCode = 404;
            Yii::$app->response->data = [
                'success' => false,
                'data' => null,
                'error' => ['code' => 'NOT_FOUND', 'message' => 'Invoice tidak ditemukan'],
            ];
            Yii::$app->response->send();
            Yii::$app->end();
        }

        return $model;
    }
}
