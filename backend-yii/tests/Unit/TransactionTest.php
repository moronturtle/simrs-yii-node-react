<?php

declare(strict_types=1);

namespace app\tests\Unit;

use app\models\DoctorSchedule;
use app\models\Invoice;
use app\models\MedicalAction;
use app\models\Medicine;
use app\models\Patient;
use app\models\Payment;
use app\models\User;
use app\services\PaymentService;
use app\services\PrescriptionService;
use app\services\ServiceException;
use app\services\VisitActionService;
use app\services\VisitService;
use Yii;
use yii\db\Transaction;

final class TransactionTest extends \Codeception\Test\Unit
{
    private Transaction $tx;
    private Patient $patient;
    private DoctorSchedule $schedule;
    private int $userId;

    protected function _before(): void
    {
        $this->tx = Yii::$app->db->beginTransaction();

        $this->patient = new Patient([
            'name' => 'Pasien Test Transaksi',
            'medical_record_no' => 'RM-TST-' . uniqid(),
        ]);
        $this->patient->save(false);

        $this->schedule = DoctorSchedule::find()->where(['is_active' => true])->one();
        $this->userId = (int) User::find()->select('id')->scalar();
    }

    protected function _after(): void
    {
        $this->tx->rollBack();
    }

    public function testQueueNumberIncrementsPerPoliPerDay(): void
    {
        $service = new VisitService();
        $first = $service->create($this->visitData(), $this->userId);

        $other = new Patient([
            'name' => 'Pasien Test Kedua',
            'medical_record_no' => 'RM-TST-' . uniqid(),
        ]);
        $other->save(false);
        $second = $service->create([
            'patient_id' => $other->id,
            'schedule_id' => $this->schedule->id,
        ], $this->userId);

        verify($first->status)->equals('REGISTERED');
        verify($second->queue_number)->equals($first->queue_number + 1);
    }

    public function testDuplicateActiveVisitIsRejected(): void
    {
        $service = new VisitService();
        $service->create($this->visitData(), $this->userId);

        $this->expectException(ServiceException::class);
        $service->create($this->visitData(), $this->userId);
    }

    public function testPaymentWithSameKeyIsNotDuplicated(): void
    {
        $visit = (new VisitService())->create($this->visitData(), $this->userId);
        $action = MedicalAction::find()->where(['is_active' => true])->one();

        (new VisitActionService())->add($visit, ['medical_action_id' => $action->id], $this->userId);
        $invoice = Invoice::findOne(['visit_id' => $visit->id]);

        $service = new PaymentService();
        $body = [
            'amount' => (float) $invoice->total_amount,
            'method' => 'CASH',
            'idempotency_key' => 'test-pay-key-001',
        ];

        [$first, $createdFirst] = $service->pay($invoice, $body, $this->userId);
        [$second, $createdSecond] = $service->pay($invoice, $body, $this->userId);

        verify($createdFirst)->true();
        verify($createdSecond)->false();
        verify($second->id)->equals($first->id);
        verify(Payment::find()->where(['invoice_id' => $invoice->id])->count())->equals(1);
        $invoice->refresh();
        $visit->refresh();
        verify($invoice->status)->equals('PAID');
        verify($visit->status)->equals('PAID');
    }

    public function testDispenseFailsWhenStockRunsOut(): void
    {
        $visit = (new VisitService())->create($this->visitData(), $this->userId);
        $visit->status = 'IN_EXAM';
        $visit->save(false);

        $medicine = Medicine::find()->where(['is_active' => true])->andWhere(['>', 'stock', 0])->one();
        $prescription = (new PrescriptionService())->create($visit, [
            'items' => [
                [
                    'medicine_id' => $medicine->id,
                    'quantity' => (int) $medicine->stock,
                    'dose' => '1 tablet',
                    'frequency' => '3x sehari',
                ],
            ],
        ], $this->userId);

        $medicine->stock = 0;
        $medicine->save(false);

        try {
            (new PrescriptionService())->dispense($prescription, $this->userId);
            $this->fail('Dispense seharusnya gagal saat stok habis');
        } catch (ServiceException $exception) {
            verify($exception->statusCode)->equals(409);
            $prescription->refresh();
            verify($prescription->status)->equals('PRESCRIBED');
        }
    }

    private function visitData(): array
    {
        return [
            'patient_id' => $this->patient->id,
            'schedule_id' => $this->schedule->id,
            'chief_complaint' => 'keluhan test',
        ];
    }
}
