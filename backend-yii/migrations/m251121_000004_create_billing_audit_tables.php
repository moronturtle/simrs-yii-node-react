<?php

use yii\db\Migration;

class m251121_000004_create_billing_audit_tables extends Migration
{
    public function safeUp(): void
    {
        $this->createTable('invoices', [
            'id' => $this->bigPrimaryKey(),
            'visit_id' => $this->bigInteger()->notNull()->unique(),
            'invoice_number' => $this->string(32)->notNull()->unique(),
            'status' => $this->string(32)->notNull()->defaultValue('UNPAID'),
            'total_amount' => $this->decimal(14, 2)->notNull()->defaultValue(0),
            'paid_amount' => $this->decimal(14, 2)->notNull()->defaultValue(0),
            'issued_at' => $this->dateTime()->notNull()->defaultExpression('CURRENT_TIMESTAMP'),
            'created_at' => $this->dateTime()->notNull()->defaultExpression('CURRENT_TIMESTAMP'),
            'updated_at' => $this->dateTime()->notNull()->defaultExpression('CURRENT_TIMESTAMP'),
        ]);
        $this->addForeignKey('fk_invoice_visit', 'invoices', 'visit_id', 'visits', 'id', 'CASCADE', 'CASCADE');
        $this->execute("ALTER TABLE invoices ADD CONSTRAINT invoices_status_check CHECK (status IN ('UNPAID','PARTIAL','PAID','CANCELLED'))");
        $this->execute('ALTER TABLE invoices ADD CONSTRAINT invoices_amount_check CHECK (total_amount >= 0 AND paid_amount >= 0)');
        $this->execute('ALTER TABLE invoices ADD CONSTRAINT invoices_paid_check CHECK (paid_amount <= total_amount)');
        $this->createIndex('idx_invoices_status', 'invoices', 'status');

        $this->createTable('invoice_items', [
            'id' => $this->bigPrimaryKey(),
            'invoice_id' => $this->bigInteger()->notNull(),
            'item_type' => $this->string(32)->notNull(),
            'reference_id' => $this->bigInteger(),
            'description' => $this->string()->notNull(),
            'quantity' => $this->integer()->notNull()->defaultValue(1),
            'unit_price' => $this->decimal(14, 2)->notNull(),
            'total_price' => $this->decimal(14, 2)->notNull(),
            'created_at' => $this->dateTime()->notNull()->defaultExpression('CURRENT_TIMESTAMP'),
        ]);
        $this->addForeignKey('fk_invoice_item_invoice', 'invoice_items', 'invoice_id', 'invoices', 'id', 'CASCADE', 'CASCADE');
        $this->execute("ALTER TABLE invoice_items ADD CONSTRAINT invoice_items_type_check CHECK (item_type IN ('REGISTRATION','ACTION','LAB','MEDICINE','ADJUSTMENT'))");
        $this->execute('ALTER TABLE invoice_items ADD CONSTRAINT invoice_items_quantity_check CHECK (quantity > 0)');
        $this->execute('ALTER TABLE invoice_items ADD CONSTRAINT invoice_items_price_check CHECK (unit_price >= 0 AND total_price >= 0)');
        $this->createIndex('idx_invoice_items_invoice', 'invoice_items', 'invoice_id');

        $this->createTable('payments', [
            'id' => $this->bigPrimaryKey(),
            'invoice_id' => $this->bigInteger()->notNull(),
            'payment_number' => $this->string(32)->notNull()->unique(),
            'amount' => $this->decimal(14, 2)->notNull(),
            'method' => $this->string(32)->notNull(),
            'idempotency_key' => $this->string(128)->notNull()->unique(),
            'paid_by' => $this->bigInteger()->notNull(),
            'paid_at' => $this->dateTime()->notNull()->defaultExpression('CURRENT_TIMESTAMP'),
            'created_at' => $this->dateTime()->notNull()->defaultExpression('CURRENT_TIMESTAMP'),
        ]);
        $this->addForeignKey('fk_payment_invoice', 'payments', 'invoice_id', 'invoices', 'id', 'CASCADE', 'CASCADE');
        $this->addForeignKey('fk_payment_user', 'payments', 'paid_by', 'users', 'id', 'RESTRICT', 'CASCADE');
        $this->execute('ALTER TABLE payments ADD CONSTRAINT payments_amount_check CHECK (amount > 0)');
        $this->execute("ALTER TABLE payments ADD CONSTRAINT payments_method_check CHECK (method IN ('CASH','NON_CASH'))");
        $this->createIndex('idx_payments_invoice', 'payments', 'invoice_id');

        $this->createTable('audit_logs', [
            'id' => $this->bigPrimaryKey(),
            'table_name' => $this->string(64)->notNull(),
            'record_id' => $this->bigInteger()->notNull(),
            'action' => $this->string(32)->notNull(),
            'old_values' => $this->json(),
            'new_values' => $this->json(),
            'changed_by' => $this->bigInteger(),
            'created_at' => $this->dateTime()->notNull()->defaultExpression('CURRENT_TIMESTAMP'),
        ]);
        $this->addForeignKey('fk_audit_user', 'audit_logs', 'changed_by', 'users', 'id', 'SET NULL', 'CASCADE');
        $this->execute("ALTER TABLE audit_logs ADD CONSTRAINT audit_logs_action_check CHECK (action IN ('INSERT','UPDATE','DELETE','ADJUSTMENT'))");
        $this->createIndex('idx_audit_record', 'audit_logs', ['table_name', 'record_id']);
    }

    public function safeDown(): void
    {
        $this->dropTable('audit_logs');
        $this->dropTable('payments');
        $this->dropTable('invoice_items');
        $this->dropTable('invoices');
    }
}
