# Keputusan Teknis

Dokumen ini menjelaskan keputusan utama yang dipakai dalam implementasi.

## 1. Monorepo dengan modular monolith

Ketiga aplikasi (Yii, React, Node) ditempatkan dalam satu repository (monorepo) supaya mudah dirun bersama lewat Docker Compose dan perubahan lintas stack tetap dalam satu commit.

Arsitektur runtime-nya adalah **modular monolith**: Yii adalah satu service monolit yang memegang seluruh business logic inti, didampingi satu service pendukung Node untuk lab dan dashboard. Pendekatan ini dipilih bukan menggunakan microservices penuh karena:

- transaksi masih simple cukup dalam satu database,
- deployment dan debugging jauh lebih sederhana,
- pembagian modul tetap jelas dengan tanggung jawab Yii dan Node.

## 2. Yii2 sebagai backend utama

Yii2 dipakai untuk semua business logic inti karena requirement meminta Yii dan framework ini sudah menyediakan Active Record, migration, validasi model, dan autentikasi.

Keputusan ini membuat aturan seperti antrean, stok, billing, dan pembayaran tetap berada dalam satu service yang konsisten.

## 3. Node.js hanya untuk service pendukung

Node dipakai untuk laboratorium dan dashboard karena requirement meminta backend service terpisah. Node tidak mengambil alih business logic Yii.

Pembagian ini dipilih supaya:

- business logic tidak terduplikasi,
- billing tetap konsisten,
- perubahan data inti tetap lewat Yii,
- Node tetap berguna sebagai API pendukung.

Untuk lab, Yii membuat `lab_orders`, item invoice `LAB`, dan status kunjungan. Node kemudian mengelola workflow `REQUESTED → IN_PROGRESS → COMPLETED` dan menyimpan `lab_results`. Input hasil memakai upsert pada `lab_order_id` supaya retry setelah timeout tidak membuat hasil ganda.

## 4. Satu database PostgreSQL

Satu database dipakai supaya alur tetap sederhana dan konsisten. Node mendapat akses terbatas sesuai kebutuhan service nya.

Untuk production yang lebih besar, pendekatan bisa dipisah per service (microservices), tetapi untuk aplikasi ini satu database lebih mudah divalidasi dan dijelaskan.

## 5. Harga disimpan sebagai snapshot

Tarif tindakan, biaya lab, dan harga obat disalin ke `invoice_items` saat item tagihan dibuat.

Alasannya: perubahan master tarif tidak mengubah tagihan lama yang sudah dibuat.

## 6. Antrean dibuat dalam transaction

Nomor antrean diambil per poli dan tanggal dalam transaction memakai `INSERT ... ON CONFLICT ... DO UPDATE ... RETURNING` pada `queue_counters`. Pendekatan ini atomik dan tidak perlu membaca counter lalu menulisnya sebagai dua query terpisah.

Constraint unik `poli_id + visit_date + queue_number` tetap dipakai sebagai pengaman terakhir. Partial unique index pada `visits.patient_id` juga mencegah pasien punya dua kunjungan aktif.

## 7. Stok dikurangi dengan conditional update

Penyerahan obat memakai pola:

```sql
UPDATE medicines
SET stock = stock - :qty
WHERE id = :id AND stock >= :qty
```

Kalau tidak ada row yang berubah, transaksi dibatalkan. Ini mencegah stok menjadi minus saat dua transaksi berjalan bersamaan.

Resep dibuat oleh `DOKTER` setelah pemeriksaan dimulai, lalu `FARMASI` melakukan dispense. Row resep dikunci dengan `SELECT ... FOR UPDATE` sebelum status dicek, jadi dua request dispense bersamaan tidak bisa sama-sama lolos validasi `PRESCRIBED`. Semua perubahan penting berada dalam satu transaction: pengurangan stok, status item resep, status resep, status kunjungan, dan invoice item `MEDICINE`. Dispense ulang selalu ditolak `409` supaya stok tidak terpotong dua kali.

## 8. Pembayaran memakai idempotency key

Client mengirim `idempotency_key` saat membuat pembayaran. Database memberi unique constraint pada key tersebut.

Kalau request diulang karena jaringan putus, sistem mengembalikan pembayaran yang sudah ada, bukan mencatat transaksi baru. Di dalam transaction, row invoice dikunci `FOR UPDATE`, nominal dicek terhadap sisa tagihan, `paid_amount` ditambah, status invoice dihitung ulang, dan visit berubah `PAID` saat lunas.

## 9. Perubahan penting dicatat di audit log

`audit_logs` mencatat operasi penting seperti pemeriksaan, tindakan, order lab, dispense resep, invoice, item invoice, pembayaran, dan perubahan status kunjungan.

Invoice yang belum lunas masih boleh menerima item baru selama pelayanan masih berjalan, misalnya saat dokter menambah tindakan, membuat order lab, atau farmasi menyerahkan obat. Setelah status `PAID` atau `CANCELLED`, item invoice tidak boleh ditambah lagi; endpoint mengembalikan `409`. Pendekatan ini dipilih supaya implementasi tetap sederhana dan riwayat tagihan yang sudah lunas tidak berubah.

## 10. Validasi dibuat dua lapis

Input divalidasi di model Yii supaya error bisa dikembalikan dengan jelas ke client. Aturan penting tetap diperkuat di database lewat `UNIQUE`, `CHECK`, dan `FOREIGN KEY`.

Dengan pola ini, validasi model menjaga pengalaman pengguna, sedangkan constraint database menjaga konsistensi kalau ada akses di luar aplikasi.

## 11. Master data memakai satu controller CRUD bersama

Enam resource master data memakai `BaseCrudController` yang sama. Perbedaan tiap resource cukup didefinisikan lewat model, field pencarian, dan role tulis.

Pola ini dipilih supaya controller tetap tipis, response konsisten, dan logic CRUD tidak disalin enam kali. Role tulis juga tetap eksplisit: pasien boleh ditulis `ADMIN` dan `PENDAFTARAN`, sedangkan master lain hanya `ADMIN`.

No. RM pasien dibuat otomatis oleh backend saat form tidak mengirim nomor tersebut. Ini mengurangi input manual dan tetap aman karena kolomnya punya unique constraint.

## 12. ORM untuk CRUD, SQL untuk kasus khusus

CRUD master data memakai Yii `ActiveRecord` dan `ActiveQuery`. Ini membuat validasi model, relasi, pagination, dan serialization tetap konsisten.

Raw SQL tetap dipakai di bagian yang memang butuh kontrol database langsung, misalnya conditional update stok, counter antrean, partial unique index, trigram index, dan aggregate dashboard. Jadi ORM dipakai untuk produktivitas, sedangkan SQL dipakai untuk invariant dan performa.

Dashboard Node juga memakai aggregate SQL read-only. Ini sengaja dipisah dari business logic Yii supaya reporting ringan tidak ikut mengubah data inti.

## 13. UI dibuat fungsional

Frontend memakai React dan Tailwind CSS. Tailwind dipakai pada bagian yang paling terlihat seperti login, layout utama, dan dashboard. Styling lama tetap dirapikan lewat CSS bersama supaya seluruh halaman konsisten tanpa rewrite besar di akhir pengerjaan.

Fokus utama tetap alur kerja yang jelas. UI dibuat rapi dan sederhana, bukan desain yang kompleks.