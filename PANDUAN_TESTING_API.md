# Panduan Pengujian API - SaaS Barbershop

**Base URL:** `http://127.0.0.1:8000/api`
**Dokumentasi Interaktif (Scramble):** `http://127.0.0.1:8000/docs/api`
**Password semua akun demo:** `password`

---

## Cara Menggunakan Token (Autentikasi)

Semua endpoint yang memerlukan login menggunakan **Bearer Token**.

1. Lakukan request login sesuai role.
2. Copy nilai `token` dari response JSON.
3. Di setiap request selanjutnya, tambahkan Header:
   ```
   Authorization: Bearer {token_anda}
   Accept: application/json
   ```
4. Di Scramble (docs), klik tombol **gembok (Authorize)** di pojok kanan atas, lalu paste token Anda.

---

## ROLE 1: DEVELOPER

**Login:**
```
POST /auth/login/developer
Body: { "email": "dev@admin.com", "password": "password" }
```

Developer adalah pengelola platform SaaS. Ia tidak membuat barbershop, namun mengawasi seluruh ekosistem sistem.

---

### Langkah 1.1 — Lihat Dashboard Sistem

```
GET /developer/dashboard
```

**Tidak perlu Body.** Respons akan menampilkan statistik global:

```json
{
  "total_owners": 1,
  "total_branches": 3,
  "total_transactions": 10,
  "total_system_fee_collected": 20000
}
```

> **Catatan:** `total_system_fee_collected` adalah total pendapatan Developer dari biaya per transaksi.

---

### Langkah 1.2 — Lihat Daftar Owner yang Mendaftar

```
GET /developer/owners
```

Respons berupa daftar semua akun dengan role `owner`. Perhatikan field `status`:
- `pending` → Owner baru daftar, belum bisa login ke aplikasi Owner.
- `active` → Owner sudah disetujui dan bisa menggunakan sistem.

---

### Langkah 1.3 — Setujui Pendaftaran Owner

Setelah Owner mendaftar (status `pending`), Developer harus menyetujuinya.

```
PUT /developer/owners/{id}/approve
```

- Ganti `{id}` dengan ID Owner yang ingin disetujui (didapat dari Langkah 1.2).
- Tidak perlu Body.
- Sistem akan otomatis mengaktifkan akun dan menginisialisasi saldo deposit Owner menjadi Rp 0.

---

### Langkah 1.4 — Atur Biaya Sistem (Fee per Transaksi)

Developer bisa mengubah besaran biaya yang dipotong dari saldo Owner setiap kali ada transaksi di kasir.

```
PUT /developer/system-fee
Body (JSON):
{
  "fee": 2000
}
```

> **Contoh:** Jika diset `2000`, maka setiap kali Kasir mencatat transaksi, saldo Owner akan berkurang Rp 2.000 sebagai biaya penggunaan sistem SaaS.

---

### Langkah 1.5 — Lihat Permintaan Top-up Saldo

Owner tidak bisa langsung menambah saldo sendiri. Mereka harus *request* ke Developer.

```
GET /developer/deposits/requests
```

Respons berupa daftar request top-up dengan status `pending`.

---

### Langkah 1.6 — Setujui atau Tolak Top-up

Setelah memverifikasi pembayaran (di dunia nyata via transfer bank), Developer menyetujui request:

```
PUT /developer/deposits/requests/{id}/approve
```
```
PUT /developer/deposits/requests/{id}/reject
```

- Ganti `{id}` dengan ID request deposit.
- Tidak perlu Body.
- Jika **approve**: saldo Owner otomatis bertambah sesuai jumlah yang diminta.
- Jika **reject**: status menjadi `rejected`, saldo tidak berubah.

---

### Langkah 1.7 — Lihat Riwayat Transaksi Sistem (Pendapatan SaaS)

```
GET /developer/transactions
```

Menampilkan daftar seluruh pemotongan saldo Owner akibat biaya sistem. Ini adalah catatan **pendapatan Developer** dari platform SaaS.

---

## ROLE 2: OWNER

**Login:**
```
POST /auth/login/owner
Body: { "email": "owner@barber.com", "password": "password" }
```

> **PENTING:** Sebelum bisa login, pastikan akun Owner sudah disetujui oleh Developer (status `active`).

