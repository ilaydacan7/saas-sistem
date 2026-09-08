<?php

declare(strict_types=1);

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\Product;
use App\Models\StockMovement;
use App\Models\User;
use App\Modules\Module;
use App\Tenancy\TenantContext;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class DashboardController extends Controller
{
    public function __invoke(Request $request, TenantContext $context): View
    {
        $tenant = $context->getOrFail();
        $donem = $this->donem((string) $request->query('donem'));

        return view('panel', [
            'tenant' => $tenant,
            'moduller' => $tenant->enabledModules(),
            'donem' => $donem['anahtar'],
            'donemBaslangic' => $donem['baslangic'],
            'kartlar' => $this->kartlar($tenant, $donem['baslangic']),
            'aktiviteler' => $this->aktiviteler($tenant),
            'kritikUrunler' => $this->kritikUrunler($tenant),
            'kalanGun' => $tenant->trial_ends_at?->isFuture()
                ? (int) now()->startOfDay()->diffInDays($tenant->trial_ends_at->startOfDay())
                : null,
        ]);
    }

    /**
     * @return array{anahtar: string, baslangic: Carbon}
     */
    private function donem(string $secim): array
    {
        return match ($secim) {
            'hafta' => ['anahtar' => 'hafta', 'baslangic' => now()->startOfWeek()],
            'ay' => ['anahtar' => 'ay', 'baslangic' => now()->startOfMonth()],
            default => ['anahtar' => 'bugun', 'baslangic' => now()->startOfDay()],
        };
    }

    /**
     * @return list<array{etiket: string, deger: string, ikon: string, ton: string, rota: ?string}>
     */
    private function kartlar($tenant, Carbon $baslangic): array
    {
        $kartlar = [];

        if ($tenant->hasModule(Module::Inventory)) {
            $kartlar[] = [
                'etiket' => 'Toplam Ürün',
                'deger' => (string) Product::query()->where('is_active', true)->count(),
                'ikon' => 'kutu',
                'ton' => 'notr',
                'rota' => route('stok.index'),
            ];

            $kritik = $this->kritikSorgu()->count();

            $kartlar[] = [
                'etiket' => 'Kritik Stok',
                'deger' => (string) $kritik,
                'ikon' => 'uyari',
                'ton' => $kritik > 0 ? 'uyari' : 'notr',
                'rota' => route('stok.index', ['stok' => 'kritik']),
            ];
        }

        if ($tenant->hasModule(Module::Customers)) {
            $kartlar[] = [
                'etiket' => 'Yeni Müşteri',
                'deger' => (string) Customer::query()->where('created_at', '>=', $baslangic)->count(),
                'ikon' => 'kisiler',
                'ton' => 'basari',
                'rota' => route('musteriler.index'),
            ];
        }

        if ($tenant->hasModule(Module::Inventory)) {
            $kartlar[] = [
                'etiket' => 'Stok Hareketi',
                'deger' => (string) StockMovement::query()->where('occurred_at', '>=', $baslangic)->count(),
                'ikon' => 'grafik',
                'ton' => 'notr',
                'rota' => route('stok.hareketler'),
            ];
        }

        $kartlar[] = [
            'etiket' => 'Ekip',
            'deger' => (string) User::query()->where('tenant_id', $tenant->getKey())->count(),
            'ikon' => 'ekip',
            'ton' => 'notr',
            'rota' => null,
        ];

        return array_slice($kartlar, 0, 4);
    }

    /**
     * Modüllerden gelen son hareketleri tek akışta birleştirir.
     *
     * @return Collection<int, array{metin: string, zaman: Carbon, ton: string}>
     */
    private function aktiviteler($tenant): Collection
    {
        $akis = collect();

        if ($tenant->hasModule(Module::Inventory)) {
            StockMovement::query()
                ->with('product.unit')
                ->orderByDesc('occurred_at')
                ->limit(6)
                ->get()
                ->each(function (StockMovement $hareket) use ($akis): void {
                    $miktar = rtrim(rtrim(number_format(abs((float) $hareket->quantity), 3, ',', '.'), '0'), ',');

                    $akis->push([
                        'metin' => sprintf(
                            '%s stok %s: %s%s %s',
                            $hareket->product->name,
                            mb_strtolower($hareket->type->label()),
                            (float) $hareket->quantity >= 0 ? '+' : '−',
                            $miktar,
                            $hareket->product->unitLabel(),
                        ),
                        'zaman' => $hareket->occurred_at,
                        'ton' => (float) $hareket->quantity >= 0 ? 'basari' : 'notr',
                    ]);
                });
        }

        if ($tenant->hasModule(Module::Customers)) {
            Customer::query()
                ->orderByDesc('created_at')
                ->limit(4)
                ->get()
                ->each(fn (Customer $musteri) => $akis->push([
                    'metin' => $musteri->displayName().' müşteri olarak eklendi',
                    'zaman' => $musteri->created_at,
                    'ton' => 'basari',
                ]));
        }

        User::query()
            ->where('tenant_id', $tenant->getKey())
            ->orderByDesc('created_at')
            ->limit(3)
            ->get()
            ->each(fn (User $kullanici) => $akis->push([
                'metin' => $kullanici->name.' ekibe katıldı',
                'zaman' => $kullanici->created_at,
                'ton' => 'bilgi',
            ]));

        return $akis->sortByDesc('zaman')->take(6)->values();
    }

    /**
     * @return Collection<int, Product>
     */
    private function kritikUrunler($tenant): Collection
    {
        if (! $tenant->hasModule(Module::Inventory)) {
            return collect();
        }

        return $this->kritikSorgu()->withStock()->with('unit')->orderBy('name')->limit(5)->get();
    }

    private function kritikSorgu()
    {
        return Product::query()
            ->where('is_active', true)
            ->where('tracks_stock', true)
            ->whereRaw('(select coalesce(sum(quantity), 0) from stock_levels where stock_levels.product_id = products.id) <= products.min_stock');
    }
}
