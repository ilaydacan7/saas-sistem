<?php

declare(strict_types=1);

namespace Tests\Feature\Moduller;

use App\Inventory\InventoryProvisioner;
use App\Inventory\StockException;
use App\Inventory\StockManager;
use App\Models\Product;
use App\Models\StockMovement;
use App\Models\Tenant;
use App\Models\Unit;
use App\Models\User;
use App\Models\Warehouse;
use App\Modules\Module;
use App\Modules\ProductType;
use App\Modules\StockMovementType;
use App\Tenancy\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StokModuluTest extends TestCase
{
    use RefreshDatabase;

    private const HOST = 'http://acme.saas.local';

    private Tenant $tenant;

    private User $sahip;

    private Warehouse $depo;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tenant = Tenant::factory()->create(['slug' => 'acme']);
        $this->tenant->enableModule(Module::Customers);
        $this->tenant->enableModule(Module::Inventory);

        $this->sahip = User::factory()->forTenant($this->tenant)->owner()->create();
        $this->depo = app(InventoryProvisioner::class)->defaultWarehouse($this->tenant);

        // Gerçek istekte ResolveTenant kurar; doğrudan servis çağrılarında elle kuruyoruz.
        app(TenantContext::class)->set($this->tenant);
    }

    private function stok(): StockManager
    {
        return app(StockManager::class);
    }

    private function urun(array $degisiklik = []): Product
    {
        return Product::factory()->create(array_merge(['tenant_id' => $this->tenant->getKey()], $degisiklik));
    }

    public function test_modul_acilinca_birimler_ve_depo_olusur(): void
    {
        $this->assertSame(count(Unit::defaults()), Unit::query()->count());
        $this->assertSame(1, Warehouse::query()->count());
        $this->assertTrue($this->depo->is_default);
    }

    public function test_modul_kapali_kiracida_erisilemez(): void
    {
        $beta = Tenant::factory()->create(['slug' => 'beta']);
        $betaSahip = User::factory()->forTenant($beta)->owner()->create();

        $this->actingAs($betaSahip)->get('http://beta.saas.local/stok')->assertNotFound();
    }

    public function test_urun_olusturulur(): void
    {
        $this->actingAs($this->sahip)->post(self::HOST.'/stok', [
            'type' => 'product',
            'name' => 'Çelik Vida 5mm',
            'sku' => 'vd-005',
            'purchase_price' => '12,50',
            'sale_price' => '1.499,90',
            'vat_rate' => 20,
            'min_stock' => '10',
            'is_active' => '1',
        ])->assertRedirect(self::HOST.'/stok');

        $urun = Product::query()->firstOrFail();

        $this->assertSame('Çelik Vida 5mm', $urun->name);
        $this->assertSame('VD-005', $urun->sku);
        $this->assertSame(1250, $urun->purchase_price_minor);
        $this->assertSame(149990, $urun->sale_price_minor);
        $this->assertTrue($urun->tracks_stock);
    }

    public function test_hizmet_kaydinda_stok_takibi_kapanir(): void
    {
        $this->actingAs($this->sahip)->post(self::HOST.'/stok', [
            'type' => 'service',
            'name' => 'Montaj Hizmeti',
            'purchase_price' => '0',
            'sale_price' => '500',
            'vat_rate' => 20,
            'min_stock' => '99',
            'is_active' => '1',
        ])->assertSessionHasNoErrors();

        $hizmet = Product::query()->firstOrFail();

        $this->assertSame(ProductType::Service, $hizmet->type);
        $this->assertFalse($hizmet->tracks_stock);
        $this->assertSame('0.000', $hizmet->min_stock);
    }

    public function test_ayni_stok_kodu_iki_kez_kullanilamaz(): void
    {
        $this->urun(['sku' => 'AYNI-1']);

        $this->actingAs($this->sahip)->post(self::HOST.'/stok', [
            'type' => 'product',
            'name' => 'İkinci ürün',
            'sku' => 'ayni-1',
            'purchase_price' => '1',
            'sale_price' => '2',
            'vat_rate' => 20,
            'min_stock' => '0',
        ])->assertSessionHasErrors('sku');
    }

    public function test_ayni_stok_kodu_farkli_kiracilarda_kullanilabilir(): void
    {
        $beta = Tenant::factory()->create(['slug' => 'beta']);
        Product::factory()->create(['tenant_id' => $beta->getKey(), 'sku' => 'ORTAK-1']);

        $this->actingAs($this->sahip)->post(self::HOST.'/stok', [
            'type' => 'product',
            'name' => 'Acme ürünü',
            'sku' => 'ORTAK-1',
            'purchase_price' => '1',
            'sale_price' => '2',
            'vat_rate' => 20,
            'min_stock' => '0',
        ])->assertSessionHasNoErrors();
    }

    public function test_stok_girisi_bakiyeyi_artirir(): void
    {
        $urun = $this->urun();

        $hareket = $this->stok()->receive($urun, $this->depo, 25, $this->sahip);

        $this->assertSame('25.000', $hareket->balance_after);
        $this->assertSame(25.0, $urun->fresh()->totalStock());
    }

    public function test_stok_cikisi_bakiyeyi_azaltir(): void
    {
        $urun = $this->urun();
        $this->stok()->receive($urun, $this->depo, 25, $this->sahip);

        $hareket = $this->stok()->issue($urun, $this->depo, 10, $this->sahip);

        $this->assertSame('-10.000', $hareket->quantity);
        $this->assertSame('15.000', $hareket->balance_after);
        $this->assertSame(15.0, $urun->fresh()->totalStock());
    }

    public function test_stok_eksiye_dusemez(): void
    {
        $urun = $this->urun();
        $this->stok()->receive($urun, $this->depo, 5, $this->sahip);

        $this->expectException(StockException::class);

        $this->stok()->issue($urun, $this->depo, 6, $this->sahip);
    }

    public function test_yetersiz_stok_formda_hata_dondurur(): void
    {
        $urun = $this->urun();
        $this->stok()->receive($urun, $this->depo, 2, $this->sahip);

        $this->actingAs($this->sahip)->post(self::HOST.'/stok/hareket', [
            'product_id' => $urun->getKey(),
            'warehouse_id' => $this->depo->getKey(),
            'type' => 'out',
            'quantity' => '5',
        ])->assertSessionHasErrors('quantity');

        $this->assertSame(2.0, $urun->fresh()->totalStock());
    }

    public function test_duzeltme_sayim_sonucunu_yazar(): void
    {
        $urun = $this->urun();
        $this->stok()->receive($urun, $this->depo, 30, $this->sahip);

        $hareket = $this->stok()->adjust($urun, $this->depo, 27.5, $this->sahip, 'Yıl sonu sayımı');

        $this->assertSame(StockMovementType::Adjustment, $hareket->type);
        $this->assertSame('-2.500', $hareket->quantity);
        $this->assertSame('27.500', $hareket->balance_after);
        $this->assertSame(27.5, $urun->fresh()->totalStock());
    }

    public function test_stok_takibi_kapali_urune_hareket_islenemez(): void
    {
        $hizmet = $this->urun(['type' => ProductType::Service, 'tracks_stock' => false]);

        $this->expectException(StockException::class);

        $this->stok()->receive($hizmet, $this->depo, 5, $this->sahip);
    }

    public function test_sifir_veya_negatif_miktar_reddedilir(): void
    {
        $urun = $this->urun();

        $this->expectException(StockException::class);

        $this->stok()->receive($urun, $this->depo, 0, $this->sahip);
    }

    public function test_hareket_formu_bakiyeyi_gunceller(): void
    {
        $urun = $this->urun();

        $this->actingAs($this->sahip)->post(self::HOST.'/stok/hareket', [
            'product_id' => $urun->getKey(),
            'warehouse_id' => $this->depo->getKey(),
            'type' => 'in',
            'quantity' => '12,5',
            'note' => 'Fatura 2026-1',
        ])->assertRedirect(self::HOST.'/stok/hareketler');

        $this->assertSame(12.5, $urun->fresh()->totalStock());
        $this->assertSame('Fatura 2026-1', StockMovement::query()->firstOrFail()->note);
    }

    public function test_baska_kiracinin_urunune_hareket_islenemez(): void
    {
        $beta = Tenant::factory()->create(['slug' => 'beta']);
        $betaUrun = Product::factory()->create(['tenant_id' => $beta->getKey()]);

        $this->actingAs($this->sahip)->post(self::HOST.'/stok/hareket', [
            'product_id' => $betaUrun->getKey(),
            'warehouse_id' => $this->depo->getKey(),
            'type' => 'in',
            'quantity' => '5',
        ])->assertSessionHasErrors('product_id');
    }

    public function test_kritik_stok_filtresi(): void
    {
        $kritik = $this->urun(['name' => 'Kritik Urun', 'min_stock' => 10]);
        $bol = $this->urun(['name' => 'Bol Urun', 'min_stock' => 10]);

        $this->stok()->receive($kritik, $this->depo, 3, $this->sahip);
        $this->stok()->receive($bol, $this->depo, 50, $this->sahip);

        $this->actingAs($this->sahip)->get(self::HOST.'/stok?stok=kritik')
            ->assertOk()
            ->assertSee('Kritik Urun')
            ->assertDontSee('Bol Urun');
    }

    public function test_tukenen_urun_filtresi(): void
    {
        $tukenen = $this->urun(['name' => 'Tukenen Urun']);
        $bol = $this->urun(['name' => 'Bol Urun']);

        $this->stok()->receive($bol, $this->depo, 50, $this->sahip);

        $this->actingAs($this->sahip)->get(self::HOST.'/stok?stok=tukendi')
            ->assertOk()
            ->assertSee('Tukenen Urun')
            ->assertDontSee('Bol Urun');
    }

    public function test_baska_kiracinin_urunleri_listede_gorunmez(): void
    {
        $beta = Tenant::factory()->create(['slug' => 'beta']);
        Product::factory()->create(['tenant_id' => $beta->getKey(), 'name' => 'Beta Urunu']);
        $this->urun(['name' => 'Acme Urunu']);

        $this->actingAs($this->sahip)->get(self::HOST.'/stok')
            ->assertOk()
            ->assertSee('Acme Urunu')
            ->assertDontSee('Beta Urunu');
    }

    public function test_hareket_gecmisi_kiraci_kapsamli(): void
    {
        $beta = Tenant::factory()->create(['slug' => 'beta']);
        $betaUrun = Product::factory()->create(['tenant_id' => $beta->getKey(), 'name' => 'Beta Urunu']);
        $betaDepo = Warehouse::factory()->create(['tenant_id' => $beta->getKey()]);
        $this->stok()->receive($betaUrun, $betaDepo, 5);

        $acmeUrun = $this->urun(['name' => 'Acme Urunu']);
        $this->stok()->receive($acmeUrun, $this->depo, 5);

        $this->actingAs($this->sahip)->get(self::HOST.'/stok/hareketler')
            ->assertOk()
            ->assertSee('Acme Urunu')
            ->assertDontSee('Beta Urunu');
    }
}
