<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\DepositHistory;
use App\Models\OwnerDeposit;
use App\Models\Product;
use App\Models\Service;
use App\Models\Transaction;
use App\Models\User;
use App\Http\Requests\StoreProductRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

/**
 * @tags Owner
 */
class OwnerController extends Controller
{
    // Profile
    public function getProfile(Request $request)
    {
        return response()->json($request->user());
    }

    public function updateProfile(Request $request)
    {
        $user = $request->user();
        $data = $request->validate([
            'name' => 'sometimes|string|max:255',
            'email' => 'sometimes|string|email|max:255|unique:users,email,' . $user->id,
            'password' => 'sometimes|string|min:8|confirmed',
        ]);

        if (isset($data['password'])) {
            $data['password'] = Hash::make($data['password']);
        }

        $user->update($data);
        return response()->json(['message' => 'Profil berhasil diperbarui.', 'user' => $user]);
    }

    /**
     * Daftar Cabang
     */
    public function indexBranches(Request $request)
    {
        return response()->json($request->user()->branches);
    }

    /**
     * Tambah Cabang Baru
     * @bodyParam name string example="Barber King Cabang B"
     * @bodyParam address string example="Jl. Sudirman No. 5"
     */
    public function storeBranch(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string',
            'address' => 'required|string',
        ]);

        $branch = Branch::create([
            'owner_id' => $request->user()->id,
            'name' => $data['name'],
            'address' => $data['address'],
        ]);

        return response()->json($branch, 201);
    }

    /**
     * Daftar Layanan
     */
    public function indexServices(Request $request)
    {
        $services = Service::where('owner_id', $request->user()->id)->get();
        return response()->json($services);
    }

    /**
     * Tambah Layanan Baru
     * @bodyParam name string example="Hair Color"
     * @bodyParam price integer example=100000
     * @bodyParam commission_type string example="percentage"
     * @bodyParam commission_amount integer example=25
     */
    public function storeService(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string',
            'price' => 'required|numeric',
            'commission_type' => 'required|in:percentage,fixed',
            'commission_amount' => 'required|numeric',
        ]);

        $service = Service::create(array_merge($data, ['owner_id' => $request->user()->id]));
        return response()->json($service, 201);
    }

    /**
     * Daftar Produk per Cabang
     */
    public function indexProducts(Request $request)
    {
        $products = Product::whereIn('branch_id', $request->user()->branches->pluck('id'))->get();
        return response()->json($products);
    }

    /**
     * Tambah Produk Baru
     */
    public function storeProduct(StoreProductRequest $request)
    {
        $product = Product::create($request->validated());
        return response()->json($product, 201);
    }

    /**
     * Daftar Seluruh Karyawan (Kasir & Staff)
     */
    public function indexEmployees(Request $request)
    {
        $employees = User::whereIn('branch_id', $request->user()->branches->pluck('id'))
            ->whereIn('role', ['kasir', 'staff'])
            ->get();
        return response()->json($employees);
    }

    /**
     * Tambah Karyawan Baru
     * @bodyParam branch_id integer example=1
     * @bodyParam name string example="Siti Kasir"
     * @bodyParam email string example="siti@barber.com"
     * @bodyParam password string example="password"
     * @bodyParam role string example="kasir"
     * @bodyParam daily_base_salary integer example=45000
     */
    public function storeEmployee(Request $request)
    {
        $data = $request->validate([
            'branch_id' => 'required|exists:branches,id',
            'name' => 'required|string',
            'email' => 'required|email|unique:users',
            'password' => 'required|min:8',
            'role' => 'required|in:kasir,staff',
            'daily_base_salary' => 'nullable|numeric',
        ]);

        $employee = User::create(array_merge($data, [
            'password' => Hash::make($data['password']),
            'status' => 'active',
        ]));

        return response()->json($employee, 201);
    }

    /**
     * Cek Saldo SaaS
     */
    public function getDepositBalance(Request $request)
    {
        $deposit = OwnerDeposit::where('owner_id', $request->user()->id)->first();
        return response()->json($deposit);
    }

    /**
     * Request Top-up Saldo ke Developer
     * @bodyParam amount integer example=500000
     * @bodyParam description string example="Topup via Transfer"
     */
    public function requestTopup(Request $request)
    {
        $data = $request->validate([
            'amount' => 'required|numeric|min:10000',
            'description' => 'nullable|string',
        ]);

        $history = DepositHistory::create([
            'owner_id' => $request->user()->id,
            'amount' => $data['amount'],
            'type' => 'topup',
            'status' => 'pending',
            'description' => $data['description'],
        ]);

        return response()->json($history, 201);
    }

    /**
     * Laporan Penjualan (Sales)
     */
    public function salesReport(Request $request)
    {
        $branches = $request->user()->branches->pluck('id');
        $sales = Transaction::whereIn('branch_id', $branches)
            ->with(['branch', 'details'])
            ->latest()
            ->get();
        return response()->json($sales);
    }

    /**
     * Laporan Performa Layanan
     */
    public function serviceReport(Request $request)
    {
        $branches = $request->user()->branches->pluck('id');
        $services = \App\Models\TransactionDetail::whereHas('transaction', function($q) use ($branches) {
                $q->whereIn('branch_id', $branches);
            })
            ->where('item_type', 'service')
            ->with(['staff', 'transaction'])
            ->get();
        return response()->json($services);
    }

    /**
     * Laporan Penggajian (Payroll)
     */
    public function payrollReport(Request $request)
    {
        $branches = $request->user()->branches->pluck('id');
        $employeeIds = User::whereIn('branch_id', $branches)->pluck('id');
        
        $payrolls = \App\Models\Attendance::whereIn('user_id', $employeeIds)
            ->with('user')
            ->get();
        return response()->json($payrolls);
    }
}
