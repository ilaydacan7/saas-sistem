<?php

declare(strict_types=1);

use App\Tenancy\TenantContext;
use Illuminate\Support\Facades\Route;

Route::get('/', function (TenantContext $context) {
    return view('welcome', ['tenant' => $context->get()]);
})->name('home');

Route::view('/faturalandirma/odeme-gerekli', 'billing.overdue')->name('billing.overdue');
