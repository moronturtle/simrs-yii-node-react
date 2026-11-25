# Dokumentasi API

Dokumen ini menjelaskan endpoint yang diimplementasikan pada backend Yii (`http://localhost:8081/api`) dan service Node (`http://localhost:3001/api`), beserta contoh request/response sesuai implementasi saat ini.

## Format response

```json
{
  "success": true,
  "data": {},
  "error": null
}
```

Jika gagal:

```json
{
  "success": false,
  "data": null,
  "error": {
    "code": "VALIDATION_ERROR",
    "message": "Data tidak valid"
  }
}
```

## Yii API

### Health

| Method | Endpoint | Fungsi |
|--------|----------|--------|
| GET | `/api/ping` | Cek service Yii. |

### Auth

| Method | Endpoint | Fungsi |
|--------|----------|--------|
| POST | `/api/auth/login` | Login dan mengambil JWT. |
| GET | `/api/auth/me` | Data user yang sedang login. |
| GET | `/api/admin/ping` | Contoh endpoint khusus role `ADMIN`. |

Contoh login:

```json
{
  "username": "admin",
  "password": "password123"
}
```

Response sukses:

```json
{
  "success": true,
  "data": {
    "token": "...",
    "token_type": "Bearer",
    "expires_in": 28800,
    "user": {
      "id": 1,
      "username": "admin",
      "name": "Administrator",
      "role": "ADMIN"
    }
  },
  "error": null
}
```

Endpoint selain login memakai header:

```http
Authorization: Bearer <token>
```

### Master data

Semua endpoint master data memakai Bearer token. Semua role bisa membaca data, tetapi hak tulis dibatasi:

- `patients`: `ADMIN`, `PENDAFTARAN`
- `doctors`, `polis`, `schedules`, `actions`, `medicines`: `ADMIN`

`GET` list mendukung query:

- `q` untuk pencarian,
- `page` untuk halaman,
- `per-page` untuk jumlah data.

Response list:

```json
{
  "success": true,
  "data": {
    "items": [],
    "pagination": {
      "page": 1,
      "page_size": 20,
      "total": 0
    }
  },
  "error": null
}
```

| Method | Endpoint | Fungsi |
|--------|----------|--------|
| GET/POST | `/api/patients` | List dan tambah pasien. |
| GET/PUT/PATCH/DELETE | `/api/patients/{id}` | Detail, ubah, hapus pasien. |
| GET/POST | `/api/doctors` | List dan tambah dokter. |
| GET/PUT/PATCH/DELETE | `/api/doctors/{id}` | Detail, ubah, hapus dokter. |
| GET/POST | `/api/polis` | List dan tambah poli. |
| GET/PUT/PATCH/DELETE | `/api/polis/{id}` | Detail, ubah, hapus poli. |
| GET/POST | `/api/schedules` | List dan tambah jadwal dokter. |
| GET/PUT/PATCH/DELETE | `/api/schedules/{id}` | Detail, ubah, hapus jadwal. |
| GET/POST | `/api/actions` | List dan tambah tindakan medis. |
| GET/PUT/PATCH/DELETE | `/api/actions/{id}` | Detail, ubah, hapus tindakan. |
| GET/POST | `/api/medicines` | List dan tambah obat. |
| GET/PUT/PATCH/DELETE | `/api/medicines/{id}` | Detail, ubah, hapus obat. |

Untuk pasien, `medical_record_no` tidak perlu dikirim saat create. Backend akan membuat nomor dengan format `RM-000001`; kalau client mengirim nilai sendiri, validasi `UNIQUE` tetap berlaku.

### Rawat jalan

`GET /api/visits` mendukung query `q`, `status`, `visit_date`, `patient_id`, `poli_id`, `doctor_id`, `page`, dan `per-page`.

| Method | Endpoint | Role tulis | Fungsi |
|--------|----------|------------|--------|
| GET | `/api/visits` | Semua role login | Daftar kunjungan/antrean. |
| POST | `/api/visits` | `ADMIN`, `PENDAFTARAN` | Membuat kunjungan dan nomor antrean. |
| GET | `/api/visits/{id}` | Semua role login | Detail kunjungan. |
| PATCH | `/api/visits/{id}/status` | `ADMIN`, `PENDAFTARAN`, `DOKTER` | Mengubah status antrean. |
| GET | `/api/visits/{id}/examination` | Semua role login | Detail pemeriksaan. |
| POST | `/api/visits/{id}/examination` | `DOKTER` | Simpan pemeriksaan. |
| GET | `/api/visits/{id}/actions` | Semua role login | Daftar tindakan kunjungan. |
| POST | `/api/visits/{id}/actions` | `DOKTER` | Tambah tindakan medis. |
| GET | `/api/visits/{id}/lab-orders` | Semua role login | Daftar order lab pada kunjungan. |
| POST | `/api/visits/{id}/lab-orders` | `DOKTER` | Buat order lab dan item tagihan `LAB`. |
| GET | `/api/prescriptions` | Semua role login | Daftar resep; filter `visit_id` dan `status`. |
| GET | `/api/prescriptions/{id}` | Semua role login | Detail resep dan item obat. |
| POST | `/api/visits/{id}/prescriptions` | `DOKTER` | Buat resep. |
| POST | `/api/prescriptions/{id}/dispense` | `FARMASI` | Serahkan obat dan kurangi stok. |

