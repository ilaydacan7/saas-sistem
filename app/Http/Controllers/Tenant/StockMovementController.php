<?php

declare(strict_types=1);

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Http\Requests\StockMovementRequest;
use App\Inventory\InventoryProvisioner;
use App\Inventory\StockException;
use App\Inventory\StockManager;
use App\Models\Product;
use App\Models\StockMovement;
use App\Models\Warehouse;
use App\Modules\StockMovementType;
use App\Tenancy\TenantContext;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class StockMovementController extends Controller
{
    public function __construct(
        private readonly StockManager $stok,
        private readonly TenantContext $context,
        private readonly InventoryProvisioner $provisioner,
    ) {}

    public function index(Request $request): View
    {
        $movements = StockMovement::query()
            ->with(['product.unit', 'warehouse', 'createdBy'])
            ->when($request->integer('urun') > 0, fn ($q) => $q->where('product_id', $request->integer('urun')))
            ->when(
                StockMovementType::tryFrom((string) $request->query('tip')) !== null,
                fn ($q) => $q->where('type', $request->query('tip'))
            )
            ->orderByDesc('occurred_at')
            ->orderByDesc('id')
            ->paginate(30)
            ->withQueryString();

        return view('stok.hareketler', [
            'movements' => $movements,
            'products' => Product::query()->where('tracks_stock', true)->orderBy('name')->get(),
            'seciliUrun' => $request->integer('urun'),
            'seciliTip' => (string) $request->query('tip'),
        ]);
    }

    public function create(Request $request): View
    {
        $this->provisioner->ensure($this->context->getOrFail());

        return view('stok.hareket-form', [
            'products' => Product::query()->where('tracks_stock', true)->where('is_active', true)->orderBy('name')->get(),
            'warehouses' => Warehouse::query()->orderByDesc('is_default')->orderBy('name')->get(),
            'seciliUrun' => $request->integer('urun'),
            'seciliTip' => StockMovementType::tryFrom((string) $request->query('tip')) ?? StockMovementType::In,
        ]);
    }

    public function store(StockMovementRequest $request): RedirectResponse
    {
        $product = Product::query()->findOrFail($request->validated('product_id'));
        $warehouse = Warehouse::query()->findOrFail($request->validated('warehouse_id'));
        $tip = $request->movementType();
        $miktar = $request->quantity();
        $kullanici = $request->user();
        $not = $request->validated('note');

        try {
            $hareket = match ($tip) {
                StockMovementType::In => $this->stok->receive($product, $warehouse, $miktar, $kullanici, $not),
                StockMovementType::Out => $this->stok->issue($product, $warehouse, $miktar, $kullanici, $not),
                StockMovementType::Adjustment => $this->stok->adjust($product, $warehouse, $miktar, $kullanici, $not),
            };
        } catch (StockException $e) {
            throw ValidationException::withMessages(['quantity' => $e->getMessage()]);
        }

        return redirect()
            ->route('stok.hareketler')
            ->with('durum', sprintf(
                '%s: %s %s — yeni stok %s %s.',
                $tip->label(),
                $product->name,
                $this->miktarMetni(abs((float) $hareket->quantity)),
                $this->miktarMetni((float) $hareket->balance_after),
                $product->unitLabel(),
            ));
    }

    private function miktarMetni(float $miktar): string
    {
        $metin = number_format($miktar, 3, ',', '.');

        return rtrim(rtrim($metin, '0'), ',');
    }
}
