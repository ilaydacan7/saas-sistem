<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Tenancy\Exceptions\TenantNotFoundException;
use App\Tenancy\TenantContext;
use App\Tenancy\TenantResolverManager;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ResolveTenant
{
    public function __construct(
        private readonly TenantResolverManager $manager,
        private readonly TenantContext $context,
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        if ($this->manager->isCentralHost($request)) {
            return $next($request);
        }

        $tenant = $this->manager->resolve($request)
            ?? throw TenantNotFoundException::forHost($request->getHost());

        $this->context->set($tenant);

        app()->instance('tenant', $tenant);

        return $next($request);
    }
}
