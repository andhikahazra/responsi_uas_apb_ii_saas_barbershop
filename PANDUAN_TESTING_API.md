# Panduan Pengujian API - SaaS Barbershop

Dokumen ini adalah panduan langkah demi langkah (Skenario Pengujian) untuk memahami alur kerja backend API SaaS Barbershop. Sistem ini memiliki 5 peran (Role) utama: **Developer, Owner, Kasir, Staff, dan Customer**.

**Base URL API:** `http://127.0.0.1:8000/api`
**Dokumentasi Swagger/Scramble:** `http://127.0.0.1:8000/docs/api`

---

## Aturan Umum (Penting!)
1. **Autentikasi:** Hampir seluruh endpoint membutuhkan Token. Anda harus melakukan `POST /auth/login/...` terlebih dahulu. Copy token yang didapat, lalu masukkan sebagai **Bearer Token** di Header (atau klik tombol "Authorize" bergembok di halaman dokumentasi Scramble).
2. **Format Data:** Pastikan mengirimkan data dengan header `Accept: application/json`.
3. **Data Dummy:** Gunakan `php artisan migrate:fresh --seed` untuk mereset database dengan data sampel yang sudah siap pakai.

---

## Skenario Alur Pengujian (Testing Flow)

Agar aplikasi dapat berjalan secara logis, mahasiswa **WAJIB** melakukan pengujian dengan urutan skenario berikut:

### FASE 1: Registrasi & Persetujuan Sistem (Developer & Owner)
Fase ini menyimulasikan Owner yang baru bergabung dengan platform SaaS.

1. **Owner Mendaftar:**
   - Endpoint: `POST /auth/register-owner`
   - Body: `name`, `email`, `password`, `password_confirmation`.
   - *Catatan:* Akun yang baru dibuat berstatus `pending` dan belum bisa login.
2. **Developer Menyetujui:**
   - Login sebagai Developer (`POST /auth/login/developer` menggunakan `dev@admin.com`).
   - Cek daftar owner: `GET /developer/owners`. Cari ID owner yang baru mendaftar.
   - Setujui owner: `PUT /developer/owners/{id}/approve`.
3. **Owner Top-up Saldo SaaS (Penting!):**
   - Login sebagai Owner (`POST /auth/login/owner`).
   - Request Top-up: `POST /owner/deposits/topup` (Misal: Rp 500.000).
   - Login kembali sebagai Developer, setujui top-up tersebut melalui `PUT /developer/deposits/requests/{id}/approve`.
   - *Kenapa ini penting?* Karena setiap transaksi kasir nantinya akan memotong saldo Owner. Jika saldo 0, kasir tidak bisa memproses transaksi pelanggan.

### FASE 2: Setup Master Data oleh Owner
Setelah akun aktif dan punya saldo, Owner harus mengatur bisnisnya. Login sebagai **Owner** dan lakukan secara berurutan:

1. **Buat Cabang:** `POST /owner/branches` (Isi nama cabang dan alamat). Catat ID Cabang yang didapat.
2. **Buat Layanan (Universal):** `POST /owner/services`.
   - Layanan ini berlaku di semua cabang. Contoh: "Gunting Rambut", Harga: 35000.
   - Jangan lupa set komisi untuk staff (misal: `fixed`, `5000`).
3. **Buat Produk (Per Cabang):** `POST /owner/products`.
   - Pilih `branch_id` yang baru dibuat. Tambahkan produk seperti Pomade beserta stok awalnya.
4. **Rekrut Karyawan (Kasir & Staff):** `POST /owner/employees`.
   - Buat 1 akun dengan role `kasir`.
   - Buat 1 akun dengan role `staff`.
   - Tentukan `daily_base_salary` (gaji pokok harian) dan wajib masukkan `branch_id` tempat mereka bekerja.

### FASE 3: Operasional Harian Karyawan (Staff & Kasir)
Mensimulasikan karyawan yang datang ke barbershop dan mulai bekerja.

