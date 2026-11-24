# Test Case Fitur Utama

Daftar skenario uji untuk alur pelayanan rawat jalan. Pengujian API dilakukan lewat Postman
(`postman_collection.json`), pengujian transaksi otomatis ada di `backend-yii/tests/Unit/TransactionTest.php`.

## Autentikasi dan hak akses

| ID | Skenario | Langkah | Ekspektasi |
|----|----------|---------|------------|
| AUTH-01 | Login berhasil | `POST /api/auth/login` dengan kredensial valid | `200`, token JWT + data user dikembalikan |
| AUTH-02 | Login gagal | Login dengan password salah | `401`, pesan error jelas |
| AUTH-03 | Akses tanpa token | `GET /api/visits` tanpa header Authorization | `401` `Login diperlukan` |
| AUTH-04 | Role tidak berhak | Token role `LAB` memanggil `POST /api/visits` | `403` `Role tidak punya akses` |
| AUTH-05 | Menu sesuai role | Login `farmasi` di UI | Hanya menu Farmasi dan Dashboard yang tampil |

## Pendaftaran dan antrean

| ID | Skenario | Langkah | Ekspektasi |
|----|----------|---------|------------|
| REG-01 | Tambah pasien | `POST /api/patients` data valid | `201`, `medical_record_no` dibuat otomatis `RM-0000xx` |
| REG-02 | Validasi pasien | `POST /api/patients` tanpa nama | `422`, error validasi per field |
| REG-03 | Buat antrean | `POST /api/visits` pasien + jadwal valid | `201`, `visit_number` dan `queue_number` terisi, status `REGISTERED` |
| REG-04 | Nomor antrean urut | Dua kunjungan di poli dan hari sama | `queue_number` berurutan, tidak ada nomor ganda |
| REG-05 | Pendaftaran ganda | `POST /api/visits` untuk pasien yang masih punya kunjungan aktif | `409` `Pasien masih punya kunjungan aktif` |
| REG-06 | Status tidak bisa mundur | `PATCH` status `BILLING` → `REGISTERED` | `409` perubahan ditolak |
| REG-07 | Set PAID manual | `PATCH` status ke `PAID` tanpa pembayaran | `409`, `PAID` hanya lewat pembayaran |

## Pemeriksaan, tindakan, dan resep

| ID | Skenario | Langkah | Ekspektasi |
|----|----------|---------|------------|
| MED-01 | Simpan pemeriksaan | `POST /api/visits/{id}/examination` role `DOKTER` | `201`, anamnesis, vital, diagnosis tersimpan |
| MED-02 | Role lain isi pemeriksaan | Request yang sama pakai token `KASIR` | `403` |
| MED-03 | Tambah tindakan | `POST /api/visits/{id}/actions` | `201`, item `ACTION` masuk invoice dengan harga snapshot |
| MED-04 | Buat resep | `POST /api/visits/{id}/prescriptions` dengan item obat | `201`, status `PRESCRIBED` |
| MED-05 | Stok tidak cukup | Resep dengan qty melebihi stok | `409` `Stok tidak cukup` |
| MED-06 | Dispense resep | `POST /api/prescriptions/{id}/dispense` role `FARMASI` | `200`, status `DISPENSED`, stok berkurang |
| MED-07 | Dispense diulang | Request dispense yang sama lagi | `409`, stok tidak berkurang dua kali |

## Laboratorium (Node.js)

| ID | Skenario | Langkah | Ekspektasi |
|----|----------|---------|------------|
| LAB-01 | Buat order lab | `POST /api/visits/{id}/lab-orders` role `DOKTER` | `201`, status `REQUESTED`, item `LAB` masuk invoice |
| LAB-02 | Lihat order | `GET /api/lab/orders` (Node) | `200`, daftar order tampil |
| LAB-03 | Proses hasil | `PATCH start` lalu `PUT result` role `LAB` | `200`, status `COMPLETED`, hasil tersimpan |
| LAB-04 | Role salah proses lab | `PUT result` pakai token `KASIR` | `403` |

## Billing dan pembayaran

| ID | Skenario | Langkah | Ekspektasi |
|----|----------|---------|------------|
| PAY-01 | Total otomatis | Buat tindakan/lab/resep | Invoice terisi `REGISTRATION` + item terkait, `total_amount` benar |
| PAY-02 | Bayar lunas | `POST /api/invoices/{id}/payments` nominal penuh | `201`, invoice `PAID`, status kunjungan `PAID` |
| PAY-03 | Bayar sebagian | Bayar nominal < sisa | Invoice `PARTIAL`, `paid_amount` bertambah |
| PAY-04 | Idempotency | Dua request bayar dengan `idempotency_key` sama | Request kedua `200` mengembalikan payment yang sama, tidak tercatat dua kali |
| PAY-05 | Nominal berlebih | Bayar > sisa tagihan | `422` `melebihi sisa tagihan` |
| PAY-06 | Bukti pembayaran | `GET /api/payments/{id}/receipt` | `200`, data struk pembayaran |
| PAY-07 | Tambah item setelah lunas | Tambah tindakan ke kunjungan yang invoice-nya `PAID` | `409`, invoice selesai tidak bisa diubah |

## Dashboard

| ID | Skenario | Langkah | Ekspektasi |
|----|----------|---------|------------|
| DASH-01 | Ringkasan | `GET /api/dashboard/summary` (Node) | `200`, `visitsToday`, `totalVisits`, `revenue`, `pendingInvoices` terisi |

## Test otomatis

| Test | File | Yang diverifikasi |
|------|------|-------------------|
| `testQueueNumberIncrementsPerPoliPerDay` | `tests/Unit/TransactionTest.php` | Nomor antrean berurutan per poli per hari |
| `testDuplicateActiveVisitIsRejected` | sama | Pendaftaran ganda pasien aktif ditolak |
| `testPaymentWithSameKeyIsNotDuplicated` | sama | Pembayaran dengan key sama tidak ganda, invoice dan kunjungan `PAID` |
| `testDispenseFailsWhenStockRunsOut` | sama | Dispense gagal saat stok habis, resep tetap `PRESCRIBED` |

Jalankan dengan:

```bash
docker compose exec yii vendor/bin/codecept run Unit
```
