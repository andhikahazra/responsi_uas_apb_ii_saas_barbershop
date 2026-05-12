# Panduan Pengujian API - SaaS Barbershop

**Base URL:** `http://127.0.0.1:8000/api`
**Dokumentasi Interaktif (Scramble):** `http://127.0.0.1:8000/docs/api`
**Password semua akun demo:** `password`

---

## Cara Menggunakan Token (Autentikasi)

Semua endpoint yang memerlukan login menggunakan **Bearer Token**.

1. Lakukan request login sesuai role.
2. Copy nilai `token` dari response JSON.
3. Tambahkan Header berikut di setiap request selanjutnya:
   ```
   Authorization: Bearer {token_anda}
   Accept: application/json
   ```
4. Di Scramble (docs), klik tombol **gembok (Authorize)** → paste token.

---

## Akun Demo (setelah `php artisan migrate:fresh --seed`)

| Role      | Email               | Password   |
|-----------|---------------------|------------|
| Developer | dev@admin.com       | password   |
| Owner     | owner@barber.com    | password   |
| Kasir     | kasir@barber.com    | password   |
| Staff     | staff@barber.com    | password   |
| Customer  | test@user.com       | password   |

---

---

# ROLE 1: DEVELOPER

> Developer adalah pengelola platform SaaS. Ia mengawasi seluruh Owner, menyetujui pendaftaran dan top-up saldo, serta mengatur biaya sistem.

**Login:**
```http
POST /auth/login/developer
Body: { "email": "dev@admin.com", "password": "password" }
```

---

## Endpoint Milik Developer

### 1. Dashboard Statistik Sistem
```http
GET /developer/dashboard
```
Menampilkan ringkasan global: jumlah owner, cabang, transaksi, dan total fee yang sudah dikumpulkan.

---

### 2. Lihat Daftar Owner
```http
GET /developer/owners
```
Perhatikan field `status`:
- `pending` → Owner baru daftar, belum bisa login.
- `active` → Sudah disetujui, bisa menggunakan sistem.

---

### 3. Setujui Pendaftaran Owner
```http
PUT /developer/owners/{id}/approve
```
- `{id}` = ID Owner dari endpoint sebelumnya.
- Tidak perlu Body.
- Sistem otomatis inisialisasi saldo deposit Owner menjadi **Rp 0**.

---

### 4. Lihat Permintaan Top-up Saldo (Pending)
```http
GET /developer/deposits/requests
```
Menampilkan seluruh request top-up yang menunggu verifikasi.

---

### 5. Setujui Top-up Saldo
```http
PUT /developer/deposits/requests/{id}/approve
```
- Saldo Owner bertambah otomatis sejumlah yang diminta.

---

### 6. Tolak Top-up Saldo
```http
PUT /developer/deposits/requests/{id}/reject
```
- Status menjadi `rejected`, saldo tidak berubah.

---

### 7. Atur Biaya Sistem per Transaksi
```http
PUT /developer/system-fee
Body (JSON):
{
  "fee": 2000
}
```
Setiap kali kasir mencatat transaksi, saldo Owner berkurang sejumlah `fee` ini.

---

### 8. Lihat Riwayat Transaksi Sistem (Pendapatan Developer)
```http
GET /developer/transactions
```
Daftar seluruh pemotongan saldo Owner sebagai biaya penggunaan SaaS.

---

## ⚠️ Endpoint Lain yang Wajib Ditest oleh Mahasiswa Developer

Saat membangun **Aplikasi Developer**, mahasiswa perlu menguji skenario berikut agar logika bisnisnya berjalan. Artinya, mahasiswa perlu login ke role lain untuk membuat data terlebih dahulu:

| Skenario | Role yang Digunakan | Endpoint yang Diakses |
|---|---|---|
| Buat Owner baru untuk disetujui | – (tanpa login) | `POST /auth/register-owner` |
| Cek Owner sudah aktif bisa login | Owner | `POST /auth/login/owner` |
| Buat request top-up agar bisa disetujui | Owner | `POST /owner/deposits/topup` |
| Buat transaksi agar fee terpotong | Kasir | `POST /kasir/transactions` |
| Verifikasi fee terpotong di dashboard | Developer | `GET /developer/dashboard` → cek `total_system_fee_collected` |

---

---

# ROLE 2: OWNER

