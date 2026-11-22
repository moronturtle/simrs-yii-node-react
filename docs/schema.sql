-- Referensi schema SIMRS Rawat Jalan.
-- File ini untuk belajar/review. Sumber kebenaran tetap backend-yii/migrations.

CREATE EXTENSION IF NOT EXISTS pg_trgm;

CREATE TABLE users (
    id BIGSERIAL PRIMARY KEY,
    username VARCHAR(64) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    name VARCHAR(128) NOT NULL,
    role VARCHAR(32) NOT NULL CHECK (role IN ('ADMIN','PENDAFTARAN','DOKTER','FARMASI','LAB','KASIR')),
    is_active BOOLEAN NOT NULL DEFAULT TRUE,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE patients (
    id BIGSERIAL PRIMARY KEY,
    medical_record_no VARCHAR(32) NOT NULL UNIQUE,
    nik VARCHAR(32) UNIQUE,
    name VARCHAR(128) NOT NULL,
    date_of_birth DATE,
    gender VARCHAR(16) CHECK (gender IN ('L','P') OR gender IS NULL),
    phone VARCHAR(32),
    address TEXT,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE doctors (
    id BIGSERIAL PRIMARY KEY,
    name VARCHAR(128) NOT NULL,
    specialization VARCHAR(128),
    phone VARCHAR(32),
    is_active BOOLEAN NOT NULL DEFAULT TRUE,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE polis (
    id BIGSERIAL PRIMARY KEY,
    code VARCHAR(32) NOT NULL UNIQUE,
    name VARCHAR(128) NOT NULL,
    is_active BOOLEAN NOT NULL DEFAULT TRUE,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE doctor_schedules (
    id BIGSERIAL PRIMARY KEY,
    doctor_id BIGINT NOT NULL REFERENCES doctors(id),
    poli_id BIGINT NOT NULL REFERENCES polis(id),
    day_of_week SMALLINT NOT NULL CHECK (day_of_week BETWEEN 0 AND 6),
    start_time TIME NOT NULL,
    end_time TIME NOT NULL CHECK (start_time < end_time),
    is_active BOOLEAN NOT NULL DEFAULT TRUE,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE (doctor_id, poli_id, day_of_week)
);

CREATE TABLE medical_actions (
    id BIGSERIAL PRIMARY KEY,
    code VARCHAR(32) NOT NULL UNIQUE,
    name VARCHAR(128) NOT NULL,
    price NUMERIC(14,2) NOT NULL CHECK (price >= 0),
    is_active BOOLEAN NOT NULL DEFAULT TRUE,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE medicines (
    id BIGSERIAL PRIMARY KEY,
    code VARCHAR(32) NOT NULL UNIQUE,
    name VARCHAR(128) NOT NULL,
    unit VARCHAR(32) NOT NULL,
    price NUMERIC(14,2) NOT NULL CHECK (price >= 0),
    stock INTEGER NOT NULL DEFAULT 0 CHECK (stock >= 0),
    is_active BOOLEAN NOT NULL DEFAULT TRUE,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE queue_counters (
    poli_id BIGINT NOT NULL REFERENCES polis(id),
    queue_date DATE NOT NULL,
    last_number INTEGER NOT NULL DEFAULT 0,
    PRIMARY KEY (poli_id, queue_date)
);

CREATE TABLE visits (
    id BIGSERIAL PRIMARY KEY,
    visit_number VARCHAR(32) NOT NULL UNIQUE,
    patient_id BIGINT NOT NULL REFERENCES patients(id),
    poli_id BIGINT NOT NULL REFERENCES polis(id),
    doctor_id BIGINT NOT NULL REFERENCES doctors(id),
    schedule_id BIGINT REFERENCES doctor_schedules(id) ON DELETE SET NULL,
    visit_date DATE NOT NULL DEFAULT CURRENT_DATE,
    queue_number INTEGER NOT NULL,
    status VARCHAR(32) NOT NULL DEFAULT 'REGISTERED'
        CHECK (status IN ('REGISTERED','IN_EXAM','PHARMACY','LAB','BILLING','PAID','CANCELLED')),
    chief_complaint TEXT,
    registered_by BIGINT NOT NULL REFERENCES users(id),
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE (poli_id, visit_date, queue_number)
);

-- Satu pasien hanya boleh punya satu kunjungan aktif.
CREATE UNIQUE INDEX uq_active_visit_patient
    ON visits (patient_id)
    WHERE status IN ('REGISTERED','IN_EXAM','PHARMACY','LAB','BILLING');

CREATE TABLE examinations (
    id BIGSERIAL PRIMARY KEY,
    visit_id BIGINT NOT NULL UNIQUE REFERENCES visits(id) ON DELETE CASCADE,
    anamnesis TEXT,
    vital_signs JSON,
    physical_exam TEXT,
    primary_diagnosis VARCHAR(255) NOT NULL,
    secondary_diagnosis TEXT,
    examined_by BIGINT NOT NULL REFERENCES users(id),
    examined_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE visit_actions (
    id BIGSERIAL PRIMARY KEY,
    visit_id BIGINT NOT NULL REFERENCES visits(id) ON DELETE CASCADE,
    medical_action_id BIGINT NOT NULL REFERENCES medical_actions(id),
    quantity INTEGER NOT NULL DEFAULT 1 CHECK (quantity > 0),
    price NUMERIC(14,2) NOT NULL CHECK (price >= 0),
    notes TEXT,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE prescriptions (
    id BIGSERIAL PRIMARY KEY,
    visit_id BIGINT NOT NULL REFERENCES visits(id) ON DELETE CASCADE,
    doctor_id BIGINT NOT NULL REFERENCES users(id),
    status VARCHAR(32) NOT NULL DEFAULT 'PRESCRIBED'
        CHECK (status IN ('PRESCRIBED','DISPENSED','CANCELLED')),
    notes TEXT,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE prescription_items (
    id BIGSERIAL PRIMARY KEY,
    prescription_id BIGINT NOT NULL REFERENCES prescriptions(id) ON DELETE CASCADE,
    medicine_id BIGINT NOT NULL REFERENCES medicines(id),
    dose VARCHAR(128) NOT NULL,
    frequency VARCHAR(128) NOT NULL,
    quantity INTEGER NOT NULL CHECK (quantity > 0),
    price NUMERIC(14,2) NOT NULL CHECK (price >= 0),
    dispensed_at TIMESTAMP,
    dispensed_by BIGINT REFERENCES users(id) ON DELETE SET NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE lab_orders (
    id BIGSERIAL PRIMARY KEY,
    visit_id BIGINT NOT NULL REFERENCES visits(id) ON DELETE CASCADE,
    order_number VARCHAR(32) NOT NULL UNIQUE,
    test_name VARCHAR(128) NOT NULL,
    status VARCHAR(32) NOT NULL DEFAULT 'REQUESTED'
        CHECK (status IN ('REQUESTED','IN_PROGRESS','COMPLETED','FAILED')),
    price NUMERIC(14,2) NOT NULL DEFAULT 0 CHECK (price >= 0),
    requested_by BIGINT NOT NULL REFERENCES users(id),
    requested_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE lab_results (
    id BIGSERIAL PRIMARY KEY,
    lab_order_id BIGINT NOT NULL UNIQUE REFERENCES lab_orders(id) ON DELETE CASCADE,
    result_text TEXT NOT NULL,
    result_values JSON,
    resulted_by BIGINT REFERENCES users(id) ON DELETE SET NULL,
    resulted_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE invoices (
    id BIGSERIAL PRIMARY KEY,
    visit_id BIGINT NOT NULL UNIQUE REFERENCES visits(id) ON DELETE CASCADE,
    invoice_number VARCHAR(32) NOT NULL UNIQUE,
    status VARCHAR(32) NOT NULL DEFAULT 'UNPAID'
        CHECK (status IN ('UNPAID','PARTIAL','PAID','CANCELLED')),
    total_amount NUMERIC(14,2) NOT NULL DEFAULT 0,
    paid_amount NUMERIC(14,2) NOT NULL DEFAULT 0,
    issued_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CHECK (total_amount >= 0 AND paid_amount >= 0),
    CHECK (paid_amount <= total_amount)
);

CREATE TABLE invoice_items (
    id BIGSERIAL PRIMARY KEY,
    invoice_id BIGINT NOT NULL REFERENCES invoices(id) ON DELETE CASCADE,
    item_type VARCHAR(32) NOT NULL
        CHECK (item_type IN ('REGISTRATION','ACTION','LAB','MEDICINE','ADJUSTMENT')),
    reference_id BIGINT,
    description VARCHAR(255) NOT NULL,
    quantity INTEGER NOT NULL DEFAULT 1 CHECK (quantity > 0),
    unit_price NUMERIC(14,2) NOT NULL,
    total_price NUMERIC(14,2) NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CHECK (unit_price >= 0 AND total_price >= 0)
);

CREATE TABLE payments (
    id BIGSERIAL PRIMARY KEY,
    invoice_id BIGINT NOT NULL REFERENCES invoices(id) ON DELETE CASCADE,
    payment_number VARCHAR(32) NOT NULL UNIQUE,
    amount NUMERIC(14,2) NOT NULL CHECK (amount > 0),
    method VARCHAR(32) NOT NULL CHECK (method IN ('CASH','NON_CASH')),
    idempotency_key VARCHAR(128) NOT NULL UNIQUE,
    paid_by BIGINT NOT NULL REFERENCES users(id),
    paid_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE audit_logs (
    id BIGSERIAL PRIMARY KEY,
    table_name VARCHAR(64) NOT NULL,
    record_id BIGINT NOT NULL,
    action VARCHAR(32) NOT NULL CHECK (action IN ('INSERT','UPDATE','DELETE','ADJUSTMENT')),
    old_values JSON,
    new_values JSON,
    changed_by BIGINT REFERENCES users(id) ON DELETE SET NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
);

-- Index pencarian master data.
CREATE INDEX idx_patients_name_trgm ON patients USING gin (name gin_trgm_ops);
CREATE INDEX idx_doctors_name_trgm ON doctors USING gin (name gin_trgm_ops);
CREATE INDEX idx_polis_name_trgm ON polis USING gin (name gin_trgm_ops);
CREATE INDEX idx_medical_actions_name_trgm ON medical_actions USING gin (name gin_trgm_ops);
CREATE INDEX idx_medicines_name_trgm ON medicines USING gin (name gin_trgm_ops);

-- Index filter/join utama.
CREATE INDEX idx_schedules_poli_day ON doctor_schedules (poli_id, day_of_week);
CREATE INDEX idx_visits_patient_date ON visits (patient_id, visit_date);
CREATE INDEX idx_visits_doctor_date ON visits (doctor_id, visit_date);
CREATE INDEX idx_visit_status_date ON visits (status, visit_date);
CREATE INDEX idx_visit_actions_visit ON visit_actions (visit_id);
CREATE INDEX idx_prescriptions_visit ON prescriptions (visit_id);
CREATE INDEX idx_prescriptions_doctor ON prescriptions (doctor_id);
CREATE INDEX idx_prescription_items_medicine ON prescription_items (medicine_id);
CREATE INDEX idx_lab_orders_visit ON lab_orders (visit_id);
CREATE INDEX idx_lab_orders_status ON lab_orders (status);
CREATE INDEX idx_lab_orders_requested_by ON lab_orders (requested_by);
CREATE INDEX idx_invoices_status ON invoices (status);
CREATE INDEX idx_invoice_items_invoice ON invoice_items (invoice_id);
CREATE INDEX idx_payments_invoice ON payments (invoice_id);
CREATE INDEX idx_payments_paid_by ON payments (paid_by);
CREATE INDEX idx_audit_record ON audit_logs (table_name, record_id);
