<?php

declare(strict_types=1);

namespace app\services;

use app\models\Invoice;
use app\models\InvoiceItem;
use app\models\LabOrder;
use app\models\MedicalAction;
use app\models\Prescription;
use app\models\Visit;
use app\models\VisitAction;

class BillingService
{
    private const REGISTRATION_FEE = 50000;

    public function ensureInvoice(Visit $visit, ?int $actorId = null): Invoice
    {
        $invoice = Invoice::findOne(['visit_id' => $visit->id]);

        if (!$invoice) {
            $invoice = new Invoice([
                'visit_id' => $visit->id,
                'invoice_number' => sprintf('INV%s-%04d', date('Ymd'), $visit->id),
                'status' => 'UNPAID',
                'total_amount' => 0,
                'paid_amount' => 0,
            ]);

            if (!$invoice->save()) {
                throw new ServiceException('Invoice tidak valid', 422, $invoice->errors);
            }

            (new AuditService())->record('invoices', (int) $invoice->id, 'INSERT', null, $invoice->getAttributes(), $actorId);
            $this->addItem($invoice, 'REGISTRATION', $visit->id, 'Biaya pendaftaran', 1, self::REGISTRATION_FEE, $actorId);
        }

        return $invoice;
    }

    public function addActionItem(Visit $visit, VisitAction $visitAction, MedicalAction $action, int $actorId): Invoice
    {
        $invoice = $this->ensureInvoice($visit, $actorId);
        $this->assertEditable($invoice);

        $this->addItem(
            $invoice,
            'ACTION',
            $visitAction->id,
            "Tindakan: {$action->name}",
            $visitAction->quantity,
            $visitAction->price,
            $actorId,
        );

        $this->recalculate($invoice, $actorId);
        return $invoice;
    }

    public function addLabItem(Visit $visit, LabOrder $order, int $actorId): Invoice
    {
        $invoice = $this->ensureInvoice($visit, $actorId);
        $this->assertEditable($invoice);

        $this->addItem(
            $invoice,
            'LAB',
            $order->id,
            "Lab: {$order->test_name}",
            1,
            (float) $order->price,
            $actorId,
        );

        $this->recalculate($invoice, $actorId);
        return $invoice;
    }

    public function addMedicineItems(Visit $visit, Prescription $prescription, int $actorId): Invoice
    {
        $invoice = $this->ensureInvoice($visit, $actorId);
        $this->assertEditable($invoice);

        foreach ($prescription->items as $item) {
            $this->addItem(
                $invoice,
                'MEDICINE',
                $item->id,
                "Obat: {$item->medicine->name}",
                $item->quantity,
                (float) $item->price,
                $actorId,
            );
        }

        $this->recalculate($invoice, $actorId);
        return $invoice;
    }

    public function recalculate(Invoice $invoice, ?int $actorId = null): void
    {
        $oldValues = $invoice->getAttributes();
        $invoice->total_amount = (float) InvoiceItem::find()
            ->where(['invoice_id' => $invoice->id])
            ->sum('total_price');

        $invoice->status = match (true) {
            (float) $invoice->paid_amount <= 0 => 'UNPAID',
            (float) $invoice->paid_amount < (float) $invoice->total_amount => 'PARTIAL',
            default => 'PAID',
        };

        if (!$invoice->save()) {
            throw new ServiceException('Total invoice tidak valid', 422, $invoice->errors);
        }

        (new AuditService())->record('invoices', (int) $invoice->id, 'UPDATE', $oldValues, $invoice->getAttributes(), $actorId);
    }

    private function addItem(
        Invoice $invoice,
        string $type,
        ?int $referenceId,
        string $description,
        int $quantity,
        float $unitPrice,
        ?int $actorId = null,
    ): void {
        $item = new InvoiceItem([
            'invoice_id' => $invoice->id,
            'item_type' => $type,
            'reference_id' => $referenceId,
            'description' => $description,
            'quantity' => $quantity,
            'unit_price' => $unitPrice,
            'total_price' => $quantity * $unitPrice,
        ]);

        if (!$item->save()) {
            throw new ServiceException('Item invoice tidak valid', 422, $item->errors);
        }

        (new AuditService())->record('invoice_items', (int) $item->id, 'INSERT', null, $item->getAttributes(), $actorId);
    }

    private function assertEditable(Invoice $invoice): void
    {
        if (in_array($invoice->status, ['PAID', 'CANCELLED'], true)) {
            throw new ServiceException('Invoice yang sudah selesai tidak bisa diubah', 409);
        }
    }
}