> Owner adalah pemilik barbershop. Ia mengatur seluruh konfigurasi bisnis dan memantau operasional cabang.

**Daftar Akun Owner Baru (jika belum ada):**
```http
POST /auth/register-owner
Body (JSON):
{
  "name": "Nama Owner",
  "email": "owner_baru@email.com",
  "password": "password",
  "password_confirmation": "password"
}
```
> Setelah mendaftar, akun berstatus `pending`. **Developer harus menyetujuinya dulu** sebelum Owner bisa login.

**Login:**
```http
POST /auth/login/owner
Body: { "email": "owner@barber.com", "password": "password" }
```

---

## Endpoint Milik Owner

### 1. Kelola Profil
```http
GET /owner/profile                          # Lihat profil
PUT /owner/profile                          # Update profil
Body (JSON): { "name": "...", "email": "...", "password": "...", "password_confirmation": "..." }
```

---

### 2. Kelola Cabang (CRUD Lengkap)
```http
GET    /owner/branches                      # Lihat semua cabang
POST   /owner/branches                      # Tambah cabang baru
PUT    /owner/branches/{id}                 # Update informasi cabang
DELETE /owner/branches/{id}                 # Hapus cabang
```

**Body untuk POST & PUT:**
```json
{
  "name": "Barber King Pusat",
  "address": "Jl. Sudirman No. 1"
}
```
> ⚠️ Catat `id` cabang setelah membuat — dibutuhkan untuk mendaftarkan produk dan karyawan.

---

### 3. Kelola Layanan / Jasa (CRUD Lengkap)
Layanan bersifat **universal** (berlaku di semua cabang milik Owner ini).
```http
GET    /owner/services                      # Lihat semua layanan
POST   /owner/services                      # Tambah layanan
PUT    /owner/services/{id}                 # Update layanan
DELETE /owner/services/{id}                 # Hapus layanan
```

**Body untuk POST:**
```json
{
  "name": "Gunting Rambut Dewasa",
  "price": 35000,
  "commission_type": "fixed",
  "commission_amount": 5000
}
```
> `commission_type`: `fixed` (nominal tetap) atau `percentage` (% dari harga). Komisi ini yang akan diterima Staff setiap kali layanan digunakan.

---

### 4. Kelola Produk / Barang (CRUD Lengkap)
Produk bersifat **per cabang** — stok berbeda di tiap cabang.
```http
GET    /owner/products                      # Lihat semua produk
POST   /owner/products                      # Tambah produk
PUT    /owner/products/{id}                 # Update produk
DELETE /owner/products/{id}                 # Hapus produk
```

**Body untuk POST:**
```json
{
  "branch_id": 1,
  "name": "Pomade Waterbased",
  "price": 45000,
  "stock": 20
}
```

---

### 5. Kelola Karyawan
```http
GET  /owner/employees                       # Lihat semua karyawan (kasir & staff)
POST /owner/employees                       # Tambah karyawan baru
```

**Body untuk POST:**
```json
{
  "branch_id": 1,
  "name": "Joni Barberman",
  "email": "joni@barber.com",
  "password": "password",
  "role": "staff",
  "daily_base_salary": 50000
}
```
> `role`: hanya `kasir` atau `staff`. `daily_base_salary` adalah gaji pokok per hari (basis perhitungan gaji bulanan).

---

### 6. Kelola Saldo Deposit SaaS
```http
GET  /owner/deposits                        # Cek saldo saat ini
POST /owner/deposits/topup                  # Request top-up ke Developer
```

**Body untuk POST topup:**
```json
{
  "amount": 500000,
  "description": "Topup via Transfer Bank BCA"
}
```
> Saldo belum bertambah sampai Developer menyetujuinya. Jika saldo habis, kasir **tidak bisa** mencatat transaksi.

---

### 7. Lihat Laporan Operasional
```http
GET /owner/reports/sales                    # Laporan seluruh transaksi
GET /owner/reports/services                 # Laporan performa layanan jasa
GET /owner/reports/payrolls                 # Laporan penggajian karyawan
```

---

## ⚠️ Endpoint Lain yang Wajib Ditest oleh Mahasiswa Owner

