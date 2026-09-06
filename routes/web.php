<?php

declare(strict_types=1);

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
