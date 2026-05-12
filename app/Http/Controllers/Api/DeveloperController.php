<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\DepositHistory;
use App\Models\OwnerDeposit;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * @tags Developer
 */
class DeveloperController extends Controller
{
    /**
     * Daftar Seluruh Owner
     */
    public function indexOwners()
    {
        return response()->json(User::where('role', 'owner')->get());
    }

    /**
     * Setujui Pendaftaran Owner
     * 
     * Mengaktifkan akun owner agar bisa login.
     * @bodyParam id integer example=2
     */
    public function approveOwner($id)
    {
        $user = User::findOrFail($id);
        $user->update(['status' => 'active']);
        
        // Initialize deposit for owner
        OwnerDeposit::firstOrCreate(['owner_id' => $user->id], ['balance' => 0]);

        return response()->json(['message' => 'Owner approved and activated.']);
    }

    /**
     * Dashboard Statistik Developer
     */
    public function getDashboard()
    {
        return response()->json([
            'total_owners' => User::where('role', 'owner')->count(),
            'total_branches' => \App\Models\Branch::count(),
            'total_transactions' => \App\Models\Transaction::count(),
            'total_system_fee_collected' => DepositHistory::where('type', 'deduction')->sum('amount'),
        ]);
    }

    /**
     * Daftar Permintaan Top-up Pending
     */
    public function getTopupRequests()
    {
        return response()->json(DepositHistory::where('type', 'topup')->where('status', 'pending')->with('owner')->get());
    }

    /**
     * Setujui Top-up Saldo Owner
     * 
     * @bodyParam id integer example=1
     */
    public function approveTopup($id)
    {
        return DB::transaction(function () use ($id) {
            $history = DepositHistory::findOrFail($id);
            if ($history->status !== 'pending') return response()->json(['message' => 'Request already processed.'], 400);

            $history->update(['status' => 'approved']);
            
            $deposit = OwnerDeposit::firstOrCreate(['owner_id' => $history->owner_id], ['balance' => 0]);
            $deposit->increment('balance', $history->amount);

            return response()->json(['message' => 'Topup approved and balance updated.']);
        });
    }

    /**
     * Tolak Top-up Saldo Owner
     * 
     * @bodyParam id integer example=1
     */
    public function rejectTopup($id)
    {
        $history = \App\Models\DepositHistory::findOrFail($id);
        $history->update(['status' => 'rejected']);
        return response()->json($history);
    }

    /**
     * Atur Biaya Sistem per Transaksi (Fee)
     * @bodyParam fee integer required example=2500
     */
    public function updateSystemFee(Request $request)
    {
        $data = $request->validate([
            'fee' => 'required|integer|min:0',
        ]);

        \DB::table('settings')->updateOrInsert(
            ['key' => 'system_transaction_fee'],
            ['value' => $data['fee'], 'updated_at' => now()]
        );

        return response()->json(['message' => 'Biaya sistem berhasil diperbarui.', 'fee' => $data['fee']]);
    }

    /**
     * Riwayat Transaksi Sistem (SaaS Fees)
     * 
     * Menampilkan seluruh pemotongan saldo owner dari tiap transaksi kasir.
     */
    public function getSystemTransactions()
    {
        $transactions = DepositHistory::where('type', 'deduction')
            ->with('owner')
            ->latest()
            ->get();
        return response()->json($transactions);
    }
}