| Skenario | Role yang Digunakan | Endpoint yang Diakses |
|---|---|---|
| Verifikasi akun Owner aktif setelah disetujui | Developer | `PUT /developer/owners/{id}/approve` |
| Verifikasi saldo bertambah setelah topup disetujui | Developer | `PUT /developer/deposits/requests/{id}/approve` |
| Pastikan karyawan yang dibuat bisa login | Kasir/Staff | `POST /auth/login/kasir` atau `POST /auth/login/staff` |
| Cek produk muncul di sisi pelanggan setelah ditambahkan | Customer | `GET /customer/branches/{id}/catalog` |
| Verifikasi layanan muncul saat kasir mau transaksi | Kasir | `GET /kasir/services` |
| Cek laporan bertambah setelah ada transaksi | Kasir | `POST /kasir/transactions` |

---

---

# ROLE 3: KASIR

> Kasir mengelola transaksi pelanggan dan stok barang di cabang.

**Login:**
```http
POST /auth/login/kasir
Body: { "email": "kasir@barber.com", "password": "password" }
```
> Akun kasir dibuat oleh Owner. Kasir otomatis terikat ke 1 cabang.

---

## Endpoint Milik Kasir (Urutan Operasional)

### 1. Absen Masuk (Awal Hari)
```http
POST /kasir/attendance/check-in
```
Tidak perlu Body. Sistem mencatat waktu dan mengunci gaji harian.
> Error 400 jika sudah check-in hari ini.

---

### 2. Lihat Data Referensi (Sebelum Transaksi)

Kasir perlu tahu ID dari data-data ini sebelum mencatat transaksi:

```http
GET /kasir/customers               # Daftar pelanggan (cari ID pelanggan)
GET /kasir/services                # Daftar layanan tersedia + harga + info komisi
GET /kasir/products                # Daftar produk di cabang ini + stok saat ini
GET /kasir/available-staff         # Daftar staff yang bertugas di cabang ini
```

---

### 3. Daftarkan Pelanggan Walk-in (Jika Perlu)
```http
POST /kasir/customers
Body (JSON):
{
  "name": "Pelanggan Walk-in",
  "email": "walkIn123@email.com"
}
```
> Password default pelanggan walk-in: `pelanggan123`.

---

### 4. Catat Transaksi (Inti Proses Kasir)
```http
POST /kasir/transactions
Body (JSON):
{
  "customer_id": 6,
  "payment_method": "cash",
  "items": [
    { "type": "service", "id": 1, "staff_id": 4 },
    { "type": "product", "id": 1 }
  ]
}
```

| Field | Wajib | Keterangan |
|---|---|---|
| `customer_id` | Tidak | Bisa `null` jika pelanggan tidak mau didata. |
| `payment_method` | Ya | Contoh: `cash`, `transfer`, `qris` |
| `items` | Ya | Array, minimal 1 item |
| `items.*.type` | Ya | `service` atau `product` |
| `items.*.id` | Ya | ID layanan atau ID produk |
| `items.*.staff_id` | Ya (jika `service`) | ID staff yang melayani. Dapat komisi. |

**Yang otomatis terjadi saat transaksi berhasil:**
1. Stok produk berkurang.
2. Komisi staff tersimpan.
3. Saldo deposit Owner **dipotong** sejumlah biaya sistem (default Rp 2.000).

> **Error 402**: Saldo Owner tidak cukup — Owner harus top-up dulu ke Developer.

---

### 5. Catat Restok Barang
```http
POST /kasir/restocks
Body (JSON): { "product_id": 1, "qty": 10 }
```
Stok bertambah otomatis.

---

### 6. Lihat Laporan
```http
GET /kasir/transactions            # Seluruh transaksi di cabang ini
GET /kasir/reports/sales           # Laporan penjualan produk
GET /kasir/reports/services        # Laporan jasa layanan
```

---

### 7. Rekap Gaji Bulan Ini
```http
GET /kasir/salary-summary
```
Dihitung dari total hari check-in × gaji pokok harian.

---

### 8. Absen Pulang (Akhir Hari)
```http
POST /kasir/attendance/check-out
GET  /kasir/attendance/history     # Lihat riwayat absensi
```

---

## ⚠️ Endpoint Lain yang Wajib Ditest oleh Mahasiswa Kasir

