# Keputusan Teknis

Dokumen ini menjelaskan keputusan utama yang dipakai dalam implementasi.

## 1. Yii2 sebagai backend utama

Yii2 dipakai untuk semua business logic inti karena requirement meminta Yii dan framework ini sudah menyediakan Active Record, migration, validasi model, dan autentikasi.

Keputusan ini membuat aturan penting seperti antrean, stok, billing, dan pembayaran tetap berada dalam satu service yang konsisten.

## 2. Node.js hanya untuk service pendukung

Node dipakai untuk laboratorium dan dashboard karena requirement meminta backend service terpisah. Node tidak mengambil alih business logic Yii.

Pembagian ini dipilih supaya:

- business logic tidak terduplikasi,
- billing tetap konsisten,
- perubahan data inti tetap lewat Yii,
- Node tetap berguna sebagai API pendukung.

Untuk lab, Yii membuat `lab_orders`, item invoice `LAB`, dan status kunjungan. Node kemudian mengelola workflow `REQUESTED → IN_PROGRESS → COMPLETED` dan menyimpan `lab_results`. Input hasil memakai upsert pada `lab_order_id` supaya retry setelah timeout tidak membuat hasil ganda.

## 3. Satu database PostgreSQL

Satu database dipakai supaya alur tetap sederhana dan konsisten. Node mendapat akses terbatas sesuai kebutuhan service-nya.

Untuk production yang lebih besar, pendekatan bisa dipisah per service, tetapi untuk aplikasi ini satu database lebih mudah divalidasi dan dijelaskan.

## 4. Harga disimpan sebagai snapshot

Tarif tindakan, biaya lab, dan harga obat disalin ke `invoice_items` saat item tagihan dibuat.

Alasannya: perubahan master tarif tidak mengubah tagihan lama yang sudah dibuat.

## 5. Antrean dibuat dalam transaction

Nomor antrean diambil per poli dan tanggal dalam transaction memakai `INSERT ... ON CONFLICT ... DO UPDATE ... RETURNING` pada `queue_counters`. Pendekatan ini atomik dan tidak perlu membaca counter lalu menulisnya sebagai dua query terpisah.

Constraint unik `poli_id + visit_date + queue_number` tetap dipakai sebagai pengaman terakhir. Partial unique index pada `visits.patient_id` juga mencegah pasien punya dua kunjungan aktif.

## 6. Stok dikurangi dengan conditional update

Penyerahan obat memakai pola:

```sql
UPDATE medicines
SET stock = stock - :qty
WHERE id = :id AND stock >= :qty
```

Kalau tidak ada row yang berubah, transaksi dibatalkan. Ini mencegah stok menjadi minus saat dua transaksi berjalan bersamaan.

Resep dibuat oleh `DOKTER` setelah pemeriksaan dimulai, lalu `FARMASI` melakukan dispense. Semua perubahan penting berada dalam satu transaction: pengurangan stok, status item resep, status resep, status kunjungan, dan invoice item `MEDICINE`. Dispense ulang selalu ditolak `409` supaya stok tidak terpotong dua kali.

## 7. Pembayaran memakai idempotency key

Client mengirim `idempotency_key` saat membuat pembayaran. Database memberi unique constraint pada key tersebut.

Kalau request diulang karena jaringan putus, sistem mengembalikan pembayaran yang sudah ada, bukan mencatat transaksi baru. Di dalam transaction, row invoice dikunci `FOR UPDATE`, nominal dicek terhadap sisa tagihan, `paid_amount` ditambah, status invoice dihitung ulang, dan visit berubah `PAID` saat lunas.

## 8. Perubahan setelah billing dicatat di audit log

Kalau diagnosis atau tindakan berubah setelah invoice dibuat:

- invoice belum lunas boleh dihitung ulang,
- invoice sudah lunas memakai adjustment,
- semua perubahan penting dicatat di `audit_logs`.

## 9. Validasi dibuat dua lapis

Input divalidasi di model Yii supaya error bisa dikembalikan dengan jelas ke client. Aturan penting tetap diperkuat di database lewat `UNIQUE`, `CHECK`, dan `FOREIGN KEY`.

Dengan pola ini, validasi model menjaga pengalaman pengguna, sedangkan constraint database menjaga konsistensi kalau ada akses di luar aplikasi.

## 10. Master data memakai pola CRUD generik

Enam resource master data memakai `BaseCrudController` yang sama. Perbedaan tiap resource cukup didefinisikan lewat model, field pencarian, dan role tulis.

Pola ini dipilih supaya controller tetap tipis, response konsisten, dan logic CRUD tidak disalin enam kali. Role tulis juga tetap eksplisit: pasien boleh ditulis `ADMIN` dan `PENDAFTARAN`, sedangkan master lain hanya `ADMIN`.

## 11. ORM untuk CRUD, SQL untuk kasus khusus

CRUD master data memakai Yii `ActiveRecord` dan `ActiveQuery`. Ini membuat validasi model, relasi, pagination, dan serialization tetap konsisten.

Raw SQL tetap dipakai di bagian yang memang butuh kontrol database langsung, misalnya conditional update stok, counter antrean, partial unique index, trigram index, dan aggregate dashboard. Jadi ORM dipakai untuk produktivitas, sedangkan SQL dipakai untuk invariant dan performa.

Dashboard Node juga memakai aggregate SQL read-only. Ini sengaja dipisah dari business logic Yii supaya reporting ringan tidak ikut mengubah data inti.

## 12. UI dibuat fungsional

Fokus utama adalah alur kerja yang jelas. UI dibuat rapi dan sederhana, bukan desain kompleks, supaya waktu lebih banyak dipakai untuk correctness dan transaction safety.
