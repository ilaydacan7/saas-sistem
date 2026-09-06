<?php

declare(strict_types=1);

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Http\Requests\CustomerRequest;
use App\Models\Customer;
use App\Modules\CustomerType;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class CustomerController extends Controller
{
    public function index(Request $request): View
    {
        $arama = trim((string) $request->query('q'));
        $tur = CustomerType::tryFrom((string) $request->query('tur'));

        $customers = Customer::query()
            ->search($arama)
            ->when($tur !== null, fn ($q) => $q->where('type', $tur))
            ->when($request->query('durum') === 'pasif', fn ($q) => $q->where('is_active', false))
            ->when($request->query('durum') !== 'pasif', fn ($q) => $q->where('is_active', true))
            ->orderBy('name')
            ->paginate(20)
            ->withQueryString();

        return view('musteriler.index', [
            'customers' => $customers,
            'arama' => $arama,
            'tur' => $tur,
            'durum' => $request->query('durum'),
            'toplam' => Customer::query()->count(),
        ]);
    }

    public function create(): View
    {
        return view('musteriler.form', [
            'customer' => new Customer,
        ]);
    }

    public function store(CustomerRequest $request): RedirectResponse
    {
        $customer = new Customer($request->customerData());
        $customer->created_by_id = $request->user()->getKey();
        $customer->save();

        return redirect()
            ->route('musteriler.index')
            ->with('durum', $customer->displayName().' kaydedildi.');
    }

    public function edit(Customer $customer): View
    {
        return view('musteriler.form', [
            'customer' => $customer,
        ]);
    }

    public function update(CustomerRequest $request, Customer $customer): RedirectResponse
    {
        $customer->update($request->customerData());

        return redirect()
            ->route('musteriler.index')
            ->with('durum', $customer->displayName().' güncellendi.');
    }

    public function destroy(Customer $customer): RedirectResponse
    {
        $isim = $customer->displayName();
        $customer->delete();

        return redirect()
            ->route('musteriler.index')
            ->with('durum', $isim.' silindi.');
    }
}
