<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\Gallery;
use App\Models\Transaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

/**
 * @tags Customer
 */
class CustomerController extends Controller
{
    // Profile
    public function getProfile(Request $request)
    {
        return response()->json($request->user());
    }

    /**
     * Update Profil Customer
     * @bodyParam name string example="Rizky Pelanggan Updated"
     * @bodyParam email string example="test@user.com"
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
     * Daftar Barbershop yang Tersedia
     */
    public function indexBranches()
    {
        return response()->json(Branch::all());
    }

    /**
     * Katalog Cabang (Jasa & Produk)
     * @urlParam id integer example=1
     */
    public function getBranchCatalog($id)
    {
        $branch = Branch::findOrFail($id);
        
        return response()->json([
            'branch' => $branch,
            'services' => \App\Models\Service::where('owner_id', $branch->owner_id)->get(),
            'products' => \App\Models\Product::where('branch_id', $id)->get(),
        ]);
    }

    /**
     * Histori Kunjungan Saya
     */
    public function getVisitHistory(Request $request)
    {
        $history = Transaction::where('customer_id', $request->user()->id)
            ->with(['branch', 'details.staff', 'details.item'])
            ->latest()
            ->get();
        return response()->json($history);
    }

    /**
     * Galeri Foto Rambut Saya
     */
    public function getGalleries(Request $request)
    {
        $galleries = Gallery::where('customer_id', $request->user()->id)
            ->with(['staff', 'transaction'])
            ->latest()
            ->get();
        return response()->json($galleries);
    }
}
