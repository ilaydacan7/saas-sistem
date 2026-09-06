<?php

declare(strict_types=1);

namespace App\Tenancy\Resolvers;

use App\Models\Tenant;
use Illuminate\Http\Request;

final class HeaderResolver implements TenantResolver
{
    public function resolve(Request $request): ?Tenant
    {
        $slug = trim((string) $request->header('X-Tenant'));

        if ($slug === '') {
            return null;
        }

        return Tenant::query()->where('slug', strtolower($slug))->first();
    }
}
