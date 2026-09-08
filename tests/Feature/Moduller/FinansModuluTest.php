<?php

declare(strict_types=1);

namespace Tests\Feature\Moduller;

use App\Finance\CashBook;
use App\Inventory\InventoryProvisioner;
use App\Inventory\StockManager;
use App\Models\Product;
use App\Models\SalePayment;
use App\Models\Tenant;
use App\Models\Transaction;
use App\Models\TransactionCategory;
use App\Models\User;
use App\Models\Warehouse;
use App\Modules\Module;
use App\Modules\PaymentMethod;
use App\Modules\TransactionType;
use App\Sales\SaleRecorder;
use App\Tenancy\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FinansModuluTest extends TestCase
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
        $this->tenant->enableModule(Module::Finance);

        $this->sahip = User::factory()->forTenant($this->tenant)->owner()->create();
        $this->depo = app(InventoryProvisioner::class)->defaultWarehouse($this->tenant);

        app(TenantContext::class)->set($this->tenant);

        $this->urun = Product::factory()->create([
            'tenant_id' => $this->tenant->getKey(),
            'sale_price_minor' => 10000,
            'vat_rate' => 20,
        ]);

        app(StockManager::class)->receive($this->urun, $this->depo, 100, $this->sahip);
    }

    private function satis(int $pesin = 0)
    {
        return app(SaleRecorder::class)->record(
            tenant: $this->tenant,
            satirlar: [[
                'product_id' => $this->urun->getKey(),
                'name' => $this->urun->name,
                'quantity' => 1,
                'unit_price_minor' => 10000,
                'vat_rate' => 20,
            ]],
            warehouse: $this->depo,
            initialPaymentMinor: $pesin,
        );
    }

    public function test_modul_acilinca_kategoriler_olusur(): void
    {
        $this->assertSame(3, TransactionCategory::query()->ofType(TransactionType::Income)->count());
        $this->assertSame(9, TransactionCategory::query()->ofType(TransactionType::Expense)->count());

        $this->assertTrue(
            TransactionCategory::query()->where('name', 'Satış')->firstOrFail()->is_system
        );
    }

    public function test_satis_tahsilati_kasaya_gelir_yazar(): void
    {
        $sale = $this->satis(pesin: 12000);

        $islem = Transaction::query()->income()->firstOrFail();

        $this->assertSame(12000, $islem->amount_minor);
        $this->assertSame(SalePayment::class, $islem->source_type);
        $this->assertStringContainsString($sale->number, $islem->description);
        $this->assertTrue($islem->isAutomatic());
    }

    public function test_tahsilatsiz_satis_kasaya_yazilmaz(): void
    {
        $this->satis(pesin: 0);

        $this->assertSame(0, Transaction::query()->count());
    }

    public function test_sonradan_alinan_tahsilat_da_kasaya_yazilir(): void
    {
        $sale = $this->satis();

        app(SaleRecorder::class)->addPayment($sale, 5000, PaymentMethod::Transfer);

        $islem = Transaction::query()->income()->firstOrFail();

        $this->assertSame(5000, $islem->amount_minor);
        $this->assertSame(PaymentMethod::Transfer, $islem->method);
    }

    public function test_finans_kapaliysa_kasaya_yazilmaz(): void
    {
        $beta = Tenant::factory()->create(['slug' => 'beta']);
        $beta->enableModule(Module::Inventory);
        $betaDepo = app(InventoryProvisioner::class)->defaultWarehouse($beta);
        $betaUrun = Product::factory()->create(['tenant_id' => $beta->getKey()]);
        app(StockManager::class)->receive($betaUrun, $betaDepo, 10);

        app(SaleRecorder::class)->record(
            tenant: $beta,
            satirlar: [[
                'product_id' => $betaUrun->getKey(),
                'name' => $betaUrun->name,
                'quantity' => 1,
                'unit_price_minor' => 10000,
                'vat_rate' => 20,
            ]],
            warehouse: $betaDepo,
            initialPaymentMinor: 12000,
        );

        $this->assertSame(0, Transaction::query()->acrossTenants()->where('tenant_id', $beta->getKey())->count());
    }

    public function test_elle_gider_eklenir(): void
    {
        $kategori = TransactionCategory::query()->ofType(TransactionType::Expense)->where('name', 'Kira')->firstOrFail();

        $this->actingAs($this->sahip)->post(self::HOST.'/finans', [
            'type' => 'expense',
            'amount' => '12.500,00',
            'occurred_on' => now()->format('Y-m-d'),
            'category_id' => (string) $kategori->getKey(),
            'description' => 'Eylül kirası',
            'method' => 'transfer',
        ])->assertRedirect(self::HOST.'/finans');

        $islem = Transaction::query()->expense()->firstOrFail();

        $this->assertSame(1250000, $islem->amount_minor);
        $this->assertSame('Eylül kirası', $islem->description);
        $this->assertFalse($islem->isAutomatic());
    }

    public function test_sifir_tutar_reddedilir(): void
    {
        $this->actingAs($this->sahip)->post(self::HOST.'/finans', [
            'type' => 'expense',
            'amount' => '0',
            'occurred_on' => now()->format('Y-m-d'),
        ])->assertSessionHasErrors('amount');

        $this->assertSame(0, Transaction::query()->count());
    }

    public function test_kategori_turu_uyusmali(): void
    {
        $gelirKategorisi = TransactionCategory::query()->ofType(TransactionType::Income)->firstOrFail();

        $this->actingAs($this->sahip)->post(self::HOST.'/finans', [
            'type' => 'expense',
            'amount' => '100',
            'occurred_on' => now()->format('Y-m-d'),
            'category_id' => (string) $gelirKategorisi->getKey(),
        ])->assertSessionHasErrors('category_id');
    }

    public function test_ozet_gelir_gider_ve_neti_hesaplar(): void
    {
        $this->satis(pesin: 12000);
        Transaction::factory()->create(['tenant_id' => $this->tenant->getKey(), 'amount_minor' => 5000]);

        $ozet = app(CashBook::class)->summary(now()->startOfMonth(), now()->endOfMonth());

        $this->assertSame(12000, $ozet['gelir']);
        $this->assertSame(5000, $ozet['gider']);
        $this->assertSame(7000, $ozet['net']);
    }

    public function test_ozet_ekranda_gorunur(): void
    {
        $this->satis(pesin: 12000);

        $this->actingAs($this->sahip)->get(self::HOST.'/finans')
            ->assertOk()
            ->assertSee('Gelir')
            ->assertSee('Gider')
            ->assertSee('Net')
            ->assertSee('satıştan');
    }

    public function test_otomatik_kayit_elle_duzenlenemez(): void
    {
        $this->satis(pesin: 12000);

        $islem = Transaction::query()->income()->firstOrFail();

        $this->actingAs($this->sahip)->get(self::HOST.'/finans/'.$islem->getKey().'/duzenle')->assertForbidden();
        $this->actingAs($this->sahip)->delete(self::HOST.'/finans/'.$islem->getKey())->assertForbidden();

        $this->assertSame(1, Transaction::query()->count());
    }

    public function test_elle_kayit_duzenlenir_ve_silinir(): void
    {
        $islem = Transaction::factory()->create([
            'tenant_id' => $this->tenant->getKey(),
            'amount_minor' => 5000,
            'description' => 'Eski',
        ]);

        $this->actingAs($this->sahip)->put(self::HOST.'/finans/'.$islem->getKey(), [
            'type' => 'expense',
            'amount' => '75,50',
            'occurred_on' => now()->format('Y-m-d'),
            'description' => 'Yeni',
        ])->assertRedirect(self::HOST.'/finans');

        $this->assertSame(7550, $islem->fresh()->amount_minor);
        $this->assertSame('Yeni', $islem->fresh()->description);

        $this->actingAs($this->sahip)->delete(self::HOST.'/finans/'.$islem->getKey());

        $this->assertSame(0, Transaction::query()->count());
    }

    public function test_baska_kiracinin_kaydi_gorulemez(): void
    {
        $beta = Tenant::factory()->create(['slug' => 'beta']);
        $betaIslem = Transaction::factory()->create(['tenant_id' => $beta->getKey(), 'description' => 'Beta gideri']);

        $this->actingAs($this->sahip)->get(self::HOST.'/finans')
            ->assertOk()
            ->assertDontSee('Beta gideri');

        $this->actingAs($this->sahip)
            ->get(self::HOST.'/finans/'.$betaIslem->getKey().'/duzenle')
            ->assertNotFound();
    }

    public function test_modul_kapaliysa_erisilemez(): void
    {
        $beta = Tenant::factory()->create(['slug' => 'beta']);
        $betaSahip = User::factory()->forTenant($beta)->owner()->create();

        $this->actingAs($betaSahip)->get('http://beta.saas.local/finans')->assertNotFound();
    }
}
