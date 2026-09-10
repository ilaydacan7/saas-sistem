<?php

declare(strict_types=1);

namespace App\Reporting;

use App\Models\Customer;
use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleLine;
use App\Models\Tenant;
use App\Models\Transaction;
use App\Modules\Module;
use App\Modules\TransactionType;
use App\Tenancy\TenantContext;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * Panel için özet veriler.
 *
 * Bütün sorgular kiracı kapsamlı modeller üzerinden gider; ham SQL'e kullanıcı
 * girdisi karışmaz. Dönem seçimi sabit listeden çözülür.
 */
class DashboardReport
{
    public function __construct(
        private readonly Tenant $tenant,
        private readonly ?TenantContext $context = null,
    ) {}

    /**
     * Rapor daima kendi kiracısının verisini okur.
     *
     * Sorgular ortamdaki bağlama bırakılsaydı, rapor B kiracısı için
     * kurulup A kiracısının bağlamında çalıştığında B modülleriyle A
     * rakamları karışırdı.
     *
     * @template TReturn
     *
     * @param  \Closure():TReturn  $islem
     * @return TReturn
     */
    private function kapsamda(\Closure $islem): mixed
    {
        return ($this->context ?? app(TenantContext::class))->runFor($this->tenant, $islem);
    }

    /**
     * Grafik dönemi: yalnızca bilinen anahtarlar kabul edilir.
     *
     * @return array{anahtar: string, etiket: string}
     */
    public static function donem(?string $secim): array
    {
        return match ($secim) {
            'hafta' => ['anahtar' => 'hafta', 'etiket' => 'Son 12 hafta'],
            'yil' => ['anahtar' => 'yil', 'etiket' => now()->year.' ayları'],
            default => ['anahtar' => 'gun', 'etiket' => 'Son 14 gün'],
        };
    }

    /**
     * Üst sıradaki özet kartları. Kapalı modüllerin kartı hiç üretilmez.
     *
     * @return list<array{etiket: string, kurus: ?int, sayi: ?int, ipucu: ?string, rota: ?string, ton: string}>
     */
    public function kartlar(): array
    {
        return $this->kapsamda(function (): array {
            $kartlar = [];

            if ($this->tenant->hasModule(Module::Sales)) {
                $kartlar[] = [
                    'etiket' => 'Bugünkü satış',
                    'kurus' => $this->ciro(now()->startOfDay(), now()->endOfDay()),
                    'sayi' => null,
                    'ipucu' => $this->satisAdedi(now()->startOfDay(), now()->endOfDay()).' işlem',
                    'rota' => route('satis.index'),
                    'ton' => 'notr',
                ];

                $kartlar[] = [
                    'etiket' => 'Bu ay ciro',
                    'kurus' => $this->ciro(now()->startOfMonth(), now()->endOfMonth()),
                    'sayi' => null,
                    'ipucu' => now()->translatedFormat('F'),
                    'rota' => route('satis.index'),
                    'ton' => 'basari',
                ];
            }

            if ($this->tenant->hasModule(Module::Finance)) {
                $gider = $this->gider(now()->startOfMonth(), now()->endOfMonth());
                $gelir = $this->kasaGeliri(now()->startOfMonth(), now()->endOfMonth());

                $kartlar[] = [
                    'etiket' => 'Bu ay gider',
                    'kurus' => $gider,
                    'sayi' => null,
                    'ipucu' => now()->translatedFormat('F'),
                    'rota' => route('finans.index'),
                    'ton' => 'uyari',
                ];

                $kartlar[] = [
                    'etiket' => 'Bu ay net',
                    'kurus' => $gelir - $gider,
                    'sayi' => null,
                    'ipucu' => 'Tahsil edilen − gider',
                    'rota' => route('finans.index'),
                    'ton' => ($gelir - $gider) >= 0 ? 'basari' : 'tehlike',
                ];
            }

            if ($kartlar === [] && $this->tenant->hasModule(Module::Customers)) {
                $kartlar[] = [
                    'etiket' => 'Müşteri',
                    'kurus' => null,
                    'sayi' => Customer::query()->where('is_active', true)->count(),
                    'ipucu' => 'Aktif kayıt',
                    'rota' => route('musteriler.index'),
                    'ton' => 'notr',
                ];
            }

            return $kartlar;
        });
    }

    /**
     * Satış grafiği verisi.
     *
     * Gruplama veritabanı fonksiyonlarıyla değil PHP tarafında yapılır:
     * SQLite ve PostgreSQL'in tarih fonksiyonları farklı, tek kod iki yerde
     * de aynı sonucu vermeli.
     *
     * @return list<array{etiket: string, kurus: int}>
     */
    public function satisSerisi(string $donem): array
    {
        if (! $this->tenant->hasModule(Module::Sales)) {
            return [];
        }

        return $this->kapsamda(function () use ($donem): array {
            [$kovalar, $baslangic] = $this->kovalar($donem);

            $satislar = Sale::query()
                ->confirmed()
                ->where('sold_at', '>=', $baslangic)
                ->get(['sold_at', 'total_minor']);

            foreach ($satislar as $satis) {
                $anahtar = $this->kovaAnahtari($satis->sold_at, $donem);

                if (isset($kovalar[$anahtar])) {
                    $kovalar[$anahtar]['kurus'] += $satis->total_minor;
                }
            }

            return array_values($kovalar);
        });
    }

