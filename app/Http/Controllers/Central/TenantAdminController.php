<?php

declare(strict_types=1);

namespace App\Http\Controllers\Central;

use App\Billing\PaymentRecorder;
use App\Http\Controllers\Controller;
use App\Http\Requests\RecordPaymentRequest;
use App\Models\Payment;
use App\Models\Tenant;
use App\Tenancy\TenantStatus;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class TenantAdminController extends Controller
{
    public function index(Request $request): View
    {
        $arama = trim((string) $request->query('q'));
        $durum = $request->query('durum');

        $tenants = Tenant::query()
            ->withCount('users')
            ->when($arama !== '', fn ($q) => $q->where(function ($q) use ($arama) {
                $q->where('name', 'like', "%{$arama}%")->orWhere('slug', 'like', "%{$arama}%");
            }))
            ->when(
                $durum !== null && TenantStatus::tryFrom($durum) !== null,
                fn ($q) => $q->where('status', $durum)
            )
            ->orderByDesc('created_at')
            ->paginate(20)
            ->withQueryString();

        return view('yonetim.index', [
            'tenants' => $tenants,
            'arama' => $arama,
            'durum' => $durum,
            'sayilar' => Tenant::query()
                ->selectRaw('status, count(*) as adet')
                ->groupBy('status')
                ->pluck('adet', 'status'),
        ]);
    }

    public function show(Tenant $tenant): View
    {
        return view('yonetim.detay', [
            'tenant' => $tenant->loadCount('users'),
            'payments' => Payment::forTenant($tenant)
                ->with('recordedBy')
                ->orderByDesc('period_ends_at')
                ->limit(20)
                ->get(),
        ]);
    }

    public function recordPayment(RecordPaymentRequest $request, Tenant $tenant, PaymentRecorder $recorder): RedirectResponse
    {
        $recorder->record(
            tenant: $tenant,
            amountMinor: $request->amountMinor(),
            months: (int) $request->validated('ay'),
            recordedBy: $request->user(),
            note: $request->validated('not'),
        );

        return back()->with('durum', 'Ödeme kaydedildi.');
    }

    public function suspend(Tenant $tenant): RedirectResponse
    {
        $tenant->suspend();

        return back()->with('durum', 'Kiracı askıya alındı.');
    }

    public function reactivate(Tenant $tenant): RedirectResponse
    {
        $tenant->reactivate();

        return back()->with('durum', 'Kiracı yeniden etkinleştirildi: '.$tenant->status->label().'.');
    }

    public function extendTrial(Request $request, Tenant $tenant): RedirectResponse
    {
        $veri = $request->validate([
            'gun' => ['required', 'integer', 'min:1', 'max:365'],
        ], [], ['gun' => 'gün sayısı']);

        $tenant->extendTrial((int) $veri['gun']);

        return back()->with('durum', "Deneme süresi {$veri['gun']} gün uzatıldı.");
    }
}
