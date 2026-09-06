<?php

declare(strict_types=1);

namespace Tests\Feature\Moduller;

use App\Models\Customer;
use App\Models\Tenant;
use App\Models\User;
use App\Modules\CustomerType;
use App\Modules\Module;
use App\Tenancy\TenantRole;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MusteriModuluTest extends TestCase
{
    use RefreshDatabase;

    private const HOST = 'http://acme.saas.local';

    private Tenant $tenant;

    private User $sahip;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tenant = Tenant::factory()->create(['slug' => 'acme']);
        $this->tenant->enableModule(Module::Customers);

        $this->sahip = User::factory()->forTenant($this->tenant)->owner()->create();
    }

    private function veri(array $degisiklik = []): array
    {
        return array_merge([
            'type' => 'individual',
            'name' => 'Ayşe Yılmaz',
            'phone' => '0532 111 22 33',
            'email' => 'ayse@ornek.com',
            'city' => 'İstanbul',
            'is_active' => '1',
        ], $degisiklik);
    }

    public function test_modul_kapaliysa_erisilemez(): void
    {
        $this->tenant->disableModule(Module::Catalog);

        $beta = Tenant::factory()->create(['slug' => 'beta']);
        $betaSahip = User::factory()->forTenant($beta)->owner()->create();

        $this->actingAs($betaSahip)->get('http://beta.saas.local/musteriler')->assertNotFound();
    }

    public function test_modul_acikken_liste_gorunur(): void
    {
        Customer::factory()->create(['tenant_id' => $this->tenant->getKey(), 'name' => 'Ali Veli']);

        $this->actingAs($this->sahip)->get(self::HOST.'/musteriler')
            ->assertOk()
            ->assertSee('Ali Veli');
    }

    public function test_musteri_olusturulur(): void
    {
        $this->actingAs($this->sahip)
            ->post(self::HOST.'/musteriler', $this->veri())
            ->assertRedirect(self::HOST.'/musteriler');

        $musteri = Customer::query()->firstOrFail();

        $this->assertSame('Ayşe Yılmaz', $musteri->name);
        $this->assertSame($this->tenant->getKey(), $musteri->tenant_id);
        $this->assertSame($this->sahip->getKey(), $musteri->created_by_id);
        $this->assertSame(CustomerType::Individual, $musteri->type);
    }

    public function test_telefon_normallestirilir(): void
    {
        $this->actingAs($this->sahip)->post(self::HOST.'/musteriler', $this->veri(['phone' => '(0532) 111-22 33']));

        $this->assertSame('05321112233', Customer::query()->firstOrFail()->phone);
    }

    public function test_kurumsal_kayitta_unvan_zorunlu(): void
    {
        $this->actingAs($this->sahip)
            ->post(self::HOST.'/musteriler', $this->veri(['type' => 'company', 'company_name' => '']))
            ->assertSessionHasErrors('company_name');

        $this->assertSame(0, Customer::query()->count());
    }

    public function test_kurumsal_kayit_unvanla_olusur(): void
    {
        $this->actingAs($this->sahip)
            ->post(self::HOST.'/musteriler', $this->veri([
                'type' => 'company',
                'company_name' => 'Acme Ticaret Ltd.',
                'tax_number' => '1234567890',
            ]))
            ->assertSessionHasNoErrors();

        $musteri = Customer::query()->firstOrFail();

        $this->assertSame('Acme Ticaret Ltd.', $musteri->displayName());
        $this->assertSame('1234567890', $musteri->tax_number);
    }

    public function test_gecersiz_vergi_no_reddedilir(): void
    {
        $this->actingAs($this->sahip)
            ->post(self::HOST.'/musteriler', $this->veri(['tax_number' => '123']))
            ->assertSessionHasErrors('tax_number');
    }

    public function test_musteri_guncellenir(): void
    {
        $musteri = Customer::factory()->create(['tenant_id' => $this->tenant->getKey()]);

        $this->actingAs($this->sahip)
            ->put(self::HOST.'/musteriler/'.$musteri->getKey(), $this->veri(['name' => 'Yeni Ad']))
            ->assertRedirect(self::HOST.'/musteriler');

        $this->assertSame('Yeni Ad', $musteri->fresh()->name);
    }

    public function test_musteri_silinir(): void
    {
        $musteri = Customer::factory()->create(['tenant_id' => $this->tenant->getKey()]);

        $this->actingAs($this->sahip)
            ->delete(self::HOST.'/musteriler/'.$musteri->getKey())
            ->assertRedirect(self::HOST.'/musteriler');

        $this->assertSoftDeleted('customers', ['id' => $musteri->getKey()]);
    }

    public function test_baska_kiracinin_musterisi_gorulemez(): void
    {
        $beta = Tenant::factory()->create(['slug' => 'beta']);
        $beta->enableModule(Module::Customers);
        $betaMusteri = Customer::factory()->create(['tenant_id' => $beta->getKey(), 'name' => 'Beta Musterisi']);

        $this->actingAs($this->sahip)->get(self::HOST.'/musteriler')
            ->assertOk()
            ->assertDontSee('Beta Musterisi');

        $this->actingAs($this->sahip)
            ->get(self::HOST.'/musteriler/'.$betaMusteri->getKey().'/duzenle')
            ->assertNotFound();
    }

    public function test_baska_kiracinin_musterisi_guncellenemez(): void
    {
        $beta = Tenant::factory()->create(['slug' => 'beta']);
        $betaMusteri = Customer::factory()->create(['tenant_id' => $beta->getKey(), 'name' => 'Beta Musterisi']);

        $this->actingAs($this->sahip)
            ->put(self::HOST.'/musteriler/'.$betaMusteri->getKey(), $this->veri())
            ->assertNotFound();

        $this->assertSame('Beta Musterisi', $betaMusteri->fresh()->name);
    }

    public function test_arama_calisir(): void
    {
        Customer::factory()->create(['tenant_id' => $this->tenant->getKey(), 'name' => 'Ahmet Kaya', 'phone' => '05321112233']);
        Customer::factory()->create(['tenant_id' => $this->tenant->getKey(), 'name' => 'Mehmet Demir', 'phone' => '05339998877']);

        $this->actingAs($this->sahip)->get(self::HOST.'/musteriler?q=Ahmet')
            ->assertOk()
            ->assertSee('Ahmet Kaya')
            ->assertDontSee('Mehmet Demir');

        $this->actingAs($this->sahip)->get(self::HOST.'/musteriler?q=9998877')
            ->assertOk()
            ->assertSee('Mehmet Demir')
            ->assertDontSee('Ahmet Kaya');
    }

    public function test_pasif_kayitlar_varsayilan_listede_gorunmez(): void
    {
        Customer::factory()->passive()->create(['tenant_id' => $this->tenant->getKey(), 'name' => 'Pasif Kisi']);
        Customer::factory()->create(['tenant_id' => $this->tenant->getKey(), 'name' => 'Aktif Kisi']);

        $this->actingAs($this->sahip)->get(self::HOST.'/musteriler')
            ->assertSee('Aktif Kisi')
            ->assertDontSee('Pasif Kisi');

        $this->actingAs($this->sahip)->get(self::HOST.'/musteriler?durum=pasif')
            ->assertSee('Pasif Kisi')
            ->assertDontSee('Aktif Kisi');
    }

    public function test_uye_de_musterileri_yonetebilir(): void
    {
        $uye = User::factory()->forTenant($this->tenant)->role(TenantRole::Member)->create();

        $this->actingAs($uye)->get(self::HOST.'/musteriler')->assertOk();
        $this->actingAs($uye)->post(self::HOST.'/musteriler', $this->veri())->assertSessionHasNoErrors();
    }

    public function test_giris_yapmadan_erisilemez(): void
    {
        $this->get(self::HOST.'/musteriler')->assertRedirect(self::HOST.'/giris');
    }
}
