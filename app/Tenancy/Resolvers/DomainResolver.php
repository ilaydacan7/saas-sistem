<?php

declare(strict_types=1);

namespace App\Tenancy\Resolvers;

use App\Models\Tenant;
use Illuminate\Http\Request;

final class DomainResolver implements TenantResolver
{
    public function resolve(Request $request): ?Tenant
    {
        $host = strtolower($request->getHost());

        return Tenant::query()->where('custom_domain', $host)->first();
    }
}
