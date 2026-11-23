<?php

declare(strict_types=1);

namespace app\services;

use Yii;
use app\models\MedicalAction;
use app\models\Visit;
use app\models\VisitAction;
use Throwable;

class VisitActionService
{
    public function add(Visit $visit, array $data, int $userId): VisitAction
    {
        if (in_array($visit->status, ['PAID', 'CANCELLED'], true)) {
            throw new ServiceException('Kunjungan selesai tidak bisa ditambah tindakan', 409);
        }

        $action = MedicalAction::findOne([
            'id' => (int) ($data['medical_action_id'] ?? 0),
            'is_active' => true,
        ]);

        if (!$action) {
            throw new ServiceException('Tindakan tidak ditemukan atau tidak aktif', 422);
        }

        $transaction = Yii::$app->db->beginTransaction();

        try {
            $visitAction = new VisitAction([
                'visit_id' => $visit->id,
                'medical_action_id' => $action->id,
                'quantity' => max(1, (int) ($data['quantity'] ?? 1)),
                'price' => (float) $action->price,
                'notes' => trim((string) ($data['notes'] ?? '')) ?: null,
            ]);

            if (!$visitAction->save()) {
                throw new ServiceException('Data tindakan tidak valid', 422, $visitAction->errors);
            }

            (new AuditService())->record('visit_actions', (int) $visitAction->id, 'INSERT', null, $visitAction->getAttributes(), $userId);
            (new BillingService())->addActionItem($visit, $visitAction, $action, $userId);
            $transaction->commit();
            return $visitAction;
        } catch (Throwable $exception) {
            $transaction->rollBack();
            throw $exception;
        }
    }
}