Owner adalah pemilik bisnis barbershop. Ia bertugas menyiapkan seluruh konfigurasi bisnis sebelum operasional bisa berjalan.

---

### Langkah 2.1 — Kelola Profil

**Lihat Profil:**
```
GET /owner/profile
```

**Update Profil:**
```
PUT /owner/profile
Body (JSON):
{
  "name": "Budi Barbershop Owner",
  "email": "owner@barber.com",
  "password": "password_baru",
  "password_confirmation": "password_baru"
}
```
> Semua field bersifat opsional. Kirim hanya field yang ingin diubah.

---

### Langkah 2.2 — Buat Cabang Barbershop

Langkah **wajib pertama** sebelum menambah karyawan atau produk.

**Lihat Semua Cabang:**
```
GET /owner/branches
```

**Tambah Cabang Baru:**
```
POST /owner/branches
Body (JSON):
{
  "name": "Barber King Pusat",
  "address": "Jl. Sudirman No. 1"
}
```
> Catat `id` dari respons. ID ini dibutuhkan untuk mendaftarkan karyawan dan produk.

**Update Cabang:**
```
PUT /owner/branches/{id}
Body (JSON): { "name": "...", "address": "..." }
```

**Hapus Cabang:**
```
DELETE /owner/branches/{id}
```

---

### Langkah 2.3 — Buat Layanan (Jasa)

Layanan bersifat **universal** — berlaku di semua cabang milik Owner ini.

**Lihat Semua Layanan:**
```
GET /owner/services
```

**Tambah Layanan:**
```
POST /owner/services
Body (JSON):
{
  "name": "Gunting Rambut Dewasa",
  "price": 35000,
  "commission_type": "fixed",
  "commission_amount": 5000
}
```
> - `commission_type`: `fixed` (nominal tetap) atau `percentage` (persentase dari harga).
> - `commission_amount`: Jika `fixed` isi nominalnya (contoh: `5000`). Jika `percentage` isi persentasenya (contoh: `15` untuk 15%).

**Update Layanan:**
```
PUT /owner/services/{id}
Body (JSON): { field yang diubah }
```

**Hapus Layanan:**
```
DELETE /owner/services/{id}
```

---

### Langkah 2.4 — Buat Produk (Barang)

Produk bersifat **per cabang** — stok di tiap cabang dikelola secara independen.

**Lihat Semua Produk (seluruh cabang):**
```
GET /owner/products
```

**Tambah Produk:**
```
POST /owner/products
Body (JSON):
{
  "branch_id": 1,
  "name": "Pomade Waterbased",
  "price": 45000,
  "stock": 20
}
```
> `branch_id` wajib diisi. Pastikan ID cabang sudah dibuat terlebih dahulu (Langkah 2.2).

**Update Produk:**
```
PUT /owner/products/{id}
Body (JSON): { field yang diubah }
```

**Hapus Produk:**
```
DELETE /owner/products/{id}
```

---

### Langkah 2.5 — Rekrut Karyawan

Menambahkan akun Kasir atau Staff yang bekerja di cabang tertentu.

**Lihat Semua Karyawan:**
```
GET /owner/employees
```

**Tambah Karyawan:**
```
POST /owner/employees
Body (JSON):
{
  "branch_id": 1,
  "name": "Siti Kasir",
  "email": "siti@barber.com",
  "password": "password",
  "role": "kasir",
  "daily_base_salary": 45000
}
```
> - `role`: harus `kasir` atau `staff`.
> - `daily_base_salary`: Gaji pokok per hari (basis perhitungan gaji bulanan dari absensi).
> - Ulangi dengan `role: "staff"` untuk mendaftarkan Barberman/Staff.

---

### Langkah 2.6 — Kelola Saldo Deposit SaaS

**Cek Saldo Saat Ini:**
```
GET /owner/deposits
```

**Request Top-up Saldo ke Developer:**
```
POST /owner/deposits/topup
Body (JSON):
{
  "amount": 500000,
  "description": "Topup via Transfer Bank"
}
```
> Setelah request dikirim, saldo **belum** bertambah. Developer harus menyetujuinya terlebih dahulu (Langkah 1.6). Jika saldo habis, kasir tidak bisa memproses transaksi.

---

