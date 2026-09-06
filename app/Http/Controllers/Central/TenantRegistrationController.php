<?php

declare(strict_types=1);

namespace App\Http\Controllers\Central;

use App\Http\Controllers\Controller;
use App\Http\Requests\RegisterTenantRequest;
use App\Tenancy\TenantRegistrar;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;

class TenantRegistrationController extends Controller
{
    public function create(): View
    {
        return view('central.register', [
            'baseDomain' => config('tenancy.base_domain'),
            'trialDays' => config('billing.trial_days'),
        ]);
    }

    public function store(RegisterTenantRequest $request, TenantRegistrar $registrar): RedirectResponse
    {
        [$tenant] = $registrar->register($request->validated());

        return redirect()->away($this->loginUrl($tenant->host()));
    }

    private function loginUrl(string $host): string
    {
        $port = request()->getPort();
        $scheme = request()->getScheme();
        $suffix = in_array($port, [80, 443], true) ? '' : ':'.$port;

        return $scheme.'://'.$host.$suffix.'/giris?kayit=tamam';
    }
}
