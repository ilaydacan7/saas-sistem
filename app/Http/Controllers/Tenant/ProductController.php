<?php

declare(strict_types=1);

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Http\Requests\ProductRequest;
use App\Models\Product;
use App\Models\Unit;
use App\Modules\ProductType;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    public function index(Request $request): View
    {
        $arama = trim((string) $request->query('q'));
        $tur = ProductType::tryFrom((string) $request->query('tur'));
        $stokDurumu = (string) $request->query('stok');

        $products = Product::query()
            ->withStock()
            ->with('unit')
            ->search($arama)
            ->when($tur !== null, fn (Builder $q) => $q->where('type', $tur))
            ->when($request->query('durum') === 'pasif',
                fn (Builder $q) => $q->where('is_active', false),
                fn (Builder $q) => $q->where('is_active', true))
            ->when($stokDurumu === 'kritik', fn (Builder $q) => $q
                ->where('tracks_stock', true)
                ->whereRaw('(select coalesce(sum(quantity), 0) from stock_levels where stock_levels.product_id = products.id) <= products.min_stock'))
            ->when($stokDurumu === 'tukendi', fn (Builder $q) => $q
                ->where('tracks_stock', true)
                ->whereRaw('(select coalesce(sum(quantity), 0) from stock_levels where stock_levels.product_id = products.id) <= 0'))
            ->orderBy('name')
            ->paginate(20)
            ->withQueryString();

        return view('stok.index', [
            'products' => $products,
            'arama' => $arama,
            'tur' => $tur,
            'durum' => $request->query('durum'),
            'stokDurumu' => $stokDurumu,
            'kritikSayisi' => $this->kritikSayisi(),
        ]);
    }

    public function create(): View
    {
        return view('stok.form', [
            'product' => new Product,
            'units' => Unit::query()->orderBy('name')->get(),
        ]);
    }

    public function store(ProductRequest $request): RedirectResponse
    {
        $product = Product::create($request->productData());

        return redirect()
            ->route('stok.index')
            ->with('durum', $product->name.' kaydedildi.');
    }

    public function edit(Product $product): View
    {
        return view('stok.form', [
            'product' => $product->loadSum('stockLevels as stock_quantity', 'quantity'),
            'units' => Unit::query()->orderBy('name')->get(),
        ]);
    }

    public function update(ProductRequest $request, Product $product): RedirectResponse
    {
        $product->update($request->productData());

        return redirect()
            ->route('stok.index')
            ->with('durum', $product->name.' güncellendi.');
    }

    public function destroy(Product $product): RedirectResponse
    {
        $isim = $product->name;
        $product->delete();

        return redirect()
            ->route('stok.index')
            ->with('durum', $isim.' silindi.');
    }

    private function kritikSayisi(): int
    {
        return Product::query()
            ->where('is_active', true)
            ->where('tracks_stock', true)
            ->whereRaw('(select coalesce(sum(quantity), 0) from stock_levels where stock_levels.product_id = products.id) <= products.min_stock')
            ->count();
    }
}
