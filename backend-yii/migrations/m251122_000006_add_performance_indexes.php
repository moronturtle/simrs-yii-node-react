<?php

use yii\db\Migration;

class m251122_000006_add_performance_indexes extends Migration
{
    public function safeUp(): void
    {
        $this->execute('CREATE EXTENSION IF NOT EXISTS pg_trgm');
        $this->execute('CREATE INDEX idx_patients_name_trgm ON patients USING gin (name gin_trgm_ops)');
        $this->execute('CREATE INDEX idx_doctors_name_trgm ON doctors USING gin (name gin_trgm_ops)');
        $this->execute('CREATE INDEX idx_polis_name_trgm ON polis USING gin (name gin_trgm_ops)');
        $this->execute('CREATE INDEX idx_medical_actions_name_trgm ON medical_actions USING gin (name gin_trgm_ops)');
        $this->execute('CREATE INDEX idx_medicines_name_trgm ON medicines USING gin (name gin_trgm_ops)');

        $this->createIndex('idx_schedules_poli_day', 'doctor_schedules', ['poli_id', 'day_of_week']);
        $this->createIndex('idx_visits_patient_date', 'visits', ['patient_id', 'visit_date']);
        $this->createIndex('idx_visits_doctor_date', 'visits', ['doctor_id', 'visit_date']);
        $this->createIndex('idx_prescriptions_doctor', 'prescriptions', 'doctor_id');
        $this->createIndex('idx_prescription_items_medicine', 'prescription_items', 'medicine_id');
        $this->createIndex('idx_lab_orders_requested_by', 'lab_orders', 'requested_by');
        $this->createIndex('idx_payments_paid_by', 'payments', 'paid_by');
    }

    public function safeDown(): void
    {
        $this->dropIndex('idx_payments_paid_by', 'payments');
        $this->dropIndex('idx_lab_orders_requested_by', 'lab_orders');
        $this->dropIndex('idx_prescription_items_medicine', 'prescription_items');
        $this->dropIndex('idx_prescriptions_doctor', 'prescriptions');
        $this->dropIndex('idx_visits_doctor_date', 'visits');
        $this->dropIndex('idx_visits_patient_date', 'visits');
        $this->dropIndex('idx_schedules_poli_day', 'doctor_schedules');

        $this->execute('DROP INDEX IF EXISTS idx_medicines_name_trgm');
        $this->execute('DROP INDEX IF EXISTS idx_medical_actions_name_trgm');
        $this->execute('DROP INDEX IF EXISTS idx_polis_name_trgm');
        $this->execute('DROP INDEX IF EXISTS idx_doctors_name_trgm');
        $this->execute('DROP INDEX IF EXISTS idx_patients_name_trgm');
    }
}
