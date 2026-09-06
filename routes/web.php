<?php

declare(strict_types=1);

use App\Http\Controllers\Central\SuperAdminLoginController;
use App\Http\Controllers\Central\TenantAdminController;
use App\Http\Controllers\Central\TenantRegistrationController;
use App\Http\Controllers\Tenant\DashboardController;
use App\Http\Controllers\Tenant\LoginController;
use App\Tenancy\TenantContext;
use Illuminate\Support\Facades\Route;

Route::get('/', function (TenantContext $context) {
    if ($context->has()) {
        return redirect()->route('panel');
    }

    return view('welcome');
})->name('home');

Route::middleware('central')->group(function () {
    Route::get('/kayit', [TenantRegistrationController::class, 'create'])->name('kayit');
    Route::post('/kayit', [TenantRegistrationController::class, 'store']);

    Route::prefix('yonetim')->name('yonetim.')->group(function () {
        Route::middleware('guest')->group(function () {
            Route::get('/giris', [SuperAdminLoginController::class, 'create'])->name('giris');
            Route::post('/giris', [SuperAdminLoginController::class, 'store']);
        });

        Route::middleware(['auth', 'superadmin'])->group(function () {
            Route::get('/', [TenantAdminController::class, 'index'])->name('index');
            Route::post('/cikis', [SuperAdminLoginController::class, 'destroy'])->name('cikis');

            Route::prefix('{tenant}')->group(function () {
                Route::get('/', [TenantAdminController::class, 'show'])->name('detay');
                Route::post('/odeme', [TenantAdminController::class, 'recordPayment'])->name('odeme');
                Route::post('/askiya-al', [TenantAdminController::class, 'suspend'])->name('askiya-al');
                Route::post('/aktiflestir', [TenantAdminController::class, 'reactivate'])->name('aktiflestir');
                Route::post('/deneme-uzat', [TenantAdminController::class, 'extendTrial'])->name('deneme-uzat');
            });
        });
    });
});

Route::middleware('tenant.only')->group(function () {
    Route::middleware('guest')->group(function () {
        Route::get('/giris', [LoginController::class, 'create'])->name('giris');
        Route::post('/giris', [LoginController::class, 'store']);
    });

    Route::middleware('auth')->group(function () {
        Route::get('/panel', DashboardController::class)->name('panel');
        Route::post('/cikis', [LoginController::class, 'destroy'])->name('logout');
    });

    Route::view('/faturalandirma/odeme-gerekli', 'billing.overdue')->name('billing.overdue');
});
