<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\Branch;
use App\Models\DepositHistory;
use App\Models\OwnerDeposit;
use App\Models\Product;
use App\Models\ProductRestock;
use App\Models\Service;
use App\Models\Transaction;
use App\Models\TransactionDetail;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

use App\Http\Requests\StoreTransactionRequest;

/**
 * @tags Kasir
 */
class KasirController extends Controller
{
    /**
     * Absen Masuk (Check-in)
     */
    public function checkIn(Request $request)
    {
        $user = $request->user();
        
        $exists = Attendance::where('user_id', $user->id)
            ->where('date', now()->toDateString())
            ->first();

        if ($exists) {
            return response()->json(['message' => 'Anda sudah melakukan absen masuk hari ini.'], 400);
        }
        
        $attendance = Attendance::create([
            'user_id' => $user->id,
            'date' => now()->toDateString(),
            'check_in' => now()->toTimeString(),
            'daily_base_salary_earned' => $user->daily_base_salary ?? 0,
        ]);

        return response()->json($attendance, 201);
    }

    /**
     * Absen Pulang (Check-out)
     */
    public function checkOut(Request $request)
    {
        $attendance = Attendance::where('user_id', $request->user()->id)
            ->where('date', now()->toDateString())
            ->firstOrFail();

        if ($attendance->check_out) {
            return response()->json(['message' => 'Anda sudah melakukan absen pulang hari ini.'], 400);
        }

        $attendance->update(['check_out' => now()->toTimeString()]);
        return response()->json($attendance);
    }

    /**
     * Histori Absensi Saya
     */
    public function getAttendanceHistory(Request $request)
    {
        $history = Attendance::where('user_id', $request->user()->id)->latest()->get();
        return response()->json($history);
    }

    /**
     * Rekap Gaji Bulan Ini
     */
    public function getSalarySummary(Request $request)
    {
        $user = $request->user();
        $month = now()->month;

        $baseSalary = Attendance::where('user_id', $user->id)
            ->whereMonth('date', $month)
            ->sum('daily_base_salary_earned');

        return response()->json([
            'month' => now()->format('F Y'),
            'total_base_salary' => (float)$baseSalary,
            'take_home_pay' => (float)$baseSalary,
        ]);
    }
    /**
     * Daftar Pelanggan
     */
    public function indexCustomers()
    {
        return response()->json(User::where('role', 'customer')->get());
    }

    /**
     * Registrasi Pelanggan Baru (Walk-in)
     */
    public function storeCustomer(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'phone' => 'nullable|string',
        ]);

        $customer = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make('pelanggan123'), // Default password for walk-in
            'role' => 'customer',
            'status' => 'active',
        ]);

        return response()->json($customer, 201);
    }

    /**
     * Daftar Staff yang Tersedia di Cabang Ini
     */
    public function indexAvailableStaff(Request $request)
    {
        $kasir = $request->user();
        $staff = User::where('branch_id', $kasir->branch_id)
            ->where('role', 'staff')
            ->get();
        return response()->json($staff);
    }

    /**
     * Pencatatan Restock Barang
     */
    public function storeRestock(Request $request)
    {
        $data = $request->validate([
            'product_id' => 'required|exists:products,id',
            'qty' => 'required|integer|min:1',
        ]);

        return DB::transaction(function () use ($data) {
            $product = Product::findOrFail($data['product_id']);
            $product->increment('stock', $data['qty']);

            $restock = ProductRestock::create([
                'product_id' => $data['product_id'],
                'qty_added' => $data['qty'],
                'date' => now(),
            ]);

            return response()->json($restock, 201);
        });
    }

    /**
     * Simpan Transaksi Baru
     * 
     * Mencatat transaksi layanan dan produk, memotong stok, 
     * mengunci komisi staff, dan memotong saldo Owner (SaaS Fee).
     */
    public function storeTransaction(StoreTransactionRequest $request)
    {
        $kasir = $request->user();
        $branch = Branch::findOrFail($kasir->branch_id);
        $ownerId = $branch->owner_id;

        // System Fee Config
        $systemFee = (int) \DB::table('settings')->where('key', 'system_transaction_fee')->value('value') ?? 2000;

        return DB::transaction(function () use ($request, $kasir, $branch, $ownerId, $systemFee) {
            // 1. Check Owner Balance
            $deposit = OwnerDeposit::where('owner_id', $ownerId)->first();
            if (!$deposit || $deposit->balance < $systemFee) {
                return response()->json(['message' => 'Saldo Owner tidak mencukupi untuk biaya sistem.'], 402);
            }

            $totalAmount = 0;
            $itemsToSave = [];

            foreach ($request->items as $item) {
                if ($item['type'] === 'service') {
                    $service = Service::findOrFail($item['id']);
                    $commission = 0;
                    if ($service->commission_type === 'percentage') {
                        $commission = ($service->commission_amount / 100) * $service->price;
                    } else {
                        $commission = $service->commission_amount;
                    }

                    $totalAmount += $service->price;
                    $itemsToSave[] = [
                        'item_type' => 'service',
                        'item_id' => $service->id,
                        'price' => $service->price,
                        'staff_id' => $item['staff_id'],
                        'commission_earned' => $commission
                    ];
                } else {
                    $product = Product::findOrFail($item['id']);
                    if ($product->stock < 1) {
                        throw new \Exception("Stok produk {$product->name} habis.");
                    }
                    $product->decrement('stock', 1);

                    $totalAmount += $product->price;
                    $itemsToSave[] = [
                        'item_type' => 'product',
                        'item_id' => $product->id,
                        'price' => $product->price,
                        'staff_id' => null,
                        'commission_earned' => 0
                    ];
                }
            }

            // 2. Save Transaction
            $transaction = Transaction::create([
                'branch_id' => $branch->id,
                'customer_id' => $request->customer_id,
                'total_amount' => $totalAmount,
                'payment_method' => $request->payment_method,
                'date' => now(),
            ]);

            foreach ($itemsToSave as $itemData) {
                $itemData['transaction_id'] = $transaction->id;
                TransactionDetail::create($itemData);
            }

            // 3. Deduct Owner Balance
            $deposit->decrement('balance', $systemFee);
            DepositHistory::create([
                'owner_id' => $ownerId,
                'amount' => $systemFee,
                'type' => 'deduction',
                'status' => 'approved',
                'description' => 'Biaya sistem transaksi #' . $transaction->id,
            ]);

            return response()->json([
                'message' => 'Transaksi berhasil disimpan.',
                'transaction' => $transaction->load('details')
            ], 201);
        });
    }

    public function getSalesReport(Request $request)
    {
        $kasir = $request->user();
        $transactions = Transaction::where('branch_id', $kasir->branch_id)
            ->with(['details' => function($q) { $q->where('item_type', 'product'); }])
            ->latest()
            ->get();
        return response()->json($transactions);
    }

    public function getServiceReport(Request $request)
    {
        $kasir = $request->user();
        $transactions = Transaction::where('branch_id', $kasir->branch_id)
            ->with(['details' => function($q) { $q->where('item_type', 'service'); }])
            ->latest()
            ->get();
        return response()->json($transactions);
    }

    /**
     * Daftar Seluruh Layanan (Service)
     */
    public function indexServices(Request $request)
    {
        $branch = Branch::findOrFail($request->user()->branch_id);
        $services = Service::where('owner_id', $branch->owner_id)->get();
        return response()->json($services);
    }

    /**
     * Daftar Produk di Cabang Kasir
     */
    public function indexProducts(Request $request)
    {
        $products = Product::where('branch_id', $request->user()->branch_id)->get();
        return response()->json($products);
    }
}
