<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use App\Http\Requests\DeveloperLoginRequest;
use App\Http\Requests\OwnerLoginRequest;
use App\Http\Requests\KasirLoginRequest;
use App\Http\Requests\StaffLoginRequest;
use App\Http\Requests\CustomerLoginRequest;

class AuthController extends Controller
{
    /**
     * Registrasi Akun Owner
     * @tags Owner
     */
    public function registerOwner(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8|confirmed',
        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role' => 'owner',
            'status' => 'pending',
        ]);

        return response()->json([
            'message' => 'Registrasi berhasil. Silakan tunggu aktivasi akun oleh Developer.',
            'user' => $user,
        ], 201);
    }

    /**
     * Registrasi Akun Customer
     * @tags Customer
     */
    public function registerCustomer(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8|confirmed',
        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role' => 'customer',
            'status' => 'active',
        ]);

        return response()->json([
            'message' => 'Registrasi berhasil. Silakan login.',
            'user' => $user,
        ], 201);
    }

    /**
     * Login Logic Helper
     */
    private function executeLogin($email, $password)
    {
        $user = User::where('email', $email)->first();

        if (!$user || !Hash::check($password, $user->password)) {
            return response()->json(['message' => 'Kredensial tidak valid.'], 401);
        }

        if ($user->status !== 'active') {
            return response()->json(['message' => 'Akun Anda belum aktif.'], 403);
        }

        $token = $user->createToken('auth_token', [$user->role])->plainTextToken;

        return response()->json([
            'access_token' => $token,
            'token_type' => 'Bearer',
            'user' => $user,
        ]);
    }

    /**
     * Login Developer
     * @tags Developer
     */
    public function loginDeveloper(DeveloperLoginRequest $request) 
    { 
        return $this->executeLogin($request->email, $request->password); 
    }

    /**
     * Login Owner
     * @tags Owner
     */
    public function loginOwner(OwnerLoginRequest $request) 
    { 
        return $this->executeLogin($request->email, $request->password); 
    }

    /**
     * Login Kasir
     * @tags Kasir
     */
    public function loginKasir(KasirLoginRequest $request) 
    { 
        return $this->executeLogin($request->email, $request->password); 
    }

    /**
     * Login Staff
     * @tags Staff
     */
    public function loginStaff(StaffLoginRequest $request) 
    { 
        return $this->executeLogin($request->email, $request->password); 
    }

    /**
     * Login Customer
     * @tags Customer
     */
    public function loginCustomer(CustomerLoginRequest $request) 
    { 
        return $this->executeLogin($request->email, $request->password); 
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();
        return response()->json(['message' => 'Logout berhasil.']);
    }
}
