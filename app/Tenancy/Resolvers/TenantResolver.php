<?php

declare(strict_types=1);

namespace App\Tenancy\Resolvers;

use App\Models\Tenant;
use Illuminate\Http\Request;

interface TenantResolver
{
    public function resolve(Request $request): ?Tenant;
}
