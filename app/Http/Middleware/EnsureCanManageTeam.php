<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;

class EnsureCanManageTeam
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user === null || ! $user->canManageTeam()) {
            throw new HttpException(403, 'Bu sayfaya erişim yetkiniz yok.');
        }

        return $next($request);
    }
}
