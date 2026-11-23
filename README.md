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

Cara utama menjalankan aplikasi adalah Docker Compose:

- Docker Desktop
- Docker Compose
- Git

PHP 8.4+, Composer, Node.js 20+, dan PostgreSQL 15+ hanya diperlukan kalau ingin menjalankan aplikasi tanpa Docker.

## Menjalankan aplikasi

```bash
cp .env.example .env
docker compose up -d --build
docker compose exec yii composer install
docker compose exec yii php yii migrate/up --interactive=0
```

Perintah di atas menjalankan semua service: PostgreSQL, Yii API, Node service, dan React web. Dependency PHP dipasang lewat Composer di dalam container Yii.

Kalau menjalankan Docker Desktop dari WSL tanpa integrasi WSL, gunakan `docker.exe compose` sebagai pengganti `docker compose`.

Setelah service aktif:

- Yii API: `http://localhost:8081/api/ping`
- Node API: `http://localhost:3001/api/health`
- React web: `http://localhost:5173`

Akun seed untuk development memakai password `password123`:

- `admin` — role `ADMIN`
- `pendaftaran` — role `PENDAFTARAN`
- `dokter` — role `DOKTER`
- `farmasi` — role `FARMASI`
- `lab` — role `LAB`
- `kasir` — role `KASIR`

Untuk berhenti:

```bash
docker compose stop
```

## Dokumentasi

- [Arsitektur aplikasi](docs/arsitektur.md)
- [Skema database dan relasi](docs/database.md)
- [Referensi schema SQL](docs/schema.sql)
- [Daftar endpoint API](docs/api.md)
- [Postman collection](docs/postman_collection.json)
- [Panduan demo dan screenshot](docs/demo.md)
- [Keputusan teknis](docs/keputusan-teknis.md)