### Langkah 2.7 — Pantau Laporan Operasional

**Laporan Penjualan (semua transaksi):**
```
GET /owner/reports/sales
```

**Laporan Performa Layanan Jasa:**
```
GET /owner/reports/services
```

**Laporan Penggajian Karyawan:**
```
GET /owner/reports/payrolls
```
> Menampilkan rekap absensi dan gaji pokok harian seluruh karyawan di cabang-cabang milik Owner.

---

## ROLE 3: KASIR

**Login:**
```
POST /auth/login/kasir
Body: { "email": "kasir@barber.com", "password": "password" }
```

> **PENTING:** Akun kasir dibuat oleh Owner (Langkah 2.5). Pastikan Owner sudah membuat akun kasir dan menentukan cabang tempat kasir bekerja.

---

### Langkah 3.1 — Absen Masuk (Check-in)

Lakukan **pertama kali** setiap hari sebelum mulai bekerja.

```
POST /kasir/attendance/check-in
```
Tidak perlu Body. Sistem otomatis mencatat:
- Waktu check-in sekarang.
- Mengunci nominal gaji pokok harian (`daily_base_salary`) ke kolom `daily_base_salary_earned`.

> **Validasi:** Jika sudah check-in hari ini, sistem akan menolak dengan pesan error 400.

---

### Langkah 3.2 — Lihat Daftar Layanan & Produk (Untuk Transaksi)

Sebelum mencatat transaksi, kasir perlu mengetahui ID layanan dan produk yang tersedia.

**Lihat Layanan yang Tersedia:**
```
GET /kasir/services
```
> Menampilkan daftar jasa milik Owner dari cabang kasir bekerja, lengkap dengan ID, harga, dan info komisi.

**Lihat Produk di Cabang Ini:**
```
GET /kasir/products
```
> Menampilkan daftar produk khusus cabang ini beserta **stok saat ini**.

**Lihat Staff yang Bertugas Hari Ini:**
```
GET /kasir/available-staff
```
> Menampilkan daftar staff di cabang yang sama. ID staff diperlukan untuk mencatat siapa yang melayani pelanggan.

---

### Langkah 3.3 — Kelola Data Pelanggan

**Cari/Lihat Daftar Pelanggan:**
```
GET /kasir/customers
```

**Daftarkan Pelanggan Walk-in (Baru):**
Jika pelanggan belum punya akun, kasir bisa mendaftarkannya secara cepat.
```
POST /kasir/customers
Body (JSON):
{
  "name": "Pelanggan Baru",
  "email": "pelanggan_baru@email.com"
}
```
> Password default untuk pelanggan walk-in adalah `pelanggan123`. Pelanggan bisa mengubahnya sendiri nanti via aplikasi Customer.

---

### Langkah 3.4 — Catat Transaksi (Inti Proses Bisnis)

Ini adalah endpoint terpenting di seluruh sistem.

```
POST /kasir/transactions
Body (JSON):
{
  "customer_id": 6,
  "payment_method": "cash",
  "items": [
    {
      "type": "service",
      "id": 1,
      "staff_id": 4
    },
    {
      "type": "product",
      "id": 1
    }
  ]
}
```

**Penjelasan Field:**
| Field | Wajib | Keterangan |
|---|---|---|
| `customer_id` | Tidak | ID pelanggan. Isi `null` jika pelanggan tidak mau didata. |
| `payment_method` | Ya | Metode bayar: `cash`, `transfer`, `qris`, dll. |
| `items` | Ya | Array item yang dibeli/digunakan. |
| `items.*.type` | Ya | Tipe item: `service` atau `product`. |
| `items.*.id` | Ya | ID layanan atau ID produk. |
| `items.*.staff_id` | Ya (jika service) | ID staff yang melayani. Wajib jika tipe `service`. |

**Yang Terjadi Saat Transaksi Berhasil:**
1. Total harga dihitung otomatis dari seluruh item.
2. Stok produk yang dibeli berkurang otomatis.
3. Komisi staff terkunci dan tersimpan di database.
4. Saldo deposit Owner **dipotong** sebesar biaya sistem (default Rp 2.000) yang ditentukan Developer.
5. Riwayat transaksi tersimpan dan bisa dilihat pelanggan.

