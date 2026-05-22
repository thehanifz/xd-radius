<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\BillingController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\MemberController;
use App\Http\Controllers\PlanController;
use App\Http\Controllers\UserPreferenceController;
use App\Http\Controllers\VoucherController;
use Illuminate\Support\Facades\Route;

// ─── Auth ────────────────────────────────────────────────────────────────────
Route::middleware('guest')->group(function () {
    Route::get('/login',  [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login']);
});
Route::post('/logout', [AuthController::class, 'logout'])->name('logout')->middleware('auth');

// ─── App (requires login) ────────────────────────────────────────────────────
Route::middleware('auth')->group(function () {

    // Dashboard
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

    // ─── User Preferences (DB-based, bukan localStorage) ─────────────────────
    Route::get('/user/preferences',          [UserPreferenceController::class, 'index'])->name('preferences.index');
    Route::post('/user/preferences',         [UserPreferenceController::class, 'store'])->name('preferences.store');
    Route::delete('/user/preferences/{key}', [UserPreferenceController::class, 'destroy'])->name('preferences.destroy');

    // ─── Plans ───────────────────────────────────────────────────────────────
    Route::resource('plans', PlanController::class);
    Route::patch('/plans/{plan}/toggle', [PlanController::class, 'toggleActive'])
        ->name('plans.toggle');

    // ─── Vouchers ────────────────────────────────────────────────────────────
    Route::get('/vouchers',                        [VoucherController::class, 'index'])->name('vouchers.index');
    Route::get('/vouchers/create',                 [VoucherController::class, 'create'])->name('vouchers.create');
    Route::post('/vouchers/generate',              [VoucherController::class, 'generate'])->name('vouchers.generate');

    // AJAX preview format username (dipanggil Alpine.js, BUKAN batch preview)
    Route::get('/vouchers/preview-format',         [VoucherController::class, 'previewFormat'])->name('vouchers.preview-format');

    // Print — harus sebelum /{voucher} agar tidak tertangkap sebagai show
    Route::get('/vouchers/batch/{batch}/print',    [VoucherController::class, 'print'])->name('vouchers.print');

    // Show detail voucher
    Route::get('/vouchers/{voucher}',              [VoucherController::class, 'show'])->name('vouchers.show');

    // ─── Members ─────────────────────────────────────────────────────────────
    Route::resource('members', MemberController::class);
    Route::patch('/members/{member}/toggle-status', [MemberController::class, 'toggleStatus'])
        ->name('members.toggle-status');

    // ─── Billing ─────────────────────────────────────────────────────────────
    Route::get('/billing',                     [BillingController::class, 'index'])->name('billing.index');
    Route::get('/billing/create',              [BillingController::class, 'create'])->name('billing.create');
    Route::post('/billing',                    [BillingController::class, 'store'])->name('billing.store');
    Route::get('/billing/{billing}',           [BillingController::class, 'show'])->name('billing.show');
    Route::get('/billing/{billing}/pay',       [BillingController::class, 'payForm'])->name('billing.pay.form');
    Route::post('/billing/{billing}/pay',      [BillingController::class, 'pay'])->name('billing.pay');
    Route::patch('/billing/{billing}/cancel',  [BillingController::class, 'cancel'])->name('billing.cancel');
    Route::get('/billing/{billing}/pdf',       [BillingController::class, 'pdf'])->name('billing.pdf');
});
