# SIMRS Rawat Jalan

Aplikasi SIMRS rawat jalan untuk mengelola alur pendaftaran pasien sampai pembayaran.

## Komponen

- `backend-yii/` — Yii2 REST API dan business logic utama.
- `web/` — React + TypeScript untuk antarmuka pengguna.
- `services-node/` — Node.js service untuk laboratorium dan dashboard.
- `docs/` — dokumentasi teknis.
- PostgreSQL — database utama.

## Alur utama

1. Petugas mendaftarkan kunjungan pasien.
2. Sistem membuat nomor antrean.
3. Dokter mengisi pemeriksaan, tindakan, resep, dan permintaan lab.
4. Farmasi memproses resep dan mengurangi stok obat.
5. Kasir membuat invoice dan mencatat pembayaran.
6. Pasien atau kasir dapat mencetak bukti pembayaran.

## Kebutuhan

- PHP 8.1+
- Composer
- Node.js 20+
- PostgreSQL 15+
- Docker opsional untuk menjalankan PostgreSQL

## Menjalankan aplikasi

Perintah final akan disesuaikan setelah skeleton aplikasi dibuat.

```bash
# backend Yii2
cd backend-yii
composer install
php yii migrate/up
php yii serve --port=8080

# service Node.js
cd services-node
npm install
npm run dev

# frontend React
cd web
npm install
npm run dev
```

## Dokumentasi

- [Arsitektur aplikasi](docs/arsitektur.md)
- [Skema database dan relasi](docs/database.md)
- [Daftar endpoint API](docs/api.md)
- [Keputusan teknis](docs/keputusan-teknis.md)