Contoh body create visit:

```json
{
  "patient_id": 1,
  "schedule_id": 1,
  "chief_complaint": "Demam dan batuk"
}
```

`doctor_id` dan `poli_id` diambil dari `schedule_id` agar tidak terjadi mismatch jadwal. Nomor antrean dibuat di dalam transaction memakai counter per poli dan tanggal.

Contoh body pemeriksaan:

```json
{
  "anamnesis": "Demam sejak 2 hari",
  "vital_signs": {
    "temperature": 38.2,
    "blood_pressure": "120/80",
    "pulse": 88
  },
  "physical_exam": "Faring hiperemis",
  "primary_diagnosis": "ISPA",
  "secondary_diagnosis": "Observasi anemia"
}
```

Contoh body tindakan:

```json
{
  "medical_action_id": 1,
  "quantity": 1,
  "notes": "Konsultasi awal"
}
```

Saat tindakan dibuat, `visit_actions.price` dan `invoice_items.unit_price` menyimpan snapshot tarif saat itu. Perubahan harga master tindakan tidak mengubah tagihan/invoice dibuat sebelumnya.

Contoh body order lab:

```json
{
  "test_name": "Darah Lengkap",
  "price": 75000
}
```

Order lab hanya bisa dibuat saat kunjungan berstatus `IN_EXAM` atau `LAB`. Yii menyimpan order sebagai `REQUESTED`, mengubah visit menjadi `LAB`, dan menambah item invoice `LAB` dengan snapshot biaya.

Contoh body resep:

```json
{
  "notes": "Diminum setelah makan",
  "items": [
    {
      "medicine_id": 1,
      "dose": "500mg",
      "frequency": "3x sehari",
      "quantity": 10
    }
  ]
}
```

Resep hanya bisa dibuat setelah pemeriksaan dimulai. Harga obat disimpan sebagai snapshot di `prescription_items.price`. Setelah resep dibuat, status kunjungan berubah menjadi `PHARMACY`.

Saat dispense, row resep dikunci `FOR UPDATE`, lalu stok obat dikurangi di dalam transaction memakai conditional update `stock >= quantity`. Kalau ada satu item yang stoknya kurang, semua perubahan dibatalkan dan endpoint mengembalikan `409`. Item obat kemudian masuk `invoice_items` sebagai `MEDICINE`, dan status kunjungan berubah menjadi `BILLING`. Dispense ulang resep yang sama juga mengembalikan `409`, jadi stok tidak berkurang dua kali.

### Billing

`GET /api/invoices` mendukung query `q`, `status`, `page`, dan `per-page`.

| Method | Endpoint | Role tulis | Fungsi |
|--------|----------|------------|--------|
| GET | `/api/invoices` | Semua role login | Daftar invoice. |
| GET | `/api/invoices/{id}` | Semua role login | Detail invoice, item biaya, dan pembayaran. |
| GET | `/api/visits/{id}/invoice` | Semua role login | Ambil invoice dari kunjungan. |
| POST | `/api/invoices/{id}/payments` | `KASIR` | Catat pembayaran. |
| GET | `/api/payments/{id}/receipt` | Semua role login | Bukti pembayaran. |

Contoh body pembayaran:

```json
{
  "amount": 50000,
  "method": "CASH",
  "idempotency_key": "pay-visit-5-001"
}
```

`method` hanya menerima `CASH` atau `NON_CASH`. Nominal tidak boleh melebihi sisa tagihan. Kalau `idempotency_key` dikirim ulang, API mengembalikan payment yang sama dengan HTTP `200`, bukan membuat row baru. Invoice berubah `UNPAID → PARTIAL → PAID`; saat lunas, status kunjungan ikut menjadi `PAID`.

## Node API

### Health

| Method | Endpoint | Fungsi |
|--------|----------|--------|
| GET | `/api/health` | Cek service Node dan koneksi database. |

### Laboratorium

| Method | Endpoint | Role tulis | Fungsi |
|--------|----------|------------|--------|
| GET | `/api/lab/orders` | Semua role login | Daftar order lab; filter `status` dan `visit_id`. |
| GET | `/api/lab/orders/{id}` | Semua role login | Detail order dan hasil lab. |
| PATCH | `/api/lab/orders/{id}/start` | `LAB` | Ubah `REQUESTED` menjadi `IN_PROGRESS`. |
| PUT | `/api/lab/orders/{id}/result` | `LAB` | Input atau update hasil lab, lalu status menjadi `COMPLETED`. |

`PUT /result` memakai upsert berdasarkan `lab_order_id`, sehingga retry setelah timeout tidak membuat row hasil ganda. Kalau Node sedang tidak tersedia, UI menampilkan error dan petugas bisa mengulang aksi secara manual.

### Dashboard

| Method | Endpoint | Role akses | Fungsi |
|--------|----------|------------|--------|
| GET | `/api/dashboard/summary` | Semua role login | Ringkasan kunjungan, pendapatan, invoice belum lunas, obat diserahkan, dan order lab pending. |

Contoh response:

```json
{
  "success": true,
  "data": {
    "visitsToday": 4,
    "totalVisits": 12,
    "revenue": 245000,
    "pendingInvoices": 1,
    "medicineDispensed": 3,
    "pendingLabOrders": 0
  },
  "error": null
}
```

Dashboard Node hanya membaca aggregate PostgreSQL dan tidak mengubah data inti.
