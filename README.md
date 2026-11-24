# SIMRS Rawat Jalan

Aplikasi SIMRS rawat jalan sederhana. Alurnya mulai dari pendaftaran pasien sampai pembayaran.

## Komponen

- `backend-yii/` — REST API Yii2 dan logic utama.
- `web/` — frontend React + TypeScript.
- `services-node/` — service Node.js untuk lab dan dashboard.
- `docs/` — dokumentasi.
- PostgreSQL — database utama.

## Alur utama

1. Petugas mendaftarkan kunjungan pasien.
2. Sistem membuat nomor antrean.
3. Dokter mengisi pemeriksaan, tindakan, resep, dan permintaan lab.
4. Farmasi memproses resep dan mengurangi stok obat.
5. Sistem membuat invoice; kasir mencatat pembayaran.
6. Kasir dapat membuka bukti pembayaran.

## Kebutuhan

Cara paling gampang menjalankan aplikasi adalah lewat Docker Compose:

- Docker Desktop
- Docker Compose
- Git

PHP 8.4+, Composer, Node.js 20+, dan PostgreSQL 15+ hanya dipakai kalau mau menjalankan aplikasi tanpa Docker.

## Menjalankan aplikasi

```bash
cp .env.example .env
docker compose up -d --build
docker compose exec yii composer install
docker compose exec yii php yii migrate/up --interactive=0
```

Perintah di atas menyalakan PostgreSQL, Yii API, Node service, dan React web. Dependency PHP di-install lewat Composer di dalam container Yii.

Kalau Docker Desktop dipakai dari WSL tapi integrasi WSL belum aktif, ganti `docker compose` menjadi `docker.exe compose`.

Kalau sudah jalan:

- Yii API: `http://localhost:8081/api/ping`
- Node API: `http://localhost:3001/api/health`
- React web: `http://localhost:5173`

Akun demo memakai password `password123`. Yang dimasukkan ke form login adalah username di kolom pertama:

| Username | Role |
|----------|------|
| `admin` | `ADMIN` |
| `pendaftaran` | `PENDAFTARAN` |
| `dokter` | `DOKTER` |
| `farmasi` | `FARMASI` |
| `lab` | `LAB` |
| `kasir` | `KASIR` |

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