    /**
     * @return Collection<int, array{ad: string, adet: float, kurus: int}>
     */
    public function cokSatanlar(int $limit = 5): Collection
    {
        if (! $this->tenant->hasModule(Module::Sales)) {
            return collect();
        }

        return $this->kapsamda(function () use ($limit): Collection {
            $satisIdleri = Sale::query()
                ->confirmed()
                ->where('sold_at', '>=', now()->startOfMonth())
                ->pluck('id');

            if ($satisIdleri->isEmpty()) {
                return collect();
            }

            return SaleLine::query()
                ->whereIn('sale_id', $satisIdleri)
                ->get(['name', 'quantity', 'line_total_minor', 'unit_label'])
                ->groupBy('name')
                ->map(fn (Collection $grup, string $ad) => [
                    'ad' => $ad,
                    'adet' => (float) $grup->sum(fn ($satir) => (float) $satir->quantity),
                    'birim' => $grup->first()->unit_label,
                    'kurus' => (int) $grup->sum('line_total_minor'),
                ])
                ->sortByDesc('kurus')
                ->take($limit)
                ->values();
        });
    }

    /**
     * Vadesi geçmiş olanlar önce gelir; işletmenin peşine düşeceği liste budur.
     *
     * @return Collection<int, Sale>
     */
    public function bekleyenTahsilatlar(int $limit = 5): Collection
    {
        if (! $this->tenant->hasModule(Module::Sales)) {
            return collect();
        }

        return $this->kapsamda(fn (): Collection => Sale::query()
            ->unpaid()
            ->with('customer')
            ->orderByRaw('case when due_on is null then 1 else 0 end')
            ->orderBy('due_on')
            ->limit($limit)
            ->get());
    }

    public function bekleyenTahsilatToplami(): int
    {
        if (! $this->tenant->hasModule(Module::Sales)) {
            return 0;
        }

        return $this->kapsamda(fn (): int => (int) Sale::query()->unpaid()->get(['total_minor', 'paid_minor'])
            ->sum(fn (Sale $satis) => $satis->remainingMinor()));
    }

    /**
     * @return Collection<int, Product>
     */
    public function kritikStok(int $limit = 5): Collection
    {
        if (! $this->tenant->hasModule(Module::Inventory)) {
            return collect();
        }

        return $this->kapsamda(fn (): Collection => Product::query()
            ->where('is_active', true)
            ->where('tracks_stock', true)
            ->whereRaw('(select coalesce(sum(quantity), 0) from stock_levels where stock_levels.product_id = products.id) <= products.min_stock')
            ->withStock()
            ->with('unit')
            ->orderBy('name')
            ->limit($limit)
            ->get());
    }

    private function ciro(Carbon $baslangic, Carbon $bitis): int
    {
        return (int) Sale::query()->confirmed()->between($baslangic, $bitis)->sum('total_minor');
    }

    private function satisAdedi(Carbon $baslangic, Carbon $bitis): int
    {
        return Sale::query()->confirmed()->between($baslangic, $bitis)->count();
    }

    private function gider(Carbon $baslangic, Carbon $bitis): int
    {
        return (int) Transaction::query()
            ->where('type', TransactionType::Expense)
            ->between($baslangic, $bitis)
            ->sum('amount_minor');
    }

    private function kasaGeliri(Carbon $baslangic, Carbon $bitis): int
    {
        return (int) Transaction::query()
            ->where('type', TransactionType::Income)
            ->between($baslangic, $bitis)
            ->sum('amount_minor');
    }

    /**
     * Boş kovalar önceden üretilir; satış olmayan gün grafikte sıfır olarak durur.
     *
     * @return array{0: array<string, array{etiket: string, kurus: int}>, 1: Carbon}
     */
    private function kovalar(string $donem): array
    {
        $kovalar = [];

        if ($donem === 'yil') {
            $baslangic = now()->startOfYear();

            for ($ay = 1; $ay <= 12; $ay++) {
                $tarih = now()->startOfYear()->addMonths($ay - 1);
                $kovalar[$tarih->format('Y-m')] = [
                    'etiket' => $tarih->translatedFormat('M'),
                    'kurus' => 0,
                ];
            }

            return [$kovalar, $baslangic];
        }

        if ($donem === 'hafta') {
            $baslangic = now()->startOfWeek()->subWeeks(11);

            for ($i = 0; $i < 12; $i++) {
                $tarih = $baslangic->copy()->addWeeks($i);
                $kovalar[$tarih->format('o-W')] = [
                    'etiket' => $tarih->format('d.m'),
                    'kurus' => 0,
                ];
            }

            return [$kovalar, $baslangic];
        }

        $baslangic = now()->startOfDay()->subDays(13);

        for ($i = 0; $i < 14; $i++) {
            $tarih = $baslangic->copy()->addDays($i);
            $kovalar[$tarih->format('Y-m-d')] = [
                'etiket' => $tarih->format('d.m'),
                'kurus' => 0,
            ];
        }

        return [$kovalar, $baslangic];
    }

    private function kovaAnahtari(Carbon $tarih, string $donem): string
    {
        return match ($donem) {
            'yil' => $tarih->format('Y-m'),
            'hafta' => $tarih->format('o-W'),
            default => $tarih->format('Y-m-d'),
        };
    }
}
