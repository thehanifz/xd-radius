<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\MemberController;
use App\Http\Controllers\PlanController;
use App\Http\Controllers\VoucherController;
use Illuminate\Support\Facades\Route;

// Auth
Route::middleware('guest')->group(function () {
    Route::get('/login',  [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login']);
});
Route::post('/logout', [AuthController::class, 'logout'])->name('logout')->middleware('auth');

// App
Route::middleware('auth')->group(function () {
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

    // Plans
    Route::resource('plans', PlanController::class);
    Route::patch('/plans/{plan}/toggle', [PlanController::class, 'toggleActive'])
        ->name('plans.toggle');

    // Vouchers
    Route::get('/vouchers',           [VoucherController::class, 'index'])->name('vouchers.index');
    Route::get('/vouchers/create',    [VoucherController::class, 'create'])->name('vouchers.create');
    Route::post('/vouchers/generate', [VoucherController::class, 'generate'])->name('vouchers.generate');
    Route::get('/vouchers/preview',   [VoucherController::class, 'preview'])->name('vouchers.preview');
    Route::get('/vouchers/{voucher}', [VoucherController::class, 'show'])->name('vouchers.show');

    // Members
    Route::resource('members', MemberController::class);
    Route::patch('/members/{member}/toggle-status', [MemberController::class, 'toggleStatus'])
        ->name('members.toggle-status');
});