> **Error 402:** Muncul jika saldo deposit Owner tidak cukup untuk membayar biaya sistem. Owner harus top-up terlebih dahulu.

---

### Langkah 3.5 — Catat Restok Barang

Saat ada barang masuk ke cabang, kasir mencatat penambahan stok.

```
POST /kasir/restocks
Body (JSON):
{
  "product_id": 1,
  "qty": 10
}
```
> Stok produk akan bertambah otomatis sebesar `qty` yang diinput.

---

### Langkah 3.6 — Lihat Laporan Transaksi

**Laporan Penjualan Produk (Barang):**
```
GET /kasir/reports/sales
```

**Laporan Jasa Layanan:**
```
GET /kasir/reports/services
```

---

### Langkah 3.7 — Rekap Gaji Bulan Ini

```
GET /kasir/salary-summary
```

Respons:
```json
{
  "month": "May 2026",
  "total_base_salary": 135000,
  "take_home_pay": 135000
}
```
> Dihitung dari akumulasi `daily_base_salary_earned` pada setiap hari di bulan ini di mana kasir melakukan check-in.

---

### Langkah 3.8 — Absen Pulang (Check-out)

Lakukan di akhir jam kerja.

```
POST /kasir/attendance/check-out
```
Tidak perlu Body. Sistem mencatat waktu check-out sekarang.

> **Validasi:** Tidak bisa check-out jika belum check-in, dan tidak bisa check-out dua kali sehari.

**Lihat Riwayat Absensi:**
```
GET /kasir/attendance/history
```

---

## ROLE 4: STAFF

**Login:**
```
POST /auth/login/staff
Body: { "email": "staff@barber.com", "password": "password" }
```

> **PENTING:** Akun staff dibuat oleh Owner (Langkah 2.5).

---

### Langkah 4.1 — Absen Masuk (Check-in)

Lakukan **pertama kali** setiap hari sebelum mulai melayani pelanggan.

```
POST /staff/attendance/check-in
```
Tidak perlu Body. Sistem mencatat waktu dan mengunci gaji harian.

> **Validasi:** Hanya bisa check-in sekali sehari.

---

### Langkah 4.2 — Lihat & Update Profil

**Lihat Profil:**
```
GET /staff/profile
```

**Update Profil:**
```
PUT /staff/profile
Body (JSON):
{
  "name": "Joni Barberman Updated",
  "email": "joni@barber.com",
  "password": "password_baru",
  "password_confirmation": "password_baru"
}
```
> Semua field bersifat opsional.

---

### Langkah 4.3 — Upload Foto Hasil Cukuran ke Galeri Pelanggan

Setelah selesai melayani pelanggan dan pelanggan sudah membayar di kasir, staff mendokumentasikan hasil kerjanya.

**Langkah Persiapan:** Cari tahu ID Pelanggan yang baru dilayani.
```
GET /staff/customers
```

**Upload Foto:**
```
POST /staff/galleries
Content-Type: multipart/form-data

Form fields:
  customer_id : 6
  transaction_id : 2   (opsional, ID transaksi yang berkaitan)
  image : [pilih file gambar .jpg/.png, maks 2MB]
```

> **Penting:** Endpoint ini menggunakan format `multipart/form-data` (bukan JSON), karena ada upload file gambar. Di Postman, gunakan tab **Body → form-data**.

> Foto yang berhasil diupload akan otomatis muncul di galeri pelanggan saat pelanggan membuka `GET /customer/galleries`.

---

### Langkah 4.4 — Lihat Riwayat Absensi

```
GET /staff/attendance/history
```

Menampilkan seluruh catatan kehadiran staff dari waktu ke waktu.

---

### Langkah 4.5 — Lihat Rekap Gaji & Komisi Bulan Ini

```
GET /staff/salary-summary
```

Respons:
```json
{
  "month": "May 2026",
  "total_base_salary": 150000,
  "total_commissions": 25000,
  "take_home_pay": 175000
}
```

> - `total_base_salary`: Dari jumlah hari check-in × gaji pokok harian.
> - `total_commissions`: Akumulasi komisi dari seluruh transaksi jasa yang ditangani bulan ini.
> - `take_home_pay`: Total yang akan diterima.

---

### Langkah 4.6 — Absen Pulang (Check-out)

