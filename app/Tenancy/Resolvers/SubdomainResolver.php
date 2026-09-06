<?php

declare(strict_types=1);

namespace App\Tenancy\Resolvers;

use App\Models\Tenant;
use Illuminate\Http\Request;

final class SubdomainResolver implements TenantResolver
{
    public function resolve(Request $request): ?Tenant
    {
        $host = strtolower($request->getHost());
        $baseDomain = strtolower((string) config('tenancy.base_domain'));

        if ($baseDomain === '' || ! str_ends_with($host, '.'.$baseDomain)) {
            return null;
        }

        $slug = substr($host, 0, -(strlen($baseDomain) + 1));

        if ($slug === '' || str_contains($slug, '.')) {
            return null;
        }

        if (in_array($slug, config('tenancy.reserved_subdomains', []), true)) {
            return null;
        }

        return Tenant::query()->where('slug', $slug)->first();
    }
}