1. **Absensi (Check-in):**
   - Login sebagai **Kasir** -> Hit `POST /kasir/attendance/check-in`.
   - Login sebagai **Staff** -> Hit `POST /staff/attendance/check-in`.
   - *Catatan:* Check-in ini yang akan menjadi dasar perhitungan Gaji Pokok di akhir bulan.

### FASE 4: Interaksi Pelanggan & Transaksi Kasir
Mensimulasikan pelanggan yang datang untuk potong rambut.

1. **Pelanggan Cek Katalog (Customer):**
   - Login sebagai **Customer** (atau daftar via `POST /auth/register-customer`).
   - Lihat daftar cabang: `GET /customer/branches`.
   - Lihat menu jasa & produk di cabang tujuan: `GET /customer/branches/{id}/catalog`.
2. **Pelayanan Selesai & Pembayaran (Kasir):**
   - Pelanggan selesai dicukur oleh Staff. Menuju meja Kasir.
   - Login sebagai **Kasir**.
   - (Opsional) Jika pelanggan tidak punya akun, Kasir mendaftarkan cepat via `POST /kasir/customers` (Walk-in).
   - Buat Transaksi: `POST /kasir/transactions`.
     - Masukkan `customer_id` (bisa null jika pelanggan tidak mau didata).
     - Masukkan `items` berupa array (Pilih jasa gunting rambut dan assign `staff_id` yang mencukur agar staff dapat komisi).
     - Tambahkan produk jika pelanggan membeli pomade.
   - *Sistem di balik layar:* Memotong stok pomade, mencatat komisi staff, dan memotong saldo deposit SaaS milik Owner (misal: Rp 2.000).

### FASE 5: Dokumentasi Hasil Cukur (Staff)
Setelah pelanggan membayar, Staff memfoto hasil potongannya.

1. **Upload Galeri:**
   - Login sebagai **Staff**.
   - Cek ID Pelanggan via `GET /staff/customers`.
   - Upload foto hasil kerja: `POST /staff/galleries` (masukkan `customer_id` dan file gambar).
2. **Pelanggan Melihat Hasil:**
   - Login kembali sebagai **Customer**.
   - Cek `GET /customer/galleries` atau `GET /customer/visit-history` untuk melihat dokumentasi potongan rambut.

### FASE 6: Laporan Akhir Bulan (Owner, Staff, Developer)
Fase evaluasi keuangan dan kinerja.

1. **Staff & Kasir Cek Gaji:**
   - `GET /staff/salary-summary` atau `GET /kasir/salary-summary`.
   - Akan menampilkan Gaji Pokok (berdasarkan jumlah check-in harian) + Total Komisi (untuk staff).
2. **Owner Cek Laporan:**
   - Penjualan: `GET /owner/reports/sales`.
   - Performa Layanan: `GET /owner/reports/services`.
   - Beban Gaji Karyawan: `GET /owner/reports/payrolls`.
3. **Developer Cek Pemasukan Sistem:**
   - Cek Dashboard: `GET /developer/dashboard`.
   - Cek detail potongan fee dari seluruh owner: `GET /developer/transactions`.

---

## Tips Pengujian untuk Mahasiswa
- **Gunakan Postman / Insomnia:** Jika Scramble "Try It" dirasa kurang nyaman untuk upload file gambar (Galeri), gunakan Postman.
- **Baca Error Message:** Sistem ini dirancang untuk memberikan error 400/422 jika aturan bisnis dilanggar (contoh: stok produk habis, saldo owner tidak cukup untuk bayar fee sistem, absen check-in dua kali sehari). Pastikan membaca pesan error-nya.
- **Perhatikan ID:** Selalu catat ID dari data yang baru dibuat (ID cabang, ID user, ID produk) karena akan digunakan sebagai parameter di endpoint selanjutnya.
