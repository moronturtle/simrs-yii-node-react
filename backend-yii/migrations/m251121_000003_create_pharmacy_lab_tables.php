<?php

use yii\db\Migration;

class m251121_000003_create_pharmacy_lab_tables extends Migration
{
    public function safeUp(): void
    {
        $this->createTable('prescriptions', [
            'id' => $this->bigPrimaryKey(),
            'visit_id' => $this->bigInteger()->notNull(),
            'doctor_id' => $this->bigInteger()->notNull(),
            'status' => $this->string(32)->notNull()->defaultValue('PRESCRIBED'),
            'notes' => $this->text(),
            'created_at' => $this->dateTime()->notNull()->defaultExpression('CURRENT_TIMESTAMP'),
            'updated_at' => $this->dateTime()->notNull()->defaultExpression('CURRENT_TIMESTAMP'),
        ]);
        $this->addForeignKey('fk_prescription_visit', 'prescriptions', 'visit_id', 'visits', 'id', 'CASCADE', 'CASCADE');
        $this->addForeignKey('fk_prescription_doctor', 'prescriptions', 'doctor_id', 'users', 'id', 'RESTRICT', 'CASCADE');
        $this->execute("ALTER TABLE prescriptions ADD CONSTRAINT prescriptions_status_check CHECK (status IN ('PRESCRIBED','DISPENSED','CANCELLED'))");
        $this->createIndex('idx_prescriptions_visit', 'prescriptions', 'visit_id');

        $this->createTable('prescription_items', [
            'id' => $this->bigPrimaryKey(),
            'prescription_id' => $this->bigInteger()->notNull(),
            'medicine_id' => $this->bigInteger()->notNull(),
            'dose' => $this->string(128)->notNull(),
            'frequency' => $this->string(128)->notNull(),
            'quantity' => $this->integer()->notNull(),
            'price' => $this->decimal(14, 2)->notNull(),
            'dispensed_at' => $this->dateTime(),
            'dispensed_by' => $this->bigInteger(),
            'created_at' => $this->dateTime()->notNull()->defaultExpression('CURRENT_TIMESTAMP'),
            'updated_at' => $this->dateTime()->notNull()->defaultExpression('CURRENT_TIMESTAMP'),
        ]);
        $this->addForeignKey('fk_prescription_item_prescription', 'prescription_items', 'prescription_id', 'prescriptions', 'id', 'CASCADE', 'CASCADE');
        $this->addForeignKey('fk_prescription_item_medicine', 'prescription_items', 'medicine_id', 'medicines', 'id', 'RESTRICT', 'CASCADE');
        $this->addForeignKey('fk_prescription_item_dispensed_by', 'prescription_items', 'dispensed_by', 'users', 'id', 'SET NULL', 'CASCADE');
        $this->execute('ALTER TABLE prescription_items ADD CONSTRAINT prescription_items_quantity_check CHECK (quantity > 0)');
        $this->execute('ALTER TABLE prescription_items ADD CONSTRAINT prescription_items_price_check CHECK (price >= 0)');

        $this->createTable('lab_orders', [
            'id' => $this->bigPrimaryKey(),
            'visit_id' => $this->bigInteger()->notNull(),
            'order_number' => $this->string(32)->notNull()->unique(),
            'test_name' => $this->string(128)->notNull(),
            'status' => $this->string(32)->notNull()->defaultValue('REQUESTED'),
            'price' => $this->decimal(14, 2)->notNull()->defaultValue(0),
            'requested_by' => $this->bigInteger()->notNull(),
            'requested_at' => $this->dateTime()->notNull()->defaultExpression('CURRENT_TIMESTAMP'),
            'created_at' => $this->dateTime()->notNull()->defaultExpression('CURRENT_TIMESTAMP'),
            'updated_at' => $this->dateTime()->notNull()->defaultExpression('CURRENT_TIMESTAMP'),
        ]);
        $this->addForeignKey('fk_lab_order_visit', 'lab_orders', 'visit_id', 'visits', 'id', 'CASCADE', 'CASCADE');
        $this->addForeignKey('fk_lab_order_user', 'lab_orders', 'requested_by', 'users', 'id', 'RESTRICT', 'CASCADE');
        $this->execute("ALTER TABLE lab_orders ADD CONSTRAINT lab_orders_status_check CHECK (status IN ('REQUESTED','IN_PROGRESS','COMPLETED','FAILED'))");
        $this->execute('ALTER TABLE lab_orders ADD CONSTRAINT lab_orders_price_check CHECK (price >= 0)');
        $this->createIndex('idx_lab_orders_status', 'lab_orders', 'status');
        $this->createIndex('idx_lab_orders_visit', 'lab_orders', 'visit_id');

        $this->createTable('lab_results', [
            'id' => $this->bigPrimaryKey(),
            'lab_order_id' => $this->bigInteger()->notNull()->unique(),
            'result_text' => $this->text()->notNull(),
            'result_values' => $this->json(),
            'resulted_by' => $this->bigInteger(),
            'resulted_at' => $this->dateTime()->notNull()->defaultExpression('CURRENT_TIMESTAMP'),
            'created_at' => $this->dateTime()->notNull()->defaultExpression('CURRENT_TIMESTAMP'),
            'updated_at' => $this->dateTime()->notNull()->defaultExpression('CURRENT_TIMESTAMP'),
        ]);
        $this->addForeignKey('fk_lab_result_order', 'lab_results', 'lab_order_id', 'lab_orders', 'id', 'CASCADE', 'CASCADE');
        $this->addForeignKey('fk_lab_result_user', 'lab_results', 'resulted_by', 'users', 'id', 'SET NULL', 'CASCADE');
    }

    public function safeDown(): void
    {
        $this->dropTable('lab_results');
        $this->dropTable('lab_orders');
        $this->dropTable('prescription_items');
        $this->dropTable('prescriptions');
    }
}
