<?php

declare(strict_types=1);

namespace Tests\Feature\Moduller;

use App\Inventory\InventoryProvisioner;
use App\Inventory\StockException;
use App\Inventory\StockManager;
use App\Models\Customer;
use App\Models\Product;
use App\Models\Sale;
use App\Models\Tenant;
use App\Models\User;
use App\Models\Warehouse;
use App\Modules\Module;
use App\Modules\PaymentMethod;
use App\Modules\PaymentStatus;
use App\Modules\SaleStatus;
use App\Sales\SaleException;
use App\Sales\SaleRecorder;
use App\Tenancy\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SatisModuluTest extends TestCase
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

        $this->tenant = Tenant::factory()->create(['slug' => 'acme']);
        $this->tenant->enableModule(Module::Customers);
        $this->tenant->enableModule(Module::Inventory);
        $this->tenant->enableModule(Module::Sales);

        $this->sahip = User::factory()->forTenant($this->tenant)->owner()->create();
        $this->depo = app(InventoryProvisioner::class)->defaultWarehouse($this->tenant);

        app(TenantContext::class)->set($this->tenant);

        $this->urun = Product::factory()->create([
            'tenant_id' => $this->tenant->getKey(),
            'name' => 'Çelik Vida',
            'sale_price_minor' => 10000,
            'vat_rate' => 20,
            'min_stock' => 5,
        ]);

        app(StockManager::class)->receive($this->urun, $this->depo, 100, $this->sahip);
    }

    private function recorder(): SaleRecorder
    {
        return app(SaleRecorder::class);
    }

    private function satir(float $miktar = 2, int $fiyat = 10000, int $kdv = 20): array
    {
        return [[
            'product_id' => $this->urun->getKey(),
            'name' => $this->urun->name,
            'quantity' => $miktar,
            'unit_price_minor' => $fiyat,
            'vat_rate' => $kdv,
        ]];
    }

    public function test_satis_toplamlari_dogru_hesaplanir(): void
    {
        $sale = $this->recorder()->record($this->tenant, $this->satir(3, 10000, 20), warehouse: $this->depo);

        $this->assertSame(30000, $sale->subtotal_minor);
        $this->assertSame(6000, $sale->vat_minor);
        $this->assertSame(36000, $sale->total_minor);
    }

    public function test_indirim_kdvyi_de_azaltir(): void
    {
        $sale = $this->recorder()->record($this->tenant, $this->satir(3, 10000, 20), warehouse: $this->depo, discountMinor: 3000);

        $this->assertSame(30000, $sale->subtotal_minor);
        $this->assertSame(3000, $sale->discount_minor);
        $this->assertSame(5400, $sale->vat_minor);
        $this->assertSame(32400, $sale->total_minor);
    }

    public function test_satis_stoktan_duser(): void
    {
        $this->recorder()->record($this->tenant, $this->satir(12), warehouse: $this->depo, user: $this->sahip);

        $this->assertSame(88.0, $this->urun->fresh()->totalStock());
    }

    public function test_depo_secilmezse_stok_etkilenmez(): void
    {
        $this->recorder()->record($this->tenant, $this->satir(12), warehouse: null);

        $this->assertSame(100.0, $this->urun->fresh()->totalStock());
    }

    public function test_stoktan_fazla_satilamaz(): void
    {
        $this->expectException(StockException::class);

        $this->recorder()->record($this->tenant, $this->satir(150), warehouse: $this->depo);
    }

    public function test_yetersiz_stokta_satis_kaydedilmez(): void
    {
        try {
            $this->recorder()->record($this->tenant, $this->satir(150), warehouse: $this->depo);
        } catch (StockException) {
            // beklenen
        }

        $this->assertSame(0, Sale::query()->count());
        $this->assertSame(100.0, $this->urun->fresh()->totalStock());
    }

    public function test_belge_numarasi_sirali_uretilir(): void
    {
        $ilk = $this->recorder()->record($this->tenant, $this->satir(), warehouse: $this->depo);
        $ikinci = $this->recorder()->record($this->tenant, $this->satir(), warehouse: $this->depo);

        $yil = now()->year;

        $this->assertSame("SAT-{$yil}-0001", $ilk->number);
        $this->assertSame("SAT-{$yil}-0002", $ikinci->number);
    }

    public function test_belge_numarasi_kiraci_basina_baslar(): void
    {
        $this->recorder()->record($this->tenant, $this->satir(), warehouse: $this->depo);

        $beta = Tenant::factory()->create(['slug' => 'beta']);
        $betaDepo = Warehouse::factory()->create(['tenant_id' => $beta->getKey()]);
        $betaUrun = Product::factory()->create(['tenant_id' => $beta->getKey()]);
        app(StockManager::class)->receive($betaUrun, $betaDepo, 10);

        $betaSatis = $this->recorder()->record($beta, [[
            'product_id' => $betaUrun->getKey(),
            'name' => $betaUrun->name,
            'quantity' => 1,
            'unit_price_minor' => 5000,
            'vat_rate' => 20,
        ]], warehouse: $betaDepo);

        $this->assertSame('SAT-'.now()->year.'-0001', $betaSatis->number);
    }

    public function test_pesin_tahsilat_islenir(): void
    {
        $sale = $this->recorder()->record(
            $this->tenant, $this->satir(1, 10000, 20), warehouse: $this->depo, initialPaymentMinor: 12000
        );

        $this->assertSame(12000, $sale->paid_minor);
        $this->assertSame(PaymentStatus::Paid, $sale->paymentStatus());
        $this->assertSame(0, $sale->remainingMinor());
    }

    public function test_kismi_tahsilat_durumu(): void
    {
        $sale = $this->recorder()->record($this->tenant, $this->satir(1, 10000, 20), warehouse: $this->depo);
        $this->recorder()->addPayment($sale, 5000, PaymentMethod::Cash);

        $sale->refresh();

        $this->assertSame(PaymentStatus::Partial, $sale->paymentStatus());
        $this->assertSame(7000, $sale->remainingMinor());
    }

    public function test_borctan_fazla_tahsilat_reddedilir(): void
    {
        $sale = $this->recorder()->record($this->tenant, $this->satir(1, 10000, 20), warehouse: $this->depo);

        $this->expectException(SaleException::class);

        $this->recorder()->addPayment($sale, 99999, PaymentMethod::Cash);
    }

    public function test_iptal_stogu_geri_yukler(): void
    {
        $sale = $this->recorder()->record($this->tenant, $this->satir(20), warehouse: $this->depo, user: $this->sahip);

        $this->assertSame(80.0, $this->urun->fresh()->totalStock());

        $this->recorder()->cancel($sale, $this->sahip);

        $this->assertSame(SaleStatus::Cancelled, $sale->fresh()->status);
        $this->assertSame(100.0, $this->urun->fresh()->totalStock());
    }

    public function test_tahsilat_yapilmis_satis_iptal_edilemez(): void
    {
        $sale = $this->recorder()->record(
            $this->tenant, $this->satir(1), warehouse: $this->depo, initialPaymentMinor: 5000
        );

        $this->expectException(SaleException::class);

        $this->recorder()->cancel($sale);
    }

    public function test_bos_satisla_kayit_yapilamaz(): void
    {
        $this->expectException(SaleException::class);

        $this->recorder()->record($this->tenant, []);
    }

    public function test_form_uzerinden_satis_kaydedilir(): void
    {
        $musteri = Customer::factory()->create(['tenant_id' => $this->tenant->getKey()]);

        $this->actingAs($this->sahip)->post(self::HOST.'/satis', [
            'customer_id' => $musteri->getKey(),
            'warehouse_id' => $this->depo->getKey(),
            'sold_at' => now()->format('Y-m-d'),
            'indirim' => '0',
            'tahsilat' => '300,00',
            'method' => 'cash',
            'satirlar' => [
                ['product_id' => $this->urun->getKey(), 'quantity' => '2,5', 'unit_price' => '100,00'],
            ],
        ])->assertRedirect();

        $sale = Sale::query()->firstOrFail();

        $this->assertSame(25000, $sale->subtotal_minor);
        $this->assertSame(30000, $sale->total_minor);
        $this->assertSame(30000, $sale->paid_minor);
        $this->assertSame(PaymentStatus::Paid, $sale->paymentStatus());
        $this->assertSame(97.5, $this->urun->fresh()->totalStock());
    }

    public function test_urunsuz_satir_reddedilir(): void
    {
        $this->actingAs($this->sahip)->post(self::HOST.'/satis', [
            'sold_at' => now()->format('Y-m-d'),
            'indirim' => '0',
            'tahsilat' => '0',
            'method' => 'cash',
            'satirlar' => [['product_id' => '', 'quantity' => '0', 'unit_price' => '0']],
        ])->assertSessionHasErrors('satirlar');

        $this->assertSame(0, Sale::query()->count());
    }

    public function test_indirim_tutardan_buyuk_olamaz(): void
    {
        $this->actingAs($this->sahip)->post(self::HOST.'/satis', [
            'warehouse_id' => $this->depo->getKey(),
            'sold_at' => now()->format('Y-m-d'),
            'indirim' => '5000',
            'tahsilat' => '0',
            'method' => 'cash',
            'satirlar' => [['product_id' => $this->urun->getKey(), 'quantity' => '1', 'unit_price' => '100']],
        ])->assertSessionHasErrors('indirim');
    }

    public function test_baska_kiracinin_satisi_gorulemez(): void
    {
        $beta = Tenant::factory()->create(['slug' => 'beta']);
        $betaSatis = Sale::factory()->create(['tenant_id' => $beta->getKey(), 'number' => 'SAT-BETA-1']);

        $this->actingAs($this->sahip)->get(self::HOST.'/satis/'.$betaSatis->getKey())->assertNotFound();

        $this->actingAs($this->sahip)->get(self::HOST.'/satis')
            ->assertOk()
            ->assertDontSee('SAT-BETA-1');
    }

    public function test_modul_kapaliysa_erisilemez(): void
    {
        $beta = Tenant::factory()->create(['slug' => 'beta']);
        $betaSahip = User::factory()->forTenant($beta)->owner()->create();

        $this->actingAs($betaSahip)->get('http://beta.saas.local/satis')->assertNotFound();
    }

    public function test_tahsilat_bekleyen_filtresi(): void
    {
        $odenmis = $this->recorder()->record($this->tenant, $this->satir(1), warehouse: $this->depo, initialPaymentMinor: 12000);
        $bekleyen = $this->recorder()->record($this->tenant, $this->satir(1), warehouse: $this->depo);

        $this->actingAs($this->sahip)->get(self::HOST.'/satis?odeme=bekliyor')
            ->assertOk()
            ->assertSee($bekleyen->number)
            ->assertDontSee($odenmis->number);
    }
}
