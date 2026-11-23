<?php

declare(strict_types=1);

namespace app\services;

use Yii;

class AuditService
{
    public function record(
        string $tableName,
        int $recordId,
        string $action,
        ?array $oldValues,
        ?array $newValues,
        ?int $userId,
    ): void {
        Yii::$app->db->createCommand()->insert('audit_logs', [
            'table_name' => $tableName,
            'record_id' => $recordId,
            'action' => $action,
            'old_values' => $oldValues === null ? null : json_encode($oldValues),
            'new_values' => $newValues === null ? null : json_encode($newValues),
            'changed_by' => $userId,
        ])->execute();
    }
}