```
POST /staff/attendance/check-out
```
> Tidak bisa check-out jika belum check-in hari ini, atau jika sudah check-out sebelumnya.

---

## ROLE 5: CUSTOMER (PELANGGAN)

**Daftar Akun Baru:**
```
POST /auth/register-customer
Body (JSON):
{
  "name": "Rizky Pelanggan",
  "email": "rizky@email.com",
  "password": "password",
  "password_confirmation": "password"
}
```

**Login:**
```
POST /auth/login/customer
Body: { "email": "test@user.com", "password": "password" }
```

---

### Langkah 5.1 — Lihat Profil & Update

**Lihat Profil:**
```
GET /customer/profile
```

**Update Profil:**
```
PUT /customer/profile
Body (JSON):
{
  "name": "Nama Baru",
  "email": "email_baru@gmail.com"
}
```

---

### Langkah 5.2 — Cari & Pilih Cabang Barbershop

**Lihat Semua Cabang yang Tersedia:**
```
GET /customer/branches
```
Respons berupa daftar seluruh cabang dari semua owner yang ada di sistem. Catat `id` cabang yang ingin dikunjungi.

---

### Langkah 5.3 — Lihat Katalog Layanan & Produk di Cabang

```
GET /customer/branches/{id}/catalog
```
Ganti `{id}` dengan ID cabang yang dipilih dari Langkah 5.2.

Respons berisi tiga bagian sekaligus:
```json
{
  "branch": { "id": 1, "name": "Barber King Pusat", ... },
  "services": [
    { "id": 1, "name": "Gunting Rambut Dewasa", "price": "35000.00" },
    ...
  ],
  "products": [
    { "id": 1, "name": "Pomade Waterbased", "price": "45000.00", "stock": 18 },
    ...
  ]
}
```
> Pelanggan bisa mengetahui jasa apa yang tersedia dan produk apa yang dijual di cabang tersebut sebelum datang.

---

### Langkah 5.4 — Lihat Riwayat Kunjungan

Setelah pernah bertransaksi di barbershop:
```
GET /customer/visit-history
```

Respons menampilkan seluruh transaksi pelanggan, lengkap dengan detail:
- Cabang tempat bertransaksi.
- Daftar item (jasa dan/atau produk) yang dibeli.
- Staff yang melayani (untuk jasa).
- Total pembayaran.

---

### Langkah 5.5 — Lihat Galeri Foto Hasil Cukuran

```
GET /customer/galleries
```

Menampilkan seluruh foto dokumentasi hasil potongan rambut yang pernah diunggah oleh staff untuk pelanggan ini. Foto diupload oleh staff via `POST /staff/galleries`.

---

## Ringkasan Alur Bisnis End-to-End

```
[Developer] Setujui Owner → Setujui Top-up Saldo
     ↓
[Owner] Buat Cabang → Tambah Layanan → Tambah Produk → Rekrut Kasir & Staff
     ↓
[Kasir/Staff] Check-in Absen
     ↓
[Customer] Lihat Katalog → Datang ke Barbershop
     ↓
[Staff] Melayani pelanggan (potong rambut)
     ↓
[Kasir] Input Transaksi → Sistem potong saldo Owner (SaaS Fee)
     ↓
[Staff] Upload foto hasil cukuran → Masuk galeri pelanggan
     ↓
[Customer] Lihat riwayat kunjungan & galeri foto
     ↓
[Kasir/Staff] Check-out Absen
     ↓
[Owner] Pantau laporan penjualan, layanan & penggajian
     ↓
[Developer] Monitor transaksi sistem & pendapatan SaaS fee
```

---

## Akun Demo (Setelah `php artisan migrate:fresh --seed`)

| Role | Email | Password |
|---|---|---|
| Developer | dev@admin.com | password |
| Owner | owner@barber.com | password |
| Kasir | kasir@barber.com | password |
| Staff | staff@barber.com | password |
| Customer | test@user.com | password |

---

## Perintah Berguna

```bash
# Reset database + isi data demo
php artisan migrate:fresh --seed

# Aktifkan storage untuk akses gambar galeri
php artisan storage:link

# Bersihkan cache (jika ada perubahan routing/config)
php artisan optimize:clear

# Jalankan server lokal
php artisan serve
```