| Skenario | Role yang Digunakan | Endpoint yang Diakses |
|---|---|---|
| Pastikan layanan ada sebelum ditampilkan di UI | Owner | `POST /owner/services` |
| Pastikan produk ada dan stoknya > 0 | Owner | `POST /owner/products` |
| Cek ID staff yang bertugas hari ini | – | `GET /kasir/available-staff` |
| Verifikasi stok berkurang setelah transaksi | Kasir | `GET /kasir/products` → cek field `stock` |
| Verifikasi saldo Owner berkurang setelah transaksi | Owner | `GET /owner/deposits` |
| Pastikan riwayat transaksi muncul di sisi pelanggan | Customer | `GET /customer/visit-history` |
| Verifikasi komisi staff terekam | Staff | `GET /staff/salary-summary` |

---

---

# ROLE 4: STAFF

> Staff adalah Barberman yang melayani pelanggan, mencatat absensi, dan mengunggah foto hasil kerja.

**Login:**
```http
POST /auth/login/staff
Body: { "email": "staff@barber.com", "password": "password" }
```

---

## Endpoint Milik Staff (Urutan Operasional)

### 1. Absen Masuk (Awal Hari)
```http
POST /staff/attendance/check-in
```
Mencatat waktu dan mengunci gaji pokok harian.
> Error 400 jika sudah check-in hari ini.

---

### 2. Kelola Profil
```http
GET /staff/profile
PUT /staff/profile
Body (JSON): { "name": "...", "email": "...", "password": "...", "password_confirmation": "..." }
```

---

### 3. Upload Foto Hasil Cukuran ke Galeri Pelanggan
Setelah selesai melayani dan pelanggan sudah membayar:

**Langkah A — Cari ID pelanggan yang dilayani:**
```http
GET /staff/customers
```

**Langkah B — Upload foto:**
```http
POST /staff/galleries
Content-Type: multipart/form-data

Form fields:
  customer_id  : 6
  transaction_id: 2        (opsional, untuk menghubungkan ke transaksi)
  image        : [file gambar .jpg/.png, maks 2MB]
```
> ⚠️ **Harus menggunakan `multipart/form-data`** (bukan JSON) karena ada upload file. Di Postman: Body → form-data.

> Foto akan otomatis muncul di `GET /customer/galleries` milik pelanggan tersebut.

---

### 4. Lihat Rekap Gaji & Komisi Bulan Ini
```http
GET /staff/salary-summary
```
```json
{
  "month": "May 2026",
  "total_base_salary": 150000,
  "total_commissions": 25000,
  "take_home_pay": 175000
}
```
> Komisi hanya muncul jika staff sudah ter-assign ke transaksi jasa (via `staff_id` di `POST /kasir/transactions`).

---

### 5. Lihat Riwayat Absensi
```http
GET /staff/attendance/history
```

---

### 6. Absen Pulang (Akhir Hari)
```http
POST /staff/attendance/check-out
```
> Error 400 jika belum check-in atau sudah check-out.

---

## ⚠️ Endpoint Lain yang Wajib Ditest oleh Mahasiswa Staff

| Skenario | Role yang Digunakan | Endpoint yang Diakses |
|---|---|---|
| Pastikan ada data pelanggan sebelum upload galeri | Kasir | `POST /kasir/customers` (daftar pelanggan walk-in) |
| Verifikasi foto muncul di galeri pelanggan | Customer | `GET /customer/galleries` |
| Pastikan komisi tercatat setelah kasir input transaksi | Kasir | `POST /kasir/transactions` dengan `staff_id` diisi |
| Cek komisi muncul di rekap gaji | Staff | `GET /staff/salary-summary` → `total_commissions` |

---

---

# ROLE 5: CUSTOMER (PELANGGAN)

> Pelanggan menggunakan aplikasi untuk melihat informasi barbershop, riwayat kunjungan, dan galeri foto.

