<?php

declare(strict_types=1);

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Http\Requests\SaleRequest;
use App\Inventory\InventoryProvisioner;
use App\Inventory\StockException;
use App\Models\Customer;
use App\Models\Product;
use App\Models\Sale;
use App\Models\Warehouse;
use App\Modules\Module;
use App\Modules\PaymentMethod;
use App\Modules\SaleStatus;
use App\Sales\SaleException;
use App\Sales\SaleRecorder;
use App\Tenancy\TenantContext;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class SaleController extends Controller
{
    public function __construct(
        private readonly SaleRecorder $recorder,
        private readonly TenantContext $context,
        private readonly InventoryProvisioner $provisioner,
    ) {}

    public function index(Request $request): View
    {
        $arama = trim((string) $request->query('q'));
        $durum = (string) $request->query('odeme');

        $sorgu = Sale::query()
            ->with(['customer', 'createdBy'])
            ->when($arama !== '', fn (Builder $q) => $q->where(function (Builder $q) use ($arama) {
                $q->where('number', 'like', "%{$arama}%")
                    ->orWhereHas('customer', fn (Builder $c) => $c->search($arama));
            }))
            ->when($durum === 'bekliyor', fn (Builder $q) => $q->unpaid())
            ->when($durum === 'odendi', fn (Builder $q) => $q->confirmed()->whereColumn('paid_minor', '>=', 'total_minor'))
            ->when($durum === 'iptal', fn (Builder $q) => $q->where('status', SaleStatus::Cancelled))
            ->when($durum === '', fn (Builder $q) => $q->where('status', '!=', SaleStatus::Cancelled));

        return view('satis.index', [
            'sales' => (clone $sorgu)->orderByDesc('sold_at')->orderByDesc('id')->paginate(20)->withQueryString(),
            'arama' => $arama,
            'durum' => $durum,
            'bekleyenTutar' => Sale::query()->unpaid()->sum(DB::raw('total_minor - paid_minor')),
            'buAyCiro' => Sale::query()->confirmed()->where('sold_at', '>=', now()->startOfMonth())->sum('total_minor'),
        ]);
    }

    public function create(): View
    {
        $tenant = $this->context->getOrFail();

        if ($tenant->hasModule(Module::Inventory)) {
            $this->provisioner->ensure($tenant);
        }

        return view('satis.form', [
            'products' => Product::query()->where('is_active', true)->orderBy('name')->get(),
            'customers' => Customer::query()->where('is_active', true)->orderBy('name')->get(),
            'warehouses' => Warehouse::query()->orderByDesc('is_default')->orderBy('name')->get(),
        ]);
    }

    public function store(SaleRequest $request): RedirectResponse
    {
        $tenant = $this->context->getOrFail();

        $satirlar = [];

        foreach ($request->validated('satirlar') as $satir) {
            $urun = Product::query()->findOrFail($satir['product_id']);

            $satirlar[] = [
                'product_id' => $urun->getKey(),
                'name' => $urun->name,
                'quantity' => (float) $satir['quantity'],
                'unit_price_minor' => (int) round(((float) $satir['unit_price']) * 100),
                'vat_rate' => $urun->vat_rate,
            ];
        }

        $depoId = $request->validated('warehouse_id');

        try {
            $sale = $this->recorder->record(
                tenant: $tenant,
                satirlar: $satirlar,
                customerId: $request->validated('customer_id'),
                warehouse: $depoId !== null ? Warehouse::query()->find($depoId) : null,
                discountMinor: (int) round(((float) $request->validated('indirim')) * 100),
                soldAt: Carbon::parse($request->validated('sold_at')),
                dueOn: $request->validated('due_on') !== null
                    ? Carbon::parse($request->validated('due_on'))
                    : null,
                note: $request->validated('note'),
                user: $request->user(),
                initialPaymentMinor: (int) round(((float) $request->validated('tahsilat')) * 100),
                method: PaymentMethod::from($request->validated('method')),
            );
        } catch (StockException|SaleException $e) {
            throw ValidationException::withMessages(['satirlar' => $e->getMessage()]);
        }

        return redirect()
            ->route('satis.show', $sale)
            ->with('durum', $sale->number.' kaydedildi.');
    }

    public function show(Sale $sale): View
    {
        return view('satis.detay', [
            'sale' => $sale->load(['lines.product', 'payments.createdBy', 'customer', 'warehouse', 'createdBy']),
        ]);
    }

    public function addPayment(Request $request, Sale $sale): RedirectResponse
    {
        $veri = $request->validate([
            'tutar' => ['required', 'string'],
            'method' => ['required', Rule::enum(PaymentMethod::class)],
            'note' => ['nullable', 'string', 'max:255'],
        ], [], ['tutar' => 'tutar', 'method' => 'ödeme yöntemi']);

        $ham = str_replace(' ', '', $veri['tutar']);
        $ham = str_contains($ham, ',') ? str_replace(['.', ','], ['', '.'], $ham) : $ham;

        if (! is_numeric($ham)) {
            throw ValidationException::withMessages(['tutar' => 'Geçerli bir tutar girin.']);
        }

        try {
            $this->recorder->addPayment(
                sale: $sale,
                amountMinor: (int) round(((float) $ham) * 100),
                method: PaymentMethod::from($veri['method']),
                user: $request->user(),
                note: $veri['note'] ?? null,
            );
        } catch (SaleException $e) {
            throw ValidationException::withMessages(['tutar' => $e->getMessage()]);
        }

        return back()->with('durum', 'Tahsilat kaydedildi.');
    }

    public function cancel(Request $request, Sale $sale): RedirectResponse
    {
        try {
            $this->recorder->cancel($sale, $request->user());
        } catch (SaleException $e) {
            throw ValidationException::withMessages(['iptal' => $e->getMessage()]);
        }

        return back()->with('durum', $sale->number.' iptal edildi, stok geri alındı.');
    }
}
