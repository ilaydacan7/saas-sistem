<?php

declare(strict_types=1);

use App\Http\Controllers\Central\SuperAdminLoginController;
use App\Http\Controllers\Central\TenantAdminController;
use App\Http\Controllers\Central\TenantRegistrationController;
use App\Http\Controllers\Tenant\CustomerController;
use App\Http\Controllers\Tenant\DashboardController;
use App\Http\Controllers\Tenant\ForgotPasswordController;
use App\Http\Controllers\Tenant\InvitationAcceptController;
use App\Http\Controllers\Tenant\LoginController;
use App\Http\Controllers\Tenant\ModuleSettingsController;
use App\Http\Controllers\Tenant\ProductController;
use App\Http\Controllers\Tenant\ResetPasswordController;
use App\Http\Controllers\Tenant\StockMovementController;
use App\Http\Controllers\Tenant\TeamController;
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

        Route::get('/parola/unuttum', [ForgotPasswordController::class, 'create'])->name('parola.unuttum');
        Route::post('/parola/unuttum', [ForgotPasswordController::class, 'store']);

        Route::get('/parola/sifirla/{token}', [ResetPasswordController::class, 'create'])->name('parola.sifirla');
        Route::post('/parola/sifirla', [ResetPasswordController::class, 'store']);

        Route::get('/davet/{token}', [InvitationAcceptController::class, 'create'])->name('davet.kabul');
        Route::post('/davet/{token}', [InvitationAcceptController::class, 'store']);
    });

    Route::middleware('auth')->group(function () {
        Route::get('/panel', DashboardController::class)->name('panel');
        Route::post('/cikis', [LoginController::class, 'destroy'])->name('logout');

        Route::middleware(['modul:musteriler'])->prefix('musteriler')->name('musteriler.')->group(function () {
            Route::get('/', [CustomerController::class, 'index'])->name('index');
            Route::get('/yeni', [CustomerController::class, 'create'])->name('create');
            Route::post('/', [CustomerController::class, 'store'])->name('store');
            Route::get('/{customer}/duzenle', [CustomerController::class, 'edit'])->name('edit');
            Route::put('/{customer}', [CustomerController::class, 'update'])->name('update');
            Route::delete('/{customer}', [CustomerController::class, 'destroy'])->name('destroy');
        });

        Route::middleware(['modul:stok'])->prefix('stok')->name('stok.')->group(function () {
            Route::get('/', [ProductController::class, 'index'])->name('index');
            Route::get('/yeni', [ProductController::class, 'create'])->name('create');
            Route::post('/', [ProductController::class, 'store'])->name('store');
            Route::get('/hareketler', [StockMovementController::class, 'index'])->name('hareketler');
            Route::get('/hareket', [StockMovementController::class, 'create'])->name('hareket');
            Route::post('/hareket', [StockMovementController::class, 'store'])->name('hareket.kaydet');
            Route::get('/{product}/duzenle', [ProductController::class, 'edit'])->name('edit');
            Route::put('/{product}', [ProductController::class, 'update'])->name('update');
            Route::delete('/{product}', [ProductController::class, 'destroy'])->name('destroy');
        });

        Route::middleware('ekip')->prefix('ayarlar')->name('ayarlar.')->group(function () {
            Route::get('/moduller', [ModuleSettingsController::class, 'index'])->name('moduller');
            Route::patch('/moduller', [ModuleSettingsController::class, 'update'])->name('moduller.guncelle');
        });

        Route::middleware('ekip')->prefix('ekip')->name('ekip.')->group(function () {
            Route::get('/', [TeamController::class, 'index'])->name('index');
            Route::post('/davet', [TeamController::class, 'invite'])->name('davet');
            Route::delete('/davet/{invitation}', [TeamController::class, 'cancelInvite'])->name('davet.iptal');
            Route::patch('/{user}/rol', [TeamController::class, 'updateRole'])->name('rol');
            Route::delete('/{user}', [TeamController::class, 'remove'])->name('cikar');
        });
    });

    Route::view('/faturalandirma/odeme-gerekli', 'billing.overdue')->name('billing.overdue');
});
