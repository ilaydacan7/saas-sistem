<?php

declare(strict_types=1);

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Reporting\DashboardReport;
use App\Tenancy\TenantContext;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function __invoke(Request $request, TenantContext $context): View
    {
        $tenant = $context->getOrFail();
        $donem = DashboardReport::donem($request->query('donem'));
        $rapor = new DashboardReport($tenant);

        return view('panel', [
            'tenant' => $tenant,
            'moduller' => $tenant->enabledModules(),
            'donem' => $donem,
            'kartlar' => $rapor->kartlar(),
            'seri' => $rapor->satisSerisi($donem['anahtar']),
            'cokSatanlar' => $rapor->cokSatanlar(),
            'bekleyenler' => $rapor->bekleyenTahsilatlar(),
            'bekleyenToplam' => $rapor->bekleyenTahsilatToplami(),
            'kritikStok' => $rapor->kritikStok(),
            'kalanGun' => $tenant->trial_ends_at?->isFuture()
                ? (int) now()->startOfDay()->diffInDays($tenant->trial_ends_at->startOfDay())
                : null,
        ]);
    }
}
