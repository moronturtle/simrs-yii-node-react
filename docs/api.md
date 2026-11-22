# Dokumentasi API

Dokumen ini adalah daftar endpoint yang direncanakan. Detail request/response final akan diperbarui setelah implementasi.

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

### Rawat jalan

| Method | Endpoint | Fungsi |
|--------|----------|--------|
| GET | `/api/visits` | Daftar kunjungan/antrean. |
| POST | `/api/visits` | Membuat kunjungan dan nomor antrean. |
| GET | `/api/visits/{id}` | Detail kunjungan. |
| POST | `/api/visits/{id}/examination` | Simpan pemeriksaan. |
| POST | `/api/visits/{id}/actions` | Tambah tindakan medis. |
| POST | `/api/visits/{id}/prescriptions` | Buat resep. |
| POST | `/api/prescriptions/{id}/dispense` | Serahkan obat dan kurangi stok. |

### Billing

| Method | Endpoint | Fungsi |
|--------|----------|--------|
| GET | `/api/visits/{id}/invoice` | Ambil invoice kunjungan. |
| POST | `/api/invoices/{id}/payments` | Catat pembayaran. |
| GET | `/api/payments/{id}/receipt` | Bukti pembayaran. |

## Node API

### Health

| Method | Endpoint | Fungsi |
|--------|----------|--------|
| GET | `/api/health` | Cek service Node dan koneksi database. |

### Laboratorium

| Method | Endpoint | Fungsi |
|--------|----------|--------|
| POST | `/api/lab/orders` | Buat permintaan lab dari Yii. |
| GET | `/api/lab/orders` | Daftar permintaan lab. |
| GET | `/api/lab/orders/{id}` | Detail permintaan. |
| PUT | `/api/lab/orders/{id}/result` | Input hasil lab. |

### Dashboard

| Method | Endpoint | Fungsi |
|--------|----------|--------|
| GET | `/api/dashboard/summary` | Ringkasan kunjungan, tindakan, pendapatan, obat, dan status bayar. |
