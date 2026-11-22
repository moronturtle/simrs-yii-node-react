# Skema Database

Skema ini dirancang untuk mendukung alur rawat jalan dengan tetap sederhana.

## Tabel utama

| Tabel | Fungsi |
|-------|--------|
| `users` | Akun dan role pengguna. |
| `patients` | Data pasien dan nomor rekam medis. |
| `doctors` | Data dokter. |
| `polis` | Unit pelayanan. |
| `doctor_schedules` | Jadwal praktik dokter per poli. |
| `medical_actions` | Master tindakan dan tarif. |
| `medicines` | Master obat, stok, dan harga. |
| `visits` | Kunjungan rawat jalan dan status antrean. |
| `examinations` | Rekam medis per kunjungan. |
| `visit_actions` | Tindakan yang dilakukan pada kunjungan. |
| `prescriptions` | Resep dokter. |
| `prescription_items` | Detail obat pada resep. |
| `lab_orders` | Permintaan pemeriksaan lab. |
| `lab_results` | Hasil pemeriksaan lab. |
| `invoices` | Tagihan per kunjungan. |
| `invoice_items` | Rincian biaya dengan snapshot harga. |
| `payments` | Pembayaran bertahap dan idempotency key. |
| `audit_logs` | Riwayat perubahan data penting. |

## Relasi utama

```mermaid
erDiagram
    users ||--o{ audit_logs : melakukan
    patients ||--o{ visits : memiliki
    doctors ||--o{ doctor_schedules : memiliki
    polis ||--o{ doctor_schedules : menyediakan
    visits ||--o| examinations : menghasilkan
    visits ||--o{ visit_actions : berisi
    medical_actions ||--o{ visit_actions : dipakai
    visits ||--o{ prescriptions : memiliki
    prescriptions ||--o{ prescription_items : berisi
    medicines ||--o{ prescription_items : diresepkan
    visits ||--o{ lab_orders : meminta
    lab_orders ||--o{ lab_results : menghasilkan
    visits ||--o| invoices : ditagihkan
    invoices ||--o{ invoice_items : berisi
    invoices ||--o{ payments : dibayar
```

## Aturan konsistensi

- `patients.medical_record_no` harus unik.
- `visits` mencegah kunjungan aktif ganda untuk pasien yang sama lewat partial unique index.
- `queue_counters` menyimpan counter antrean per `poli_id + queue_date`; update counter dilakukan atomik dengan `INSERT ... ON CONFLICT ... RETURNING`.
- `invoice_items` menyimpan nama layanan dan harga sebagai snapshot.
- `payments.idempotency_key` harus unik untuk mencegah pembayaran ganda.
- `medicines.stock` memakai `CHECK (stock >= 0)`.
- Index dibuat pada kolom yang sering dipakai filter: tanggal, status, `patient_id`, `visit_id`.

## Strategi index

- Kolom unik seperti `medical_record_no`, `visit_number`, `invoice_number`, dan `idempotency_key` otomatis punya unique index.
- Kolom nama master data memakai trigram index `pg_trgm` supaya pencarian `ILIKE '%keyword%'` tetap efisien.
- Kolom foreign key dan kombinasi filter utama diberi index, misalnya `patient_id + visit_date`, `doctor_id + visit_date`, `status + visit_date`, dan `poli_id + day_of_week`.
- Index ditambah lewat migration supaya schema tetap reproducible.

## Referensi SQL

File [schema.sql](schema.sql) berisi versi SQL yang lebih mudah dibaca untuk belajar struktur tabel, constraint, dan index. File itu adalah referensi; kalau ada perbedaan, yang benar adalah migration di `backend-yii/migrations`.

## ORM dan raw query

- CRUD normal memakai Yii `ActiveRecord` dan `ActiveQuery` supaya validasi, relasi, dan struktur kode tetap rapi.
- Raw query dipakai hanya untuk kasus yang memang lebih tepat di database, misalnya conditional update stok, counter antrean, aggregate dashboard, atau locking.
- Pola ini membuat business logic tetap jelas tanpa kehilangan kontrol untuk bagian yang butuh transaction safety.
