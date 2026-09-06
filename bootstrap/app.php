<?php

use App\Http\Middleware\EnsureCanManageTeam;
use App\Http\Middleware\EnsureCentralDomain;
use App\Http\Middleware\EnsureSuperAdmin;
use App\Http\Middleware\EnsureTenantDomain;
use App\Http\Middleware\EnsureTenantIsActive;
use App\Http\Middleware\ResolveTenant;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->web(prepend: [
            ResolveTenant::class,
            EnsureTenantIsActive::class,
        ]);

        $middleware->redirectGuestsTo(fn (Request $request) => $request->is('yonetim*') ? '/yonetim/giris' : '/giris');
        $middleware->redirectUsersTo(fn (Request $request) => $request->user()?->isSuperAdmin() ? '/yonetim' : '/panel');

        $middleware->alias([
            'tenant' => ResolveTenant::class,
            'tenant.active' => EnsureTenantIsActive::class,
            'tenant.only' => EnsureTenantDomain::class,
            'central' => EnsureCentralDomain::class,
            'superadmin' => EnsureSuperAdmin::class,
            'ekip' => EnsureCanManageTeam::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {})->create();
