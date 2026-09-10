<?php

declare(strict_types=1);

namespace Tests\Feature\Moduller;

use App\Inventory\InventoryProvisioner;
use App\Inventory\StockException;
use App\Inventory\StockManager;
use App\Models\Customer;
use App\Models\Product;
use App\Models\Tenant;
use App\Models\Transaction;
use App\Models\User;
use App\Models\Warehouse;
use App\Modules\Module;
use App\Modules\TransactionType;
use App\Reporting\DashboardReport;
use App\Sales\SaleRecorder;
use App\Tenancy\TenantContext;
use App\Tenancy\TenantStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class PanelRaporuTest extends TestCase
{
    use RefreshDatabase;

    private const HOST = 'http://acme.saas.local';

    private Tenant $tenant;

    private User $sahip;

    private Warehouse $depo;

    private Product $urun;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tenant = Tenant::factory()->create(['slug' => 'acme', 'status' => TenantStatus::Active]);

        foreach ([Module::Customers, Module::Inventory, Module::Sales, Module::Finance] as $modul) {
            $this->tenant->enableModule($modul);
        }

        $this->sahip = User::factory()->forTenant($this->tenant)->owner()->create();
        $this->depo = app(InventoryProvisioner::class)->defaultWarehouse($this->tenant);

        app(TenantContext::class)->set($this->tenant);

        $this->urun = Product::factory()->create([
            'tenant_id' => $this->tenant->getKey(),
            'name' => 'Çelik Vida',
            'sale_price_minor' => 10000,
            'vat_rate' => 0,
            'min_stock' => 5,
        ]);

        app(StockManager::class)->receive($this->urun, $this->depo, 1000, $this->sahip);
    }

    private function satis(float $miktar = 1, int $pesin = 0, ?Carbon $tarih = null, ?int $musteriId = null)
    {
        return app(SaleRecorder::class)->record(
            tenant: $this->tenant,
            satirlar: [[
                'product_id' => $this->urun->getKey(),
                'name' => $this->urun->name,
                'quantity' => $miktar,
                'unit_price_minor' => 10000,
                'vat_rate' => 0,
            ]],
            customerId: $musteriId,
            warehouse: $this->depo,
            soldAt: $tarih,
            initialPaymentMinor: $pesin,
        );
    }

    private function rapor(): DashboardReport
    {
        return new DashboardReport($this->tenant);
    }

    public function test_donem_yalnizca_bilinen_degerleri_kabul_eder(): void
    {
        $this->assertSame('gun', DashboardReport::donem(null)['anahtar']);
        $this->assertSame('hafta', DashboardReport::donem('hafta')['anahtar']);
        $this->assertSame('yil', DashboardReport::donem('yil')['anahtar']);

        // Beklenmeyen girdi sessizce varsayılana düşer, sorguya karışmaz.
        $this->assertSame('gun', DashboardReport::donem("'; drop table sales; --")['anahtar']);
    }

    public function test_bugunku_satis_karti_gercek_ciroyu_gosterir(): void
    {
        $this->satis(2);
        $this->satis(3);
        $this->satis(1, tarih: now()->subDays(3));

        $kart = collect($this->rapor()->kartlar())->firstWhere('etiket', 'Bugünkü satış');

        $this->assertSame(50000, $kart['kurus']);
        $this->assertSame('2 işlem', $kart['ipucu']);
    }

    public function test_iptal_edilen_satis_ciroya_girmez(): void
    {
        $satis = $this->satis(5);
        app(SaleRecorder::class)->cancel($satis);

        $kart = collect($this->rapor()->kartlar())->firstWhere('etiket', 'Bugünkü satış');

        $this->assertSame(0, $kart['kurus']);
    }

    public function test_net_kar_tahsilat_eksi_gider(): void
    {
        $this->satis(10, pesin: 100000);

        Transaction::factory()->create([
            'tenant_id' => $this->tenant->getKey(),
            'type' => TransactionType::Expense,
            'amount_minor' => 40000,
            'occurred_on' => now()->toDateString(),
        ]);

        $kartlar = collect($this->rapor()->kartlar());

        $this->assertSame(40000, $kartlar->firstWhere('etiket', 'Bu ay gider')['kurus']);
        $this->assertSame(60000, $kartlar->firstWhere('etiket', 'Bu ay net')['kurus']);
    }

    public function test_gider_ciroyu_asinca_net_negatif_olur(): void
    {
        Transaction::factory()->create([
            'tenant_id' => $this->tenant->getKey(),
            'type' => TransactionType::Expense,
            'amount_minor' => 75000,
            'occurred_on' => now()->toDateString(),
        ]);

        $kart = collect($this->rapor()->kartlar())->firstWhere('etiket', 'Bu ay net');

        $this->assertSame(-75000, $kart['kurus']);
        $this->assertSame('tehlike', $kart['ton']);
    }

    public function test_gunluk_seri_14_kova_uretir(): void
    {
        $this->satis(2, tarih: now());
        $this->satis(1, tarih: now()->subDays(2));

        $seri = $this->rapor()->satisSerisi('gun');

        $this->assertCount(14, $seri);
        $this->assertSame(20000, $seri[13]['kurus']);
        $this->assertSame(10000, $seri[11]['kurus']);
        $this->assertSame(0, $seri[0]['kurus']);
    }

    public function test_yillik_seri_12_ay_uretir(): void
    {
        $this->satis(4, tarih: now());

        $seri = $this->rapor()->satisSerisi('yil');

        $this->assertCount(12, $seri);
        $this->assertSame(40000, $seri[now()->month - 1]['kurus']);
    }

    public function test_haftalik_seri_12_hafta_uretir(): void
    {
        $this->satis(3, tarih: now());

        $seri = $this->rapor()->satisSerisi('hafta');

        $this->assertCount(12, $seri);
        $this->assertSame(30000, $seri[11]['kurus']);
    }

    public function test_donem_disindaki_satis_seriye_girmez(): void
    {
        $this->satis(9, tarih: now()->subDays(60));

        $seri = $this->rapor()->satisSerisi('gun');

        $this->assertSame(0, array_sum(array_column($seri, 'kurus')));
    }

    public function test_cok_satanlar_tutara_gore_siralanir(): void
    {
        $ikinci = Product::factory()->create([
            'tenant_id' => $this->tenant->getKey(),
            'name' => 'Ahşap Panel',
            'vat_rate' => 0,
        ]);
        app(StockManager::class)->receive($ikinci, $this->depo, 100);

        $this->satis(2);

        app(SaleRecorder::class)->record(
            tenant: $this->tenant,
            satirlar: [[
                'product_id' => $ikinci->getKey(),
                'name' => $ikinci->name,
                'quantity' => 1,
                'unit_price_minor' => 90000,
                'vat_rate' => 0,
            ]],
            warehouse: $this->depo,
        );

        $liste = $this->rapor()->cokSatanlar();

        $this->assertSame('Ahşap Panel', $liste[0]['ad']);
        $this->assertSame(90000, $liste[0]['kurus']);
        $this->assertSame('Çelik Vida', $liste[1]['ad']);
    }

    public function test_bekleyen_tahsilatlar_vadesi_gecenleri_one_alir(): void
    {
        $musteri = Customer::factory()->create(['tenant_id' => $this->tenant->getKey(), 'name' => 'Geciken Musteri']);

        $vadesiz = $this->satis(1);

        $gecmis = app(SaleRecorder::class)->record(
            tenant: $this->tenant,
            satirlar: [[
                'product_id' => $this->urun->getKey(),
                'name' => $this->urun->name,
                'quantity' => 1,
                'unit_price_minor' => 10000,
                'vat_rate' => 0,
            ]],
            customerId: $musteri->getKey(),
            warehouse: $this->depo,
            soldAt: now()->subDays(30),
            dueOn: now()->subDays(10),
        );

        $liste = $this->rapor()->bekleyenTahsilatlar();

        $this->assertSame($gecmis->getKey(), $liste[0]->getKey());
        $this->assertTrue($liste[0]->isOverdue());
        $this->assertSame(20000, $this->rapor()->bekleyenTahsilatToplami());
    }

    public function test_odenmis_satis_bekleyenlerde_gorunmez(): void
    {
        $this->satis(1, pesin: 10000);

        $this->assertCount(0, $this->rapor()->bekleyenTahsilatlar());
        $this->assertSame(0, $this->rapor()->bekleyenTahsilatToplami());
    }

    public function test_kritik_stok_listelenir(): void
    {
        $this->satis(996);

        $liste = $this->rapor()->kritikStok();

        $this->assertCount(1, $liste);
        $this->assertSame('Çelik Vida', $liste[0]->name);
    }

    public function test_kapali_modulun_karti_uretilmez(): void
    {
        $beta = Tenant::factory()->create(['slug' => 'beta']);
        $beta->enableModule(Module::Customers);

        $kartlar = collect((new DashboardReport($beta))->kartlar());

        $this->assertNull($kartlar->firstWhere('etiket', 'Bugünkü satış'));
        $this->assertNull($kartlar->firstWhere('etiket', 'Bu ay gider'));
        $this->assertNotNull($kartlar->firstWhere('etiket', 'Müşteri'));
    }

    public function test_baska_kiracinin_satisi_raporlara_karismaz(): void
    {
        $beta = Tenant::factory()->create(['slug' => 'beta']);
        $beta->enableModule(Module::Inventory);
        $beta->enableModule(Module::Sales);

        $betaDepo = app(InventoryProvisioner::class)->defaultWarehouse($beta);
        $betaUrun = Product::factory()->create(['tenant_id' => $beta->getKey(), 'name' => 'Beta Urunu', 'vat_rate' => 0]);
        app(StockManager::class)->receive($betaUrun, $betaDepo, 100);

        app(SaleRecorder::class)->record(
            tenant: $beta,
            satirlar: [[
                'product_id' => $betaUrun->getKey(),
                'name' => $betaUrun->name,
                'quantity' => 5,
                'unit_price_minor' => 100000,
                'vat_rate' => 0,
            ]],
            warehouse: $betaDepo,
        );

        $kart = collect($this->rapor()->kartlar())->firstWhere('etiket', 'Bugünkü satış');

        $this->assertSame(0, $kart['kurus']);
        $this->assertCount(0, $this->rapor()->cokSatanlar());
        $this->assertSame(0, $this->rapor()->bekleyenTahsilatToplami());
    }

    public function test_panel_ekrani_gercek_rakamlari_gosterir(): void
    {
        $this->satis(2, pesin: 20000);

        $this->actingAs($this->sahip)->get(self::HOST.'/panel')
            ->assertOk()
            ->assertSee('Bugünkü satış')
            ->assertSee('Satış grafiği')
            ->assertSee('Bu ayın çok satanları')
            ->assertSee('Çelik Vida')
            ->assertSee('₺200,00');
    }

    public function test_satis_yokken_grafik_bos_mesaji_verir(): void
    {
        $this->actingAs($this->sahip)->get(self::HOST.'/panel')
            ->assertOk()
            ->assertSee('Bu dönemde satış kaydı yok.');
    }

    public function test_grafik_donemi_baglantiyla_degisir(): void
    {
        $this->actingAs($this->sahip)->get(self::HOST.'/panel?donem=yil')
            ->assertOk()
            ->assertSee(now()->year.' ayları');
    }

    public function test_rapor_ortamdaki_baglamdan_etkilenmez(): void
    {
        $this->satis(7);

        $beta = Tenant::factory()->create(['slug' => 'beta']);
        $beta->enableModule(Module::Sales);

        // Bağlam acme iken beta için rapor kuruluyor: beta rakamlarını vermeli.
        $kart = collect((new DashboardReport($beta))->kartlar())->firstWhere('etiket', 'Bugünkü satış');

        $this->assertSame(0, $kart['kurus']);
        $this->assertSame($this->tenant->getKey(), app(TenantContext::class)->id());
    }

    public function test_baska_kiracinin_deposuna_urun_islenemez(): void
    {
        $beta = Tenant::factory()->create(['slug' => 'beta']);
        $beta->enableModule(Module::Inventory);
        $betaDepo = app(InventoryProvisioner::class)->defaultWarehouse($beta);

        $this->expectException(StockException::class);

        app(StockManager::class)->receive($this->urun, $betaDepo, 5);
    }
}
