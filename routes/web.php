<?php

declare(strict_types=1);

use App\Http\Controllers\Central\SuperAdminLoginController;
use App\Http\Controllers\Central\TenantAdminController;
use App\Http\Controllers\Central\TenantRegistrationController;
use App\Http\Controllers\Tenant\DashboardController;
use App\Http\Controllers\Tenant\ForgotPasswordController;
use App\Http\Controllers\Tenant\InvitationAcceptController;
use App\Http\Controllers\Tenant\LoginController;
use App\Http\Controllers\Tenant\ResetPasswordController;
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
