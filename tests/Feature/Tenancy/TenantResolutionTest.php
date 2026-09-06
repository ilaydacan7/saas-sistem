<?php

declare(strict_types=1);

namespace Tests\Feature\Tenancy;

use App\Models\Tenant;
use App\Tenancy\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class TenantResolutionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Route::middleware('web')->get('/_test/tenant', function (TenantContext $context) {
            return response()->json([
                'slug' => $context->get()?->slug,
            ]);
        });

        Route::middleware('web')->get('/_test/billing', fn () => 'billing')->name('billing.overdue');

        Route::getRoutes()->refreshNameLookups();
    }

    public function test_subdomain_tenant_ile_eslesir(): void
    {
        $tenant = Tenant::factory()->create(['slug' => 'acme']);

        $this->get('http://acme.saas.local/_test/tenant')
            ->assertOk()
            ->assertJson(['slug' => $tenant->slug]);
    }

    public function test_ozel_alan_adi_tenant_ile_eslesir(): void
    {
        Tenant::factory()->withCustomDomain('panel.musteri.com')->create(['slug' => 'musteri']);

        $this->get('http://panel.musteri.com/_test/tenant')
            ->assertOk()
            ->assertJson(['slug' => 'musteri']);
    }

    public function test_merkezi_host_tenant_baglami_kurmaz(): void
    {
        $this->get('http://saas.local/_test/tenant')
            ->assertOk()
            ->assertJson(['slug' => null]);
    }

    public function test_rezerve_subdomain_tenant_sayilmaz(): void
    {
        Tenant::factory()->create(['slug' => 'www']);

        $this->get('http://www.saas.local/_test/tenant')
            ->assertOk()
            ->assertJson(['slug' => null]);
    }

    public function test_bilinmeyen_subdomain_404_verir(): void
    {
        $this->get('http://yok.saas.local/_test/tenant')->assertNotFound();
    }

    public function test_askiya_alinmis_tenant_engellenir(): void
    {
        Tenant::factory()->suspended()->create(['slug' => 'askida']);

        $this->get('http://askida.saas.local/_test/tenant')->assertForbidden();
    }

    public function test_odemesi_geciken_tenant_faturalandirmaya_yonlendirilir(): void
    {
        Tenant::factory()->pastDue()->create(['slug' => 'gecikmis']);

        $this->get('http://gecikmis.saas.local/_test/tenant')
            ->assertRedirect(route('billing.overdue'));
    }

    public function test_odemesi_geciken_tenant_faturalandirma_sayfasini_gorebilir(): void
    {
        Tenant::factory()->pastDue()->create(['slug' => 'gecikmis']);

        $this->get('http://gecikmis.saas.local/_test/billing')
            ->assertOk()
            ->assertSee('billing');
    }
}
