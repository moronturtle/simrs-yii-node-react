# Panduan Demo

Dokumen ini membantu menjalankan demo end-to-end dan menyiapkan screenshot untuk penilaian.

## Persiapan

```bash
cp .env.example .env
docker compose up -d --build
docker compose exec yii composer install
docker compose exec yii php yii migrate/up --interactive=0
```

Cek service:

- Yii API: `http://localhost:8081/api/ping`
- Node API: `http://localhost:3001/api/health`
- React web: `http://localhost:5173`

Akun development memakai password `password123`.

| Username | Role | Fungsi demo |
|----------|------|-------------|
| `admin` | `ADMIN` | Master data dan dashboard |
| `pendaftaran` | `PENDAFTARAN` | Pasien dan antrean |
| `dokter` | `DOKTER` | Pemeriksaan, tindakan, lab, resep |
| `farmasi` | `FARMASI` | Penyerahan obat |
| `lab` | `LAB` | Proses dan hasil laboratorium |
| `kasir` | `KASIR` | Pembayaran |

## Alur demo yang disarankan

### 1. Login pendaftaran

1. Login sebagai `pendaftaran`.
2. Buka menu `Master Data` kalau perlu membuat pasien baru. No. RM dibuat otomatis oleh sistem.
3. Buka `Antrean`, isi form `Daftar Kunjungan`, lalu simpan.
4. Tunjukkan nomor antrean dan status `REGISTERED`.

### 2. Pemeriksaan dokter

1. Logout, lalu login sebagai `dokter`.
2. Buka `Antrean`, klik nomor kunjungan pasien.
3. Isi form pemeriksaan:
   - keluhan/anamnesis,
   - tanda vital,
   - pemeriksaan fisik,
   - diagnosis utama.
4. Tambahkan satu tindakan medis.
5. Buat order laboratorium.
6. Buat resep satu atau dua obat.

Hal yang bisa dijelaskan:

- `schedule_id` menentukan dokter dan poli.
- tarif tindakan dan harga obat disimpan sebagai snapshot.
- status kunjungan berpindah otomatis mengikuti alur.

### 3. Laboratorium

1. Login sebagai `lab`.
2. Buka `Laboratorium`.
3. Klik `Mulai` pada order `REQUESTED`.
4. Isi hasil lab, lalu simpan.
5. Login kembali sebagai `dokter` dan buka detail kunjungan untuk menunjukkan hasil.

Hal yang bisa dijelaskan:

- order dan billing dibuat Yii,
- workflow `REQUESTED → IN_PROGRESS → COMPLETED` dikelola Node,
- hasil lab memakai upsert `lab_order_id`, jadi retry tidak membuat data ganda.

### 4. Farmasi

1. Login sebagai `farmasi`.
2. Buka `Farmasi`.
3. Pilih resep `PRESCRIBED`, cek item, lalu klik `Dispense`.
4. Tunjukkan status berubah `DISPENSED` dan stok obat berkurang.

Hal yang bisa dijelaskan:

- row resep dikunci `FOR UPDATE` saat dispense,
- stok memakai conditional update `stock >= quantity`,
- kalau stok kurang atau resep sudah diproses, transaction rollback.

### 5. Billing dan pembayaran

1. Login sebagai `kasir`.
2. Buka `Billing`.
3. Pilih invoice, lalu tunjukkan rincian biaya pendaftaran, tindakan, lab, dan obat.
4. Bisa tunjukkan pembayaran sebagian terlebih dahulu, lalu pelunasan.
5. Buka bukti pembayaran.
6. Status invoice menjadi `PAID` dan kunjungan ikut `PAID`.

Hal yang bisa dijelaskan:

- `idempotency_key` mencegah pembayaran dobel saat retry,
- invoice dikunci `FOR UPDATE` saat payment,
- overpayment ditolak.

### 6. Dashboard

1. Login sebagai `admin` atau role lain.
2. Buka `Dashboard`.
3. Tunjukkan metric kunjungan, revenue, invoice pending, obat terdispense, dan lab pending.

## Checklist screenshot

Simpan screenshot di folder `docs/screenshots/` dengan nama yang jelas supaya mudah dilampirkan:

1. `01-login.png` — halaman login.
2. `02-antrean.png` — daftar kunjungan dan nomor antrean.
3. `03-pemeriksaan.png` — detail kunjungan dan form pemeriksaan.
4. `04-tindakan-resep.png` — tindakan, order lab, dan resep.
5. `05-lab.png` — input hasil laboratorium.
6. `06-farmasi.png` — resep dan tombol dispense.
7. `07-invoice.png` — rincian invoice.
8. `08-pembayaran.png` — form pembayaran atau bukti bayar.
9. `09-dashboard.png` — metric dashboard.

## Kalau Node mati saat demo

Halaman detail kunjungan tetap bisa dibuka. Bagian laboratorium akan menampilkan pesan error dan tombol `Coba lagi`. Ini bisa dijelaskan sebagai error boundary sederhana: service pendukung down tidak merusak alur utama Yii.

## Skenario teknis yang sudah diverifikasi

| Skenario | Hasil yang diharapkan |
|----------|-----------------------|
| Daftar kunjungan aktif dua kali untuk pasien yang sama | Request pertama `201`; request kedua `409` |
| Dua pendaftaran antrean bersamaan | Nomor antrean tetap unik dan berurutan |
| Dua dispense resep yang sama bersamaan | Satu `200`, satu `409`; stok hanya berkurang sekali |
| Stok obat kurang dari jumlah resep | `409`; transaction rollback dan stok tidak berubah |
| Dua payment dengan `idempotency_key` sama | Satu `201`, satu `200`; hanya satu row `payments` |
| Nominal pembayaran lebih dari sisa invoice | `422` |
| `idempotency_key` dipakai invoice lain | `409` |
| Request Node tanpa token | `401` |
| Role salah mengakses aksi lab | `403` |
| Semua seed user login | Semua `200` |
| Perubahan penting | Tercatat di `audit_logs` |

## Postman

Import `docs/postman_collection.json` untuk mengetes endpoint tanpa UI. Variable yang perlu disesuaikan:

- `yii_base_url`
- `node_base_url`
- `token`
