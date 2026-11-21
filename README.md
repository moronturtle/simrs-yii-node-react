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

- PHP 8.4+
- Composer
- Node.js 20+
- PostgreSQL 15+
- Docker opsional untuk menjalankan PostgreSQL

## Menjalankan aplikasi

```bash
cp .env.example .env
docker compose up -d db
docker compose up -d --build
docker compose exec yii php yii migrate/up --interactive=0
```

Kalau menjalankan Docker Desktop dari WSL tanpa integrasi WSL, gunakan `docker.exe compose` sebagai pengganti `docker compose`.

Setelah service aktif:

- Yii API: `http://localhost:8080/api/ping`
- Node API: `http://localhost:3000/api/health`
- React web: `http://localhost:5173`

## Dokumentasi

- [Arsitektur aplikasi](docs/arsitektur.md)
- [Skema database dan relasi](docs/database.md)
- [Daftar endpoint API](docs/api.md)
- [Keputusan teknis](docs/keputusan-teknis.md)
