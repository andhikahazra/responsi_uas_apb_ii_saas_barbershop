<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Branch;
use App\Models\Product;
use App\Models\Service;
use App\Models\Transaction;
use App\Models\TransactionDetail;
use App\Models\OwnerDeposit;
use App\Models\DepositHistory;
use App\Models\Attendance;
use App\Models\Gallery;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Carbon\Carbon;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // 1. DEVELOPER
        User::create([
            'name' => 'Developer Admin',
            'email' => 'dev@admin.com',
            'password' => Hash::make('password'),
            'role' => 'developer',
            'status' => 'active',
        ]);

        // 2. OWNER
        $owner = User::create([
            'name' => 'Budi Barbershop Owner',
            'email' => 'owner@barber.com',
            'password' => Hash::make('password'),
            'role' => 'owner',
            'status' => 'active',
        ]);

        // 3. CABANG (BRANCHES)
        $branch1 = Branch::create(['owner_id' => $owner->id, 'name' => 'Barber King Pusat', 'address' => 'Jl. Sudirman No. 1']);
        $branch2 = Branch::create(['owner_id' => $owner->id, 'name' => 'Barber King Mall', 'address' => 'Lantai 2, Mall Plaza']);
        $branch3 = Branch::create(['owner_id' => $owner->id, 'name' => 'Barber King Express', 'address' => 'Stasiun Gubeng']);

        // 4. STAFF & KASIR (Per Cabang)
        $kasir = User::create([
            'name' => 'Siti Kasir',
            'email' => 'kasir@barber.com',
            'password' => Hash::make('password'),
            'role' => 'kasir',
            'status' => 'active',
            'branch_id' => $branch1->id,
            'daily_base_salary' => 45000,
        ]);

        $staff1 = User::create([
            'name' => 'Joni Barberman',
            'email' => 'staff@barber.com',
            'password' => Hash::make('password'),
            'role' => 'staff',
            'status' => 'active',
            'branch_id' => $branch1->id,
            'daily_base_salary' => 50000,
        ]);

        $staff2 = User::create([
            'name' => 'Eko Barberman',
            'email' => 'eko@barber.com',
            'password' => Hash::make('password'),
            'role' => 'staff',
            'status' => 'active',
            'branch_id' => $branch2->id,
            'daily_base_salary' => 55000,
        ]);

        // 5. CUSTOMERS
        $customers = [];
        $customerData = [
            ['name' => 'Rizky Pelanggan', 'email' => 'test@user.com'],
            ['name' => 'Andi Wijaya', 'email' => 'andi@gmail.com'],
            ['name' => 'Santi Putri', 'email' => 'santi@yahoo.com'],
            ['name' => 'Bambang Sukses', 'email' => 'bambang@outlook.com'],
            ['name' => 'Dewi Lestari', 'email' => 'dewi@gmail.com'],
        ];

        foreach ($customerData as $data) {
            $customers[] = User::create([
                'name' => $data['name'],
                'email' => $data['email'],
                'password' => Hash::make('password'),
                'role' => 'customer',
                'status' => 'active',
            ]);
        }

        // 6. PRODUK & LAYANAN
        $service1 = Service::create(['owner_id' => $owner->id, 'name' => 'Gunting Rambut Dewasa', 'price' => 35000, 'commission_type' => 'fixed', 'commission_amount' => 5000]);
        $service2 = Service::create(['owner_id' => $owner->id, 'name' => 'Cukur + Cuci + Pijat', 'price' => 50000, 'commission_type' => 'fixed', 'commission_amount' => 10000]);
        $service3 = Service::create(['owner_id' => $owner->id, 'name' => 'Semir Rambut Black', 'price' => 75000, 'commission_type' => 'fixed', 'commission_amount' => 15000]);

        $product1 = Product::create(['branch_id' => $branch1->id, 'name' => 'Pomade Waterbased', 'price' => 45000, 'stock' => 20]);
        $product2 = Product::create(['branch_id' => $branch1->id, 'name' => 'Hair Tonic Tonic', 'price' => 30000, 'stock' => 15]);

        // 7. DEPOSIT OWNER
        $deposit = OwnerDeposit::create(['owner_id' => $owner->id, 'balance' => 500000]);
        
        // Histori Deposit
        DepositHistory::create(['owner_id' => $owner->id, 'amount' => 500000, 'type' => 'topup', 'status' => 'approved', 'description' => 'Saldo awal']);
        DepositHistory::create(['owner_id' => $owner->id, 'amount' => 100000, 'type' => 'topup', 'status' => 'pending', 'description' => 'Menunggu verifikasi']);

        // 8. ABSENSI (Beberapa hari ke belakang)
        for ($i = 0; $i < 3; $i++) {
            // Absen Staff
            Attendance::create([
                'user_id' => $staff1->id,
                'date' => Carbon::now()->subDays($i)->toDateString(),
                'check_in' => '09:00:00',
                'check_out' => '17:00:00',
                'daily_base_salary_earned' => 50000,
            ]);

            // Absen Kasir
            Attendance::create([
                'user_id' => $kasir->id,
                'date' => Carbon::now()->subDays($i)->toDateString(),
                'check_in' => '08:00:00',
                'check_out' => '16:00:00',
                'daily_base_salary_earned' => 45000,
            ]);
        }

        // 9. TRANSAKSI HISTORIS (Seminggu Terakhir)
        for ($i = 0; $i < 10; $i++) {
            $customer = $customers[array_rand($customers)];
            $date = Carbon::now()->subDays(rand(0, 7));
            
            $trx = Transaction::create([
                'customer_id' => $customer->id,
                'branch_id' => $branch1->id,
                'total_amount' => 0, // Will update
                'payment_method' => 'cash',
                'date' => $date->toDateString(),
            ]);

            // Tambah Detail Layanan
            $detail = TransactionDetail::create([
                'transaction_id' => $trx->id,
                'item_type' => 'service',
                'item_id' => $service1->id,
                'staff_id' => $staff1->id,
                'price' => $service1->price,
                'commission_earned' => $service1->commission_amount,
            ]);

            // Tambah Detail Produk (Acak)
            if (rand(0, 1)) {
                TransactionDetail::create([
                    'transaction_id' => $trx->id,
                    'item_type' => 'product',
                    'item_id' => $product1->id,
                    'price' => $product1->price,
                ]);
            }

            // Update Total
            $total = TransactionDetail::where('transaction_id', $trx->id)->sum('price');
            $trx->update(['total_amount' => $total]);
        }

        // 10. GALLERY (Contoh Foto)
        Gallery::create([
            'customer_id' => $customers[0]->id,
            'staff_id' => $staff1->id,
            'photo_url' => 'galleries/sample.jpg',
        ]);
    }
}
