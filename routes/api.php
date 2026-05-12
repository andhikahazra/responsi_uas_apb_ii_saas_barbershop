<?php

use App\Http\Controllers\Api\AuthController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::post('/auth/login/developer', [AuthController::class, 'loginDeveloper']);
Route::post('/auth/login/owner', [AuthController::class, 'loginOwner']);
Route::post('/auth/login/kasir', [AuthController::class, 'loginKasir']);
Route::post('/auth/login/staff', [AuthController::class, 'loginStaff']);
Route::post('/auth/login/customer', [AuthController::class, 'loginCustomer']);

Route::post('/auth/register-owner', [AuthController::class, 'registerOwner']);
Route::post('/auth/register-customer', [AuthController::class, 'registerCustomer']);

Route::middleware(['auth:sanctum'])->group(function () {
    Route::post('/auth/logout', [AuthController::class, 'logout']);

    // Developer Routes
    Route::middleware(['role:developer'])->prefix('developer')->group(function () {
        Route::get('/owners', [\App\Http\Controllers\Api\DeveloperController::class, 'indexOwners']);
        Route::put('/owners/{id}/approve', [\App\Http\Controllers\Api\DeveloperController::class, 'approveOwner']);
        Route::get('/dashboard', [\App\Http\Controllers\Api\DeveloperController::class, 'getDashboard']);
        Route::get('/deposits/requests', [\App\Http\Controllers\Api\DeveloperController::class, 'getTopupRequests']);
        Route::put('/deposits/requests/{id}/approve', [\App\Http\Controllers\Api\DeveloperController::class, 'approveTopup']);
        Route::put('/deposits/requests/{id}/reject', [\App\Http\Controllers\Api\DeveloperController::class, 'rejectTopup']);
        Route::put('/system-fee', [\App\Http\Controllers\Api\DeveloperController::class, 'updateSystemFee']);
        Route::get('/transactions', [\App\Http\Controllers\Api\DeveloperController::class, 'getSystemTransactions']);
    });

    // Owner Routes
    Route::middleware(['role:owner'])->prefix('owner')->group(function () {
        Route::get('/profile', [\App\Http\Controllers\Api\OwnerController::class, 'getProfile']);
        Route::put('/profile', [\App\Http\Controllers\Api\OwnerController::class, 'updateProfile']);
        
        // Custom actions for owner controller because apiResource might conflict with unified controller
        Route::get('/branches', [\App\Http\Controllers\Api\OwnerController::class, 'indexBranches']);
        Route::post('/branches', [\App\Http\Controllers\Api\OwnerController::class, 'storeBranch']);
        Route::put('/branches/{id}', [\App\Http\Controllers\Api\OwnerController::class, 'updateBranch']);
        Route::delete('/branches/{id}', [\App\Http\Controllers\Api\OwnerController::class, 'deleteBranch']);

        Route::get('/services', [\App\Http\Controllers\Api\OwnerController::class, 'indexServices']);
        Route::post('/services', [\App\Http\Controllers\Api\OwnerController::class, 'storeService']);
        Route::put('/services/{id}', [\App\Http\Controllers\Api\OwnerController::class, 'updateService']);
        Route::delete('/services/{id}', [\App\Http\Controllers\Api\OwnerController::class, 'deleteService']);

        Route::get('/products', [\App\Http\Controllers\Api\OwnerController::class, 'indexProducts']);
        Route::post('/products', [\App\Http\Controllers\Api\OwnerController::class, 'storeProduct']);
        Route::put('/products/{id}', [\App\Http\Controllers\Api\OwnerController::class, 'updateProduct']);
        Route::delete('/products/{id}', [\App\Http\Controllers\Api\OwnerController::class, 'deleteProduct']);

        Route::get('/employees', [\App\Http\Controllers\Api\OwnerController::class, 'indexEmployees']);
        Route::post('/employees', [\App\Http\Controllers\Api\OwnerController::class, 'storeEmployee']);

        Route::get('/deposits', [\App\Http\Controllers\Api\OwnerController::class, 'getDepositBalance']);
        Route::post('/deposits/topup', [\App\Http\Controllers\Api\OwnerController::class, 'requestTopup']);

        Route::get('/reports/sales', [\App\Http\Controllers\Api\OwnerController::class, 'salesReport']);
        Route::get('/reports/services', [\App\Http\Controllers\Api\OwnerController::class, 'serviceReport']);
        Route::get('/reports/payrolls', [\App\Http\Controllers\Api\OwnerController::class, 'payrollReport']);
    });

    // Kasir Routes
    Route::middleware(['role:kasir'])->prefix('kasir')->group(function () {
        Route::get('/customers', [\App\Http\Controllers\Api\KasirController::class, 'indexCustomers']);
        Route::post('/customers', [\App\Http\Controllers\Api\KasirController::class, 'storeCustomer']);
        Route::post('/attendance/check-in', [\App\Http\Controllers\Api\KasirController::class, 'checkIn']);
        Route::post('/attendance/check-out', [\App\Http\Controllers\Api\KasirController::class, 'checkOut']);
        Route::get('/attendance/history', [\App\Http\Controllers\Api\KasirController::class, 'getAttendanceHistory']);
        Route::get('/salary-summary', [\App\Http\Controllers\Api\KasirController::class, 'getSalarySummary']);
        Route::get('/available-staff', [\App\Http\Controllers\Api\KasirController::class, 'indexAvailableStaff']);
        Route::post('/restocks', [\App\Http\Controllers\Api\KasirController::class, 'storeRestock']);
        Route::post('/transactions', [\App\Http\Controllers\Api\KasirController::class, 'storeTransaction']);
        Route::get('/transactions', [\App\Http\Controllers\Api\KasirController::class, 'getSalesReport']); 
        Route::get('/services', [\App\Http\Controllers\Api\KasirController::class, 'indexServices']);
        Route::get('/products', [\App\Http\Controllers\Api\KasirController::class, 'indexProducts']);
        Route::get('/reports/sales', [\App\Http\Controllers\Api\KasirController::class, 'getSalesReport']);
        Route::get('/reports/services', [\App\Http\Controllers\Api\KasirController::class, 'getServiceReport']);
    });

    // Staff Routes
    Route::middleware(['role:staff'])->prefix('staff')->group(function () {
        Route::get('/profile', [\App\Http\Controllers\Api\StaffController::class, 'getProfile']);
        Route::put('/profile', [\App\Http\Controllers\Api\StaffController::class, 'updateProfile']);
        Route::post('/attendance/check-in', [\App\Http\Controllers\Api\StaffController::class, 'checkIn']);
        Route::post('/attendance/check-out', [\App\Http\Controllers\Api\StaffController::class, 'checkOut']);
        Route::get('/attendance/history', [\App\Http\Controllers\Api\StaffController::class, 'getAttendanceHistory']);
        Route::get('/customers', [\App\Http\Controllers\Api\StaffController::class, 'indexCustomers']);
        Route::post('/galleries', [\App\Http\Controllers\Api\StaffController::class, 'storeGallery']);
        Route::get('/salary-summary', [\App\Http\Controllers\Api\StaffController::class, 'getSalarySummary']);
    });

    // Customer Routes
    Route::middleware(['role:customer'])->prefix('customer')->group(function () {
        Route::get('/profile', [\App\Http\Controllers\Api\CustomerController::class, 'getProfile']);
        Route::put('/profile', [\App\Http\Controllers\Api\CustomerController::class, 'updateProfile']);
        Route::get('/branches', [\App\Http\Controllers\Api\CustomerController::class, 'indexBranches']);
        Route::get('/branches/{id}/catalog', [\App\Http\Controllers\Api\CustomerController::class, 'getBranchCatalog']);
        Route::get('/visit-history', [\App\Http\Controllers\Api\CustomerController::class, 'getVisitHistory']);
        Route::get('/galleries', [\App\Http\Controllers\Api\CustomerController::class, 'getGalleries']);
    });
});
