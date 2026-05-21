<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\PlanController;
use App\Http\Controllers\VoucherController;
use Illuminate\Support\Facades\Route;

// -------------------------------------------------------
// Guest routes
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

    // -------------------------------------------------------
    // Superuser only
    // -------------------------------------------------------
    Route::middleware('superuser')->group(function () {

        // Paket Internet
        Route::resource('plans', PlanController::class)->except(['show']);
        Route::patch('plans/{plan}/toggle', [PlanController::class, 'toggleActive'])->name('plans.toggle');

        // Placeholder routes (akan diisi bertahap)
        Route::get('/routers', fn() => 'Coming soon')->name('routers.index');
        Route::get('/users',   fn() => 'Coming soon')->name('users.index');
    });

    // -------------------------------------------------------
    // Superuser + Operator
    // -------------------------------------------------------

    // Vouchers
    Route::get('/vouchers',           [VoucherController::class, 'index'])->name('vouchers.index');
    Route::get('/vouchers/create',    [VoucherController::class, 'create'])->name('vouchers.create');
    Route::post('/vouchers/generate', [VoucherController::class, 'generate'])->name('vouchers.generate');
    Route::get('/vouchers/preview',   [VoucherController::class, 'preview'])->name('vouchers.preview');

    // Voucher Batches — alias ke vouchers.index untuk sementara
    Route::get('/voucher-batches', [VoucherController::class, 'index'])->name('voucher-batches.index');

    Route::get('/members', fn() => 'Coming soon')->name('members.index');
    Route::get('/billing', fn() => 'Coming soon')->name('billing.index');
    Route::get('/reports', fn() => 'Coming soon')->name('reports.index');
});
