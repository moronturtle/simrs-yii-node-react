<?php

use yii\db\Migration;

class m251121_000002_create_visit_tables extends Migration
{
    public function safeUp(): void
    {
        $this->createTable('queue_counters', [
            'poli_id' => $this->bigInteger()->notNull(),
            'queue_date' => $this->date()->notNull(),
            'last_number' => $this->integer()->notNull()->defaultValue(0),
        ]);
        $this->addPrimaryKey('pk_queue_counters', 'queue_counters', ['poli_id', 'queue_date']);
        $this->addForeignKey('fk_queue_counter_poli', 'queue_counters', 'poli_id', 'polis', 'id', 'RESTRICT', 'CASCADE');

        $this->createTable('visits', [
            'id' => $this->bigPrimaryKey(),
            'visit_number' => $this->string(32)->notNull()->unique(),
            'patient_id' => $this->bigInteger()->notNull(),
            'poli_id' => $this->bigInteger()->notNull(),
            'doctor_id' => $this->bigInteger()->notNull(),
            'schedule_id' => $this->bigInteger(),
            'visit_date' => $this->date()->notNull()->defaultExpression('CURRENT_DATE'),
            'queue_number' => $this->integer()->notNull(),
            'status' => $this->string(32)->notNull()->defaultValue('REGISTERED'),
            'chief_complaint' => $this->text(),
            'registered_by' => $this->bigInteger()->notNull(),
            'created_at' => $this->dateTime()->notNull()->defaultExpression('CURRENT_TIMESTAMP'),
            'updated_at' => $this->dateTime()->notNull()->defaultExpression('CURRENT_TIMESTAMP'),
        ]);
        $this->addForeignKey('fk_visit_patient', 'visits', 'patient_id', 'patients', 'id', 'RESTRICT', 'CASCADE');
        $this->addForeignKey('fk_visit_poli', 'visits', 'poli_id', 'polis', 'id', 'RESTRICT', 'CASCADE');
        $this->addForeignKey('fk_visit_doctor', 'visits', 'doctor_id', 'doctors', 'id', 'RESTRICT', 'CASCADE');
        $this->addForeignKey('fk_visit_schedule', 'visits', 'schedule_id', 'doctor_schedules', 'id', 'SET NULL', 'CASCADE');
        $this->addForeignKey('fk_visit_registered_by', 'visits', 'registered_by', 'users', 'id', 'RESTRICT', 'CASCADE');
        $this->execute("ALTER TABLE visits ADD CONSTRAINT visits_status_check CHECK (status IN ('REGISTERED','IN_EXAM','PHARMACY','LAB','BILLING','PAID','CANCELLED'))");
        $this->createIndex('uq_visit_queue', 'visits', ['poli_id', 'visit_date', 'queue_number'], true);
        $this->createIndex('idx_visit_status_date', 'visits', ['status', 'visit_date']);
        $this->execute("CREATE UNIQUE INDEX uq_active_visit_patient ON visits (patient_id) WHERE status IN ('REGISTERED','IN_EXAM','PHARMACY','LAB','BILLING')");

        $this->createTable('examinations', [
            'id' => $this->bigPrimaryKey(),
            'visit_id' => $this->bigInteger()->notNull()->unique(),
            'anamnesis' => $this->text(),
            'vital_signs' => $this->json(),
            'physical_exam' => $this->text(),
            'primary_diagnosis' => $this->string()->notNull(),
            'secondary_diagnosis' => $this->text(),
            'examined_by' => $this->bigInteger()->notNull(),
            'examined_at' => $this->dateTime()->notNull()->defaultExpression('CURRENT_TIMESTAMP'),
            'created_at' => $this->dateTime()->notNull()->defaultExpression('CURRENT_TIMESTAMP'),
            'updated_at' => $this->dateTime()->notNull()->defaultExpression('CURRENT_TIMESTAMP'),
        ]);
        $this->addForeignKey('fk_exam_visit', 'examinations', 'visit_id', 'visits', 'id', 'CASCADE', 'CASCADE');
        $this->addForeignKey('fk_exam_doctor', 'examinations', 'examined_by', 'users', 'id', 'RESTRICT', 'CASCADE');

        $this->createTable('visit_actions', [
            'id' => $this->bigPrimaryKey(),
            'visit_id' => $this->bigInteger()->notNull(),
            'medical_action_id' => $this->bigInteger()->notNull(),
            'quantity' => $this->integer()->notNull()->defaultValue(1),
            'price' => $this->decimal(14, 2)->notNull(),
            'notes' => $this->text(),
            'created_at' => $this->dateTime()->notNull()->defaultExpression('CURRENT_TIMESTAMP'),
            'updated_at' => $this->dateTime()->notNull()->defaultExpression('CURRENT_TIMESTAMP'),
        ]);
        $this->addForeignKey('fk_visit_action_visit', 'visit_actions', 'visit_id', 'visits', 'id', 'CASCADE', 'CASCADE');
        $this->addForeignKey('fk_visit_action_master', 'visit_actions', 'medical_action_id', 'medical_actions', 'id', 'RESTRICT', 'CASCADE');
        $this->execute('ALTER TABLE visit_actions ADD CONSTRAINT visit_actions_quantity_check CHECK (quantity > 0)');
        $this->execute('ALTER TABLE visit_actions ADD CONSTRAINT visit_actions_price_check CHECK (price >= 0)');
        $this->createIndex('idx_visit_actions_visit', 'visit_actions', 'visit_id');
    }

    public function safeDown(): void
    {
        $this->dropTable('visit_actions');
        $this->dropTable('examinations');
        $this->dropTable('visits');
        $this->dropTable('queue_counters');
    }
}
