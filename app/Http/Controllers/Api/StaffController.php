<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\Gallery;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;

/**
 * @tags Staff
 */
class StaffController extends Controller
{
    // Profile
    public function getProfile(Request $request)
    {
        return response()->json($request->user());
    }

    /**
     * Update Profil Staff
     * @bodyParam name string example="Joni Barberman Updated"
     * @bodyParam email string example="staff@barber.com"
     * @bodyParam password string example="newpassword"
     * @bodyParam password_confirmation string example="newpassword"
     */
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
     * Absen Masuk (Check-in)
     * 
     * Mencatat waktu masuk dan mengunci gaji pokok harian saat ini.
     */
    public function checkIn(Request $request)
    {
        $user = $request->user();
        
        // Cek apakah sudah absen hari ini
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
     * Daftar Pelanggan (Untuk Tag di Gallery)
     */
    public function indexCustomers()
    {
        return response()->json(User::where('role', 'customer')->get());
    }

    /**
     * Unggah Foto Hasil Kerja ke Galeri Pelanggan
     * @bodyParam customer_id integer example=5
     * @bodyParam transaction_id integer example=1
     * @bodyParam image file
     */
    public function storeGallery(Request $request)
    {
        $request->validate([
            'customer_id' => 'required|exists:users,id',
            'transaction_id' => 'nullable|exists:transactions,id',
            'image' => 'required|image|max:2048',
        ]);

        $path = $request->file('image')->store('galleries', 'public');

        $gallery = Gallery::create([
            'customer_id' => $request->customer_id,
            'staff_id' => $request->user()->id,
            'transaction_id' => $request->transaction_id,
            'photo_url' => $path,
        ]);

        return response()->json($gallery, 201);
    }

    /**
     * Rekap Gaji & Komisi Bulan Ini
     */
    public function getSalarySummary(Request $request)
    {
        $user = $request->user();
        $month = now()->month;

        $baseSalary = Attendance::where('user_id', $user->id)
            ->whereMonth('date', $month)
            ->sum('daily_base_salary_earned');

        $commissions = \App\Models\TransactionDetail::where('staff_id', $user->id)
            ->whereHas('transaction', function($q) use ($month) {
                $q->whereMonth('date', $month);
            })
            ->sum('commission_earned');

        return response()->json([
            'month' => now()->format('F Y'),
            'total_base_salary' => (float)$baseSalary,
            'total_commissions' => (float)$commissions,
            'take_home_pay' => (float)$baseSalary + (float)$commissions,
        ]);
    }
}