**Daftar Akun Baru:**
```http
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
```http
POST /auth/login/customer
Body: { "email": "test@user.com", "password": "password" }
```

---

## Endpoint Milik Customer

### 1. Kelola Profil
```http
GET /customer/profile
PUT /customer/profile
Body (JSON): { "name": "...", "email": "..." }
```

---

### 2. Lihat Semua Cabang Barbershop
```http
GET /customer/branches
```
Menampilkan semua cabang dari semua Owner di sistem. Catat `id` cabang yang ingin dikunjungi.

---

### 3. Lihat Katalog Cabang (Layanan + Produk Sekaligus)
```http
GET /customer/branches/{id}/catalog
```
Ganti `{id}` dengan ID cabang. Respons berisi:
```json
{
  "branch": { "id": 1, "name": "Barber King Pusat", "address": "..." },
  "services": [
    { "id": 1, "name": "Gunting Rambut Dewasa", "price": "35000.00" }
  ],
  "products": [
    { "id": 1, "name": "Pomade Waterbased", "price": "45000.00", "stock": 18 }
  ]
}
```

---

### 4. Lihat Riwayat Kunjungan
```http
GET /customer/visit-history
```
Menampilkan semua transaksi yang pernah dilakukan, lengkap dengan:
- Cabang tempat bertransaksi.
- Daftar jasa/produk yang dibeli.
- Staff yang melayani (untuk jasa).
- Total pembayaran.

---

### 5. Lihat Galeri Foto Hasil Cukuran
```http
GET /customer/galleries
```
Foto-foto ini diunggah oleh Staff setelah pelayanan selesai (`POST /staff/galleries`).

---

## ⚠️ Endpoint Lain yang Wajib Ditest oleh Mahasiswa Customer

| Skenario | Role yang Digunakan | Endpoint yang Diakses |
|---|---|---|
| Pastikan ada cabang sebelum tampil di list | Owner | `POST /owner/branches` |
| Pastikan ada layanan & produk di katalog | Owner | `POST /owner/services` & `POST /owner/products` |
| Buat transaksi agar muncul di riwayat kunjungan | Kasir | `POST /kasir/transactions` (dengan `customer_id` diisi) |
| Upload foto agar muncul di galeri | Staff | `POST /staff/galleries` (dengan `customer_id` diisi) |
| Verifikasi foto galeri bisa diakses via URL | – | Buka `http://127.0.0.1:8000/storage/{photo_url}` di browser |

---

---

# Alur Bisnis End-to-End

Urutan wajib agar sistem dapat berjalan dari nol:

```
1. [Developer]  Atur biaya sistem           → PUT  /developer/system-fee
2. [-]          Owner mendaftar             → POST /auth/register-owner
3. [Developer]  Setujui Owner               → PUT  /developer/owners/{id}/approve
4. [Owner]      Top-up saldo               → POST /owner/deposits/topup
5. [Developer]  Setujui top-up             → PUT  /developer/deposits/requests/{id}/approve
6. [Owner]      Buat cabang                → POST /owner/branches
7. [Owner]      Buat layanan               → POST /owner/services
8. [Owner]      Buat produk                → POST /owner/products
9. [Owner]      Rekrut kasir & staff       → POST /owner/employees  (x2)
10. [Kasir]     Check-in absen             → POST /kasir/attendance/check-in
11. [Staff]     Check-in absen             → POST /staff/attendance/check-in
12. [-]         Pelanggan daftar/datang    → POST /auth/register-customer
13. [Kasir]     Catat transaksi            → POST /kasir/transactions
14. [Staff]     Upload foto galeri          → POST /staff/galleries
15. [Customer]  Lihat riwayat & galeri     → GET  /customer/visit-history & /galleries
16. [Owner]     Pantau laporan             → GET  /owner/reports/sales & /payrolls
17. [Developer] Cek pendapatan SaaS        → GET  /developer/transactions
```

---

# Tips Pengujian

- **Postman/Insomnia**: Gunakan untuk upload file gambar (galeri). Scramble kurang nyaman untuk file.
- **Baca error message**: Sistem memberikan pesan error yang informatif. Misal:
  - `Error 400` → Validasi gagal atau aturan bisnis dilanggar.
  - `Error 401` → Token tidak ada atau sudah expired. Login ulang.
  - `Error 402` → Saldo Owner tidak cukup untuk biaya sistem.
  - `Error 403` → Mengakses endpoint yang bukan hak role Anda.
  - `Error 404` → Data tidak ditemukan.
  - `Error 422` → Field yang dikirim tidak sesuai validasi.
- **Catat ID**: Setiap kali membuat data baru (cabang, layanan, produk, user), catat `id`-nya karena akan dibutuhkan di endpoint selanjutnya.
- **Storage link**: Jalankan `php artisan storage:link` agar URL foto galeri bisa diakses via browser.

---

# Perintah Berguna

```bash
# Reset database + isi ulang data demo
php artisan migrate:fresh --seed

# Aktifkan akses file storage (foto galeri)
php artisan storage:link

# Bersihkan cache routing & konfigurasi
php artisan optimize:clear

# Jalankan server lokal
php artisan serve
```
