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

### Auth

| Method | Endpoint | Fungsi |
|--------|----------|--------|
| POST | `/api/auth/login` | Login dan mengambil JWT. |
| GET | `/api/auth/me` | Data user yang sedang login. |

### Master data

| Method | Endpoint | Fungsi |
|--------|----------|--------|
| GET/POST | `/api/patients` | List dan tambah pasien. |
| GET/PUT/DELETE | `/api/patients/{id}` | Detail, ubah, hapus pasien. |
| GET/POST | `/api/doctors` | List dan tambah dokter. |
| GET/PUT/DELETE | `/api/doctors/{id}` | Detail, ubah, hapus dokter. |
| GET/POST | `/api/polis` | List dan tambah poli. |
| GET/PUT/DELETE | `/api/polis/{id}` | Detail, ubah, hapus poli. |
| GET/POST | `/api/schedules` | List dan tambah jadwal dokter. |
| GET/POST | `/api/actions` | List dan tambah tindakan medis. |
| GET/POST | `/api/medicines` | List dan tambah obat. |

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
