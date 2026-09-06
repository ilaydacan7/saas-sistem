<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Tenancy\TenantContext;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;

class EnsureTenantIsActive
{
    public function __construct(private readonly TenantContext $context) {}

    public function handle(Request $request, Closure $next): Response
    {
        $tenant = $this->context->get();

        if ($tenant === null) {
            return $next($request);
        }

        if (! $tenant->canAccess()) {
            throw new HttpException(403, 'Bu hesap şu anda kullanıma kapalı: '.$tenant->status->label().'.');
        }

        if ($tenant->status->isBillingRestricted() && ! $this->isBillingRoute($request)) {
            return redirect()->route('billing.overdue');
        }

        return $next($request);
    }

    private function isBillingRoute(Request $request): bool
    {
        return $request->routeIs('billing.*', 'logout');
    }
}
