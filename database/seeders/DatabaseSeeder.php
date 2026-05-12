<?php

namespace Database\Seeders;

use App\Models\Attendance;
use App\Models\Branch;
use App\Models\DepositHistory;
use App\Models\Gallery;
use App\Models\OwnerDeposit;
use App\Models\Product;
use App\Models\ProductRestock;
use App\Models\Service;
use App\Models\Transaction;
use App\Models\TransactionDetail;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // ================================================================
        // PETA ID — untuk memudahkan testing mahasiswa
        // ================================================================
        // USERS
        //   ID 1  → Developer Admin         (dev@admin.com)
        //   ID 2  → Budi (Owner)            (owner@barber.com)
        //   ID 3  → Siti Kasir  [Cabang 1]  (kasir@barber.com)
        //   ID 4  → Joni Staff  [Cabang 1]  (staff@barber.com)
        //   ID 5  → Eko Staff   [Cabang 2]  (eko@barber.com)
        //   ID 6  → Rizky       (Customer)  (test@user.com)
        //   ID 7  → Andi        (Customer)  (andi@gmail.com)
        //   ID 8  → Santi       (Customer)  (santi@gmail.com)
        // BRANCHES
        //   ID 1  → Barber King Pusat
        //   ID 2  → Barber King Mall
        // SERVICES (universal semua cabang)
        //   ID 1  → Gunting Rambut Dewasa  Rp 35.000  komisi fixed  Rp 5.000
        //   ID 2  → Cukur + Cuci + Pijat   Rp 65.000  komisi fixed  Rp 10.000
        //   ID 3  → Semir Rambut            Rp 75.000  komisi 15%   (percentage)
        // PRODUCTS (per cabang)
        //   ID 1  → Pomade Waterbased [Cabang 1]  Rp 45.000  stok 28 (30 masuk, terjual 2)
        //   ID 2  → Hair Tonic        [Cabang 1]  Rp 30.000  stok 18 (20 masuk, terjual 2)
        //   ID 3  → Clay Matte        [Cabang 2]  Rp 55.000  stok 25 (tidak ada transaksi)
        // RESTOCKS
        //   Pomade    : +20 (14hr lalu) +10 (7hr lalu) = 30 masuk, terjual 2 → stok 28
        //   Hair Tonic: +15 (10hr lalu) + 5 (3hr lalu) = 20 masuk, terjual 2 → stok 18
        //   Clay Matte: +25 (10hr lalu) = 25 masuk, terjual 0 → stok 25
        // TRANSAKSI: 8 total | fee 8 × 2.000 = 16.000
        //   Saldo owner: 500.000 − 16.000 = 484.000
        // ================================================================

        // ----------------------------------------------------------------
        // 0. SETTINGS — biaya sistem per transaksi
        // ----------------------------------------------------------------
        DB::table('settings')->updateOrInsert(
            ['key' => 'system_transaction_fee'],
            ['value' => '2000', 'updated_at' => now()]
        );
        $systemFee = 2000;

        // ----------------------------------------------------------------
        // 1. DEVELOPER
        // ----------------------------------------------------------------
        User::create([
            'name'     => 'Developer Admin',
            'email'    => 'dev@admin.com',
            'password' => Hash::make('password'),
            'role'     => 'developer',
            'status'   => 'active',
        ]);

        // ----------------------------------------------------------------
        // 2. OWNER
        // ----------------------------------------------------------------
        $owner = User::create([
            'name'     => 'Budi Barbershop Owner',
            'email'    => 'owner@barber.com',
            'password' => Hash::make('password'),
            'role'     => 'owner',
            'status'   => 'active',
        ]);

        $ownerDeposit = OwnerDeposit::create([
            'owner_id' => $owner->id,
            'balance'  => 500000,
        ]);
        DepositHistory::create([
            'owner_id'    => $owner->id,
            'amount'      => 500000,
            'type'        => 'topup',
            'status'      => 'approved',
            'description' => 'Top-up perdana (saldo awal)',
        ]);
        // Request pending — untuk testing fitur Developer approve/reject
        DepositHistory::create([
            'owner_id'    => $owner->id,
            'amount'      => 200000,
            'type'        => 'topup',
            'status'      => 'pending',
            'description' => 'Top-up via Transfer BCA – menunggu konfirmasi Developer',
        ]);

        // ----------------------------------------------------------------
        // 3. CABANG
        // ----------------------------------------------------------------
        $branch1 = Branch::create([
            'owner_id' => $owner->id,
            'name'     => 'Barber King Pusat',
            'address'  => 'Jl. Sudirman No. 1, Jakarta Pusat',
        ]);
        $branch2 = Branch::create([
            'owner_id' => $owner->id,
            'name'     => 'Barber King Mall',
            'address'  => 'Lantai 2, Mall Grand Indonesia',
        ]);

        // ----------------------------------------------------------------
        // 4. KARYAWAN
        // ----------------------------------------------------------------
        $kasir = User::create([
            'name'              => 'Siti Kasir',
            'email'             => 'kasir@barber.com',
            'password'          => Hash::make('password'),
            'role'              => 'kasir',
            'status'            => 'active',
            'branch_id'         => $branch1->id,
            'daily_base_salary' => 45000,
        ]);
        $staff1 = User::create([
            'name'              => 'Joni Barberman',
            'email'             => 'staff@barber.com',
            'password'          => Hash::make('password'),
            'role'              => 'staff',
            'status'            => 'active',
            'branch_id'         => $branch1->id,
            'daily_base_salary' => 50000,
        ]);
        $staff2 = User::create([
            'name'              => 'Eko Barberman',
            'email'             => 'eko@barber.com',
            'password'          => Hash::make('password'),
            'role'              => 'staff',
            'status'            => 'active',
            'branch_id'         => $branch2->id,
            'daily_base_salary' => 55000,
        ]);

        // ----------------------------------------------------------------
        // 5. PELANGGAN
        // ----------------------------------------------------------------
        $c1 = User::create(['name' => 'Rizky Pelanggan', 'email' => 'test@user.com',   'password' => Hash::make('password'), 'role' => 'customer', 'status' => 'active']);
        $c2 = User::create(['name' => 'Andi Wijaya',     'email' => 'andi@gmail.com',  'password' => Hash::make('password'), 'role' => 'customer', 'status' => 'active']);
        $c3 = User::create(['name' => 'Santi Putri',     'email' => 'santi@gmail.com', 'password' => Hash::make('password'), 'role' => 'customer', 'status' => 'active']);

        // ----------------------------------------------------------------
        // 6. LAYANAN — universal, berlaku di semua cabang milik owner ini
        // ----------------------------------------------------------------
        $s1 = Service::create(['owner_id' => $owner->id, 'name' => 'Gunting Rambut Dewasa', 'price' => 35000, 'commission_type' => 'fixed',      'commission_amount' => 5000]);
        $s2 = Service::create(['owner_id' => $owner->id, 'name' => 'Cukur + Cuci + Pijat', 'price' => 65000, 'commission_type' => 'fixed',      'commission_amount' => 10000]);
        $s3 = Service::create(['owner_id' => $owner->id, 'name' => 'Semir Rambut',          'price' => 75000, 'commission_type' => 'percentage', 'commission_amount' => 15]);

        // ----------------------------------------------------------------
        // 7. PRODUK — per cabang, stok awal disesuaikan setelah restock & transaksi
        //    Logika stok:
        //      Pomade    : restock awal 20 + restock ke-2 10 = 30 masuk, terjual 2 → stok akhir 28
        //      Hair Tonic: restock awal 15 + restock ke-2  5 = 20 masuk, terjual 1 → stok akhir 19
        //      Clay Matte: restock awal 25, tidak ada transaksi → stok akhir 25
        // ----------------------------------------------------------------
        $p1 = Product::create(['branch_id' => $branch1->id, 'name' => 'Pomade Waterbased', 'price' => 45000, 'stock' => 28]);
        $p2 = Product::create(['branch_id' => $branch1->id, 'name' => 'Hair Tonic',        'price' => 30000, 'stock' => 18]);
        $p3 = Product::create(['branch_id' => $branch2->id, 'name' => 'Clay Matte',        'price' => 55000, 'stock' => 25]);

        // ----------------------------------------------------------------
        // 8. RESTOCK — riwayat penambahan stok yang sudah terjadi
        //    Kolom: product_id | qty_added | date
        // ----------------------------------------------------------------
        // Pomade: restock awal 14 hari lalu, restock lagi 7 hari lalu
        ProductRestock::create(['product_id' => $p1->id, 'qty_added' => 20, 'date' => Carbon::now()->subDays(14)->toDateString()]);
        ProductRestock::create(['product_id' => $p1->id, 'qty_added' => 10, 'date' => Carbon::now()->subDays(7)->toDateString()]);

        // Hair Tonic: restock awal 10 hari lalu, restock lagi 3 hari lalu
        ProductRestock::create(['product_id' => $p2->id, 'qty_added' => 15, 'date' => Carbon::now()->subDays(10)->toDateString()]);
        ProductRestock::create(['product_id' => $p2->id, 'qty_added' =>  5, 'date' => Carbon::now()->subDays(3)->toDateString()]);

        // Clay Matte (Cabang 2): restock awal 10 hari lalu
        ProductRestock::create(['product_id' => $p3->id, 'qty_added' => 25, 'date' => Carbon::now()->subDays(10)->toDateString()]);

        // ----------------------------------------------------------------
        // 9. ABSENSI — 7 hari ke belakang (basis rekap gaji bulan ini)
        // ----------------------------------------------------------------
        for ($day = 6; $day >= 0; $day--) {
            $date = Carbon::now()->subDays($day)->toDateString();
            Attendance::create(['user_id' => $kasir->id,  'date' => $date, 'check_in' => '08:00:00', 'check_out' => '16:00:00', 'daily_base_salary_earned' => $kasir->daily_base_salary]);
            Attendance::create(['user_id' => $staff1->id, 'date' => $date, 'check_in' => '09:00:00', 'check_out' => '17:00:00', 'daily_base_salary_earned' => $staff1->daily_base_salary]);
            // Staff2 (Eko, Cabang 2) — absen 5 hari terakhir (libur 2 hari)
            if ($day <= 4) {
                Attendance::create(['user_id' => $staff2->id, 'date' => $date, 'check_in' => '09:30:00', 'check_out' => '17:30:00', 'daily_base_salary_earned' => $staff2->daily_base_salary]);
            }
        }

        // ----------------------------------------------------------------
        // 10. TRANSAKSI HISTORIS — 8 skenario berbeda (7 hari terakhir)
        //     Saldo owner dipotong Rp 2.000 per transaksi (system fee)
        //     Total potongan: 8 × 2.000 = Rp 16.000
        //     Saldo akhir owner: 500.000 − 16.000 = 484.000
        // ----------------------------------------------------------------
        $scenarios = [
            // [$service, $product|null, $customer|null, $daysAgo, $payment_method]
            [$s1, null, $c1,  6, 'cash'],     // Gunting saja – Rizky
            [$s1, $p1,  $c2,  5, 'transfer'], // Gunting + Pomade – Andi       (stok pomade -1)
            [$s2, null, $c3,  4, 'cash'],     // Paket lengkap – Santi
            [$s1, $p2,  $c1,  3, 'qris'],     // Gunting + Hair Tonic – Rizky  (stok hair tonic -1)
            [$s2, $p1,  $c2,  2, 'cash'],     // Paket + Pomade – Andi         (stok pomade -1)
            [$s3, null, $c3,  1, 'transfer'], // Semir (komisi 15%) – Santi
            [$s1, null, $c1,  0, 'cash'],     // Gunting saja – Rizky (hari ini)
            [$s1, $p2,  null, 0, 'cash'],     // Walk-in (customer_id null) – uji fitur tanpa akun pelanggan
        ];

        foreach ($scenarios as [$service, $product, $customer, $daysAgo, $method]) {
            // Hitung komisi sesuai tipe
            $commission = $service->commission_type === 'fixed'
                ? $service->commission_amount
                : round(($service->commission_amount / 100) * $service->price);

            $trx = Transaction::create([
                'branch_id'      => $branch1->id,
                'customer_id'    => $customer?->id,
                'total_amount'   => 0,
                'payment_method' => $method,
                'date'           => Carbon::now()->subDays($daysAgo)->toDateString(),
            ]);

            // Detail jasa
            TransactionDetail::create([
                'transaction_id'    => $trx->id,
                'item_type'         => 'service',
                'item_id'           => $service->id,
                'price'             => $service->price,
                'staff_id'          => $staff1->id,
                'commission_earned' => $commission,
            ]);

            $total = $service->price;

            // Detail produk (jika ada)
            if ($product) {
                TransactionDetail::create([
                    'transaction_id'    => $trx->id,
                    'item_type'         => 'product',
                    'item_id'           => $product->id,
                    'price'             => $product->price,
                    'staff_id'          => null,
                    'commission_earned' => 0,
                ]);
                $total += $product->price;
                // Stok sudah dikurangi di kolom stock produk (sesuai stok akhir yang ditulis)
            }

            $trx->update(['total_amount' => $total]);

            // Potong saldo Owner (biaya SaaS)
            $ownerDeposit->decrement('balance', $systemFee);
            DepositHistory::create([
                'owner_id'    => $owner->id,
                'amount'      => $systemFee,
                'type'        => 'deduction',
                'status'      => 'approved',
                'description' => 'Biaya sistem transaksi #' . $trx->id,
            ]);

            // Simpan ID transaksi pertama dan kedua untuk galeri
            if (!isset($trx1)) $trx1 = $trx;
            elseif (!isset($trx2)) $trx2 = $trx;
        }

        // ----------------------------------------------------------------
        // 11. GALERI — foto hasil cukur terhubung ke pelanggan & transaksi
        //     Pakai variabel $trx1/$trx2 (bukan hardcoded ID) agar aman
        // ----------------------------------------------------------------
        Gallery::create(['customer_id' => $c1->id, 'staff_id' => $staff1->id, 'transaction_id' => $trx1->id ?? null, 'photo_url' => 'galleries/sample-1.jpg']);
        Gallery::create(['customer_id' => $c2->id, 'staff_id' => $staff1->id, 'transaction_id' => $trx2->id ?? null, 'photo_url' => 'galleries/sample-2.jpg']);
        Gallery::create(['customer_id' => $c3->id, 'staff_id' => $staff1->id, 'transaction_id' => null,              'photo_url' => 'galleries/sample-3.jpg']);
    }
}
