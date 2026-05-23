<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\BillingController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\MemberController;
use App\Http\Controllers\OnboardingController;
use App\Http\Controllers\OnlineSessionController;
use App\Http\Controllers\OperatorController;
use App\Http\Controllers\PlanController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\RouterController;
use App\Http\Controllers\SystemSettingController;
use App\Http\Controllers\UserPreferenceController;
use App\Http\Controllers\VoucherController;
use Illuminate\Support\Facades\Route;

// ─── Onboarding (sebelum auth) ───────────────────────────────────────────────
Route::get('/setup',  [OnboardingController::class, 'show'])->name('onboarding.show');
Route::post('/setup', [OnboardingController::class, 'store'])->name('onboarding.store');

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

    // ─── User Preferences ────────────────────────────────────────────────────
    Route::get('/user/preferences',          [UserPreferenceController::class, 'index'])->name('preferences.index');
    Route::post('/user/preferences',         [UserPreferenceController::class, 'store'])->name('preferences.store');
    Route::delete('/user/preferences/{key}', [UserPreferenceController::class, 'destroy'])->name('preferences.destroy');

    // ─── Plans ───────────────────────────────────────────────────────────────
    Route::resource('plans', PlanController::class);
    Route::patch('/plans/{plan}/toggle', [PlanController::class, 'toggleActive'])->name('plans.toggle');

    // ─── Vouchers ────────────────────────────────────────────────────────────
    Route::get('/vouchers',                        [VoucherController::class, 'index'])->name('vouchers.index');
    Route::get('/vouchers/create',                 [VoucherController::class, 'create'])->name('vouchers.create');
    Route::post('/vouchers/generate',              [VoucherController::class, 'generate'])->name('vouchers.generate');
    Route::get('/vouchers/preview-format',         [VoucherController::class, 'previewFormat'])->name('vouchers.preview-format');
    Route::get('/vouchers/batch/{batch}/print',    [VoucherController::class, 'print'])->name('vouchers.print');
    Route::get('/vouchers/{voucher}',              [VoucherController::class, 'show'])->name('vouchers.show');

    // ─── Members ─────────────────────────────────────────────────────────────
    Route::resource('members', MemberController::class);
    Route::patch('/members/{member}/toggle-status', [MemberController::class, 'toggleStatus'])->name('members.toggle-status');

    // ─── Billing ─────────────────────────────────────────────────────────────
    Route::get('/billing',                     [BillingController::class, 'index'])->name('billing.index');
    Route::get('/billing/create',              [BillingController::class, 'create'])->name('billing.create');
    Route::post('/billing',                    [BillingController::class, 'store'])->name('billing.store');
    Route::get('/billing/{billing}',           [BillingController::class, 'show'])->name('billing.show');
    Route::get('/billing/{billing}/pay',       [BillingController::class, 'payForm'])->name('billing.pay.form');
    Route::post('/billing/{billing}/pay',      [BillingController::class, 'pay'])->name('billing.pay');
    Route::patch('/billing/{billing}/cancel',  [BillingController::class, 'cancel'])->name('billing.cancel');
    Route::get('/billing/{billing}/pdf',       [BillingController::class, 'pdf'])->name('billing.pdf');

    // ─── Online Sessions ─────────────────────────────────────────────────────
    Route::get('/online', [OnlineSessionController::class, 'index'])->name('online.index');

    // ─── Reports (superuser only) ─────────────────────────────────────────────
    Route::get('/reports/monthly',     [ReportController::class, 'index'])->name('reports.index');
    Route::get('/reports/monthly/pdf', [ReportController::class, 'pdf'])->name('reports.pdf');

    // ─── Routers / NAS ───────────────────────────────────────────────────────
    Route::resource('routers', RouterController::class);
    Route::patch('/routers/{router}/toggle', [RouterController::class, 'toggleActive'])->name('routers.toggle');

    // ─── Operators (superuser only) ──────────────────────────────────────────
    Route::get('/operators',                      [OperatorController::class, 'index'])->name('operators.index');
    Route::get('/operators/create',               [OperatorController::class, 'create'])->name('operators.create');
    Route::post('/operators',                     [OperatorController::class, 'store'])->name('operators.store');
    Route::get('/operators/{operator}/edit',      [OperatorController::class, 'edit'])->name('operators.edit');
    Route::put('/operators/{operator}',           [OperatorController::class, 'update'])->name('operators.update');
    Route::patch('/operators/{operator}/toggle',  [OperatorController::class, 'toggleActive'])->name('operators.toggle');
    Route::delete('/operators/{operator}',        [OperatorController::class, 'destroy'])->name('operators.destroy');

    // ─── System Settings (superuser only) ────────────────────────────────────
    Route::get('/settings',  [SystemSettingController::class, 'index'])->name('settings.index');
    Route::put('/settings',  [SystemSettingController::class, 'update'])->name('settings.update');
});
