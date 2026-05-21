<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use Illuminate\Support\Facades\Route;

// -------------------------------------------------------
// Guest routes (belum login)
// -------------------------------------------------------
Route::middleware('guest:app')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login']);
});

// -------------------------------------------------------
// Authenticated routes
// -------------------------------------------------------
Route::middleware(['auth:app', 'active'])->group(function () {
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

    // Dashboard
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

    // Placeholder routes agar nav-item tidak error
    Route::get('/vouchers',        fn() => 'Coming soon')->name('vouchers.index');
    Route::get('/voucher-batches', fn() => 'Coming soon')->name('voucher-batches.index');
    Route::get('/members',         fn() => 'Coming soon')->name('members.index');
    Route::get('/billing',         fn() => 'Coming soon')->name('billing.index');
    Route::get('/plans',           fn() => 'Coming soon')->name('plans.index');
    Route::get('/routers',         fn() => 'Coming soon')->name('routers.index');
    Route::get('/users',           fn() => 'Coming soon')->name('users.index');
    Route::get('/reports',         fn() => 'Coming soon')->name('reports.index');
});
