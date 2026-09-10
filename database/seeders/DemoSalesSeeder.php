<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Finance\CashBook;
use App\Inventory\InventoryProvisioner;
use App\Inventory\StockManager;
use App\Models\Customer;
use App\Models\Product;
use App\Models\Sale;
use App\Models\Tenant;
use App\Models\TransactionCategory;
use App\Models\User;
use App\Modules\CustomerType;
use App\Modules\Module;
use App\Modules\PaymentMethod;
use App\Modules\TransactionType;
use App\Sales\SaleRecorder;
use App\Tenancy\TenantContext;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;

/**
 * Yerel demo verisi: panelin ve raporların gerçek rakamlarla çalıştığını
 * görebilmek için zamana yayılmış satış, tahsilat ve gider üretir.
 * Yalnızca geliştirme ortamı içindir.
 */
class DemoSalesSeeder extends Seeder
{
    public function run(): void
    {
        $tenant = Tenant::query()->where('slug', 'yildiz')->first();

        if ($tenant === null) {
            return;
        }

        $tenant->enableModule(Module::Sales);
        $tenant->enableModule(Module::Finance);

        $context = app(TenantContext::class);
        $recorder = app(SaleRecorder::class);
        $stok = app(StockManager::class);
        $kasa = app(CashBook::class);
        $depo = app(InventoryProvisioner::class)->defaultWarehouse($tenant);
        $sahip = User::query()->where('tenant_id', $tenant->getKey())->first();

        $context->runFor($tenant, function () use ($tenant, $recorder, $stok, $kasa, $depo, $sahip): void {
            if (Sale::query()->count() > 3) {
                return;
            }

            $musteriler = $this->musteriler($tenant);
            $urunler = Product::query()->where('tracks_stock', true)->get();

            if ($urunler->isEmpty()) {
                return;
            }

            // Satışların stoğu tüketmemesi için önce yeterli giriş yapılır.
            foreach ($urunler as $urun) {
                $stok->receive($urun, $depo, 500, $sahip, 'Demo stok girişi');
            }

            // Son 40 güne yayılmış, tekrar edilebilir bir satış deseni.
            for ($gunOnce = 40; $gunOnce >= 0; $gunOnce--) {
                $tarih = now()->subDays($gunOnce)->setTime(10 + ($gunOnce % 8), 15);

                // Pazar günleri kapalı; hafta içi daha yoğun.
                if ($tarih->isSunday()) {
                    continue;
                }

                $gunlukSatis = $tarih->isSaturday() ? 1 : 1 + ($gunOnce % 3);

                for ($i = 0; $i < $gunlukSatis; $i++) {
                    $urun = $urunler[($gunOnce + $i) % $urunler->count()];
                    $miktar = 1 + (($gunOnce + $i) % 5);
                    $musteri = $musteriler[($gunOnce + $i) % $musteriler->count()];

                    // Bazı satışlar peşin, bazıları vadeli kalsın.
                    $vadeli = ($gunOnce + $i) % 4 === 0;
                    $toplam = (int) round($miktar * $urun->sale_price_minor * (1 + $urun->vat_rate / 100));

                    $recorder->record(
                        tenant: $tenant,
                        satirlar: [[
                            'product_id' => $urun->getKey(),
                            'name' => $urun->name,
                            'quantity' => $miktar,
                            'unit_price_minor' => $urun->sale_price_minor,
                            'vat_rate' => $urun->vat_rate,
                        ]],
                        customerId: $i === 0 ? $musteri->getKey() : null,
                        warehouse: $depo,
                        soldAt: $tarih,
                        dueOn: $vadeli ? $tarih->copy()->addDays(15) : null,
                        user: $sahip,
                        initialPaymentMinor: $vadeli ? 0 : $toplam,
                        method: $i % 2 === 0 ? PaymentMethod::Cash : PaymentMethod::Card,
                    );
                }
            }

            $this->giderler($tenant, $kasa, $sahip);
        });
    }

    /**
     * @return Collection<int, Customer>
     */
    private function musteriler(Tenant $tenant)
    {
        $kayitlar = [
            ['Deniz Kırtasiye', 'siparis@denizkirtasiye.com', '02125550317', CustomerType::Company],
            ['Fırat Gıda Dağıtım', 'fatura@firatgida.com', '05334410922', CustomerType::Company],
            ['Ayşe Kaya', 'ayse.kaya@ornek.com', '05321112233', CustomerType::Individual],
            ['Mert Demir', null, '05439998877', CustomerType::Individual],
        ];

        foreach ($kayitlar as [$ad, $eposta, $telefon, $tur]) {
            Customer::query()->firstOrCreate(
                ['tenant_id' => $tenant->getKey(), 'name' => $ad],
                [
                    'type' => $tur,
                    'company_name' => $tur === CustomerType::Company ? $ad : null,
                    'email' => $eposta,
                    'phone' => $telefon,
                    'city' => 'İstanbul',
                    'is_active' => true,
                ]
            );
        }

        return Customer::query()->orderBy('id')->get();
    }

    private function giderler(Tenant $tenant, CashBook $kasa, ?User $sahip): void
    {
        $kalemler = [
            ['Kira', 1250000, 'Dükkân kirası'],
            ['Elektrik', 184050, 'Elektrik faturası'],
            ['İnternet', 59900, 'İnternet aboneliği'],
            ['Personel', 2800000, 'Personel ödemesi'],
            ['Mal alımı', 940000, 'Tedarikçi ödemesi'],
        ];

        foreach ([1, 0] as $ayOnce) {
            $ay = now()->subMonthsNoOverflow($ayOnce);

            foreach ($kalemler as [$kategoriAdi, $tutar, $aciklama]) {
                $kategori = TransactionCategory::query()
                    ->where('type', TransactionType::Expense)
                    ->where('name', $kategoriAdi)
                    ->value('id');

                $kasa->record(
                    tenant: $tenant,
                    type: TransactionType::Expense,
                    amountMinor: $tutar,
                    occurredOn: $ay->copy()->startOfMonth()->addDays(4),
                    categoryId: $kategori,
                    description: $ay->translatedFormat('F').' '.$aciklama,
                    method: PaymentMethod::Transfer,
                    user: $sahip,
                );
            }
        }
    }
}
