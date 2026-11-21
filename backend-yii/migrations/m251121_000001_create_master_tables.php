<?php

use yii\db\Migration;

class m251121_000001_create_master_tables extends Migration
{
    public function safeUp(): void
    {
        $this->createTable('users', [
            'id' => $this->bigPrimaryKey(),
            'username' => $this->string(64)->notNull()->unique(),
            'password_hash' => $this->string()->notNull(),
            'name' => $this->string(128)->notNull(),
            'role' => $this->string(32)->notNull(),
            'is_active' => $this->boolean()->notNull()->defaultValue(true),
            'created_at' => $this->dateTime()->notNull()->defaultExpression('CURRENT_TIMESTAMP'),
            'updated_at' => $this->dateTime()->notNull()->defaultExpression('CURRENT_TIMESTAMP'),
        ]);
        $this->execute("ALTER TABLE users ADD CONSTRAINT users_role_check CHECK (role IN ('ADMIN','PENDAFTARAN','DOKTER','FARMASI','LAB','KASIR'))");

        $this->createTable('patients', [
            'id' => $this->bigPrimaryKey(),
            'medical_record_no' => $this->string(32)->notNull()->unique(),
            'nik' => $this->string(32)->unique(),
            'name' => $this->string(128)->notNull(),
            'date_of_birth' => $this->date(),
            'gender' => $this->string(16),
            'phone' => $this->string(32),
            'address' => $this->text(),
            'created_at' => $this->dateTime()->notNull()->defaultExpression('CURRENT_TIMESTAMP'),
            'updated_at' => $this->dateTime()->notNull()->defaultExpression('CURRENT_TIMESTAMP'),
        ]);
        $this->execute("ALTER TABLE patients ADD CONSTRAINT patients_gender_check CHECK (gender IN ('L','P') OR gender IS NULL)");

        $this->createTable('doctors', [
            'id' => $this->bigPrimaryKey(),
            'name' => $this->string(128)->notNull(),
            'specialization' => $this->string(128),
            'phone' => $this->string(32),
            'is_active' => $this->boolean()->notNull()->defaultValue(true),
            'created_at' => $this->dateTime()->notNull()->defaultExpression('CURRENT_TIMESTAMP'),
            'updated_at' => $this->dateTime()->notNull()->defaultExpression('CURRENT_TIMESTAMP'),
        ]);

        $this->createTable('polis', [
            'id' => $this->bigPrimaryKey(),
            'code' => $this->string(32)->notNull()->unique(),
            'name' => $this->string(128)->notNull(),
            'is_active' => $this->boolean()->notNull()->defaultValue(true),
            'created_at' => $this->dateTime()->notNull()->defaultExpression('CURRENT_TIMESTAMP'),
            'updated_at' => $this->dateTime()->notNull()->defaultExpression('CURRENT_TIMESTAMP'),
        ]);

        $this->createTable('doctor_schedules', [
            'id' => $this->bigPrimaryKey(),
            'doctor_id' => $this->bigInteger()->notNull(),
            'poli_id' => $this->bigInteger()->notNull(),
            'day_of_week' => $this->smallInteger()->notNull(),
            'start_time' => $this->time()->notNull(),
            'end_time' => $this->time()->notNull(),
            'is_active' => $this->boolean()->notNull()->defaultValue(true),
            'created_at' => $this->dateTime()->notNull()->defaultExpression('CURRENT_TIMESTAMP'),
            'updated_at' => $this->dateTime()->notNull()->defaultExpression('CURRENT_TIMESTAMP'),
        ]);
        $this->addForeignKey('fk_schedule_doctor', 'doctor_schedules', 'doctor_id', 'doctors', 'id', 'RESTRICT', 'CASCADE');
        $this->addForeignKey('fk_schedule_poli', 'doctor_schedules', 'poli_id', 'polis', 'id', 'RESTRICT', 'CASCADE');
        $this->execute('ALTER TABLE doctor_schedules ADD CONSTRAINT doctor_schedules_day_check CHECK (day_of_week BETWEEN 0 AND 6)');
        $this->execute('ALTER TABLE doctor_schedules ADD CONSTRAINT doctor_schedules_time_check CHECK (start_time < end_time)');
        $this->createIndex('uq_doctor_schedule', 'doctor_schedules', ['doctor_id', 'poli_id', 'day_of_week'], true);

        $this->createTable('medical_actions', [
            'id' => $this->bigPrimaryKey(),
            'code' => $this->string(32)->notNull()->unique(),
            'name' => $this->string(128)->notNull(),
            'price' => $this->decimal(14, 2)->notNull(),
            'is_active' => $this->boolean()->notNull()->defaultValue(true),
            'created_at' => $this->dateTime()->notNull()->defaultExpression('CURRENT_TIMESTAMP'),
            'updated_at' => $this->dateTime()->notNull()->defaultExpression('CURRENT_TIMESTAMP'),
        ]);
        $this->execute('ALTER TABLE medical_actions ADD CONSTRAINT medical_actions_price_check CHECK (price >= 0)');

        $this->createTable('medicines', [
            'id' => $this->bigPrimaryKey(),
            'code' => $this->string(32)->notNull()->unique(),
            'name' => $this->string(128)->notNull(),
            'unit' => $this->string(32)->notNull(),
            'price' => $this->decimal(14, 2)->notNull(),
            'stock' => $this->integer()->notNull()->defaultValue(0),
            'is_active' => $this->boolean()->notNull()->defaultValue(true),
            'created_at' => $this->dateTime()->notNull()->defaultExpression('CURRENT_TIMESTAMP'),
            'updated_at' => $this->dateTime()->notNull()->defaultExpression('CURRENT_TIMESTAMP'),
        ]);
        $this->execute('ALTER TABLE medicines ADD CONSTRAINT medicines_price_check CHECK (price >= 0)');
        $this->execute('ALTER TABLE medicines ADD CONSTRAINT medicines_stock_check CHECK (stock >= 0)');
    }

    public function safeDown(): void
    {
        $this->dropTable('medicines');
        $this->dropTable('medical_actions');
        $this->dropTable('doctor_schedules');
        $this->dropTable('polis');
        $this->dropTable('doctors');
        $this->dropTable('patients');
        $this->dropTable('users');
    }
}
