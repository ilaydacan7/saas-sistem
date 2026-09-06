<?php

declare(strict_types=1);

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Tenancy\TenantContext;
use Illuminate\Contracts\View\View;

class DashboardController extends Controller
{
    public function __invoke(TenantContext $context): View
    {
        $tenant = $context->getOrFail();

        return view('panel', [
            'tenant' => $tenant,
            'kalanGun' => $tenant->trial_ends_at?->isFuture()
                ? (int) now()->startOfDay()->diffInDays($tenant->trial_ends_at->startOfDay())
                : null,
        ]);
    }
}
