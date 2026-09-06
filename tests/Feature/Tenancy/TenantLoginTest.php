<?php

declare(strict_types=1);

namespace Tests\Feature\Tenancy;

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class TenantLoginTest extends TestCase
{
    use RefreshDatabase;

    private Tenant $acme;

    private Tenant $beta;

    private User $acmeKullanici;

    protected function setUp(): void
    {
        parent::setUp();

        $this->acme = Tenant::factory()->create(['slug' => 'acme']);
        $this->beta = Tenant::factory()->create(['slug' => 'beta']);

        $this->acmeKullanici = User::factory()->forTenant($this->acme)->create([
            'email' => 'ortak@ornek.com',
            'password' => Hash::make('gizli12345'),
        ]);

        User::factory()->forTenant($this->beta)->create([
            'email' => 'ortak@ornek.com',
            'password' => Hash::make('baskabir12345'),
        ]);
    }

    public function test_giris_sayfasi_tenant_hostunda_acilir(): void
    {
        $this->get('http://acme.saas.local/giris')
            ->assertOk()
            ->assertSee($this->acme->name);
    }

    public function test_giris_sayfasi_merkezi_hostta_acilmaz(): void
    {
        $this->get('http://saas.local/giris')->assertNotFound();
    }

    public function test_dogru_bilgilerle_giris_yapilir(): void
    {
        $this->post('http://acme.saas.local/giris', [
            'email' => 'ortak@ornek.com',
            'password' => 'gizli12345',
        ])->assertRedirect('http://acme.saas.local/panel');

        $this->assertAuthenticatedAs($this->acmeKullanici);
    }

    public function test_baska_tenantin_parolasiyla_giris_yapilamaz(): void
    {
        $this->post('http://acme.saas.local/giris', [
            'email' => 'ortak@ornek.com',
            'password' => 'baskabir12345',
        ])->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_yanlis_parola_reddedilir(): void
    {
        $this->post('http://acme.saas.local/giris', [
            'email' => 'ortak@ornek.com',
            'password' => 'yanlisparola',
        ])->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_eposta_buyuk_kucuk_harf_duyarsiz(): void
    {
        $this->post('http://acme.saas.local/giris', [
            'email' => 'ORTAK@ornek.com',
            'password' => 'gizli12345',
        ])->assertRedirect('http://acme.saas.local/panel');

        $this->assertAuthenticatedAs($this->acmeKullanici);
    }

    public function test_panel_giris_yapmadan_acilmaz(): void
    {
        $this->get('http://acme.saas.local/panel')
            ->assertRedirect('http://acme.saas.local/giris');
    }

    public function test_panel_kiraci_bilgilerini_gosterir(): void
    {
        $this->actingAs($this->acmeKullanici)
            ->get('http://acme.saas.local/panel')
            ->assertOk()
            ->assertSee($this->acme->name)
            ->assertSee('acme.saas.local');
    }

    public function test_cikis_oturumu_kapatir(): void
    {
        $this->actingAs($this->acmeKullanici)
            ->post('http://acme.saas.local/cikis')
            ->assertRedirect('http://acme.saas.local/giris');

        $this->assertGuest();
    }

    public function test_kok_adres_tenant_hostunda_panele_yonlendirir(): void
    {
        $this->actingAs($this->acmeKullanici)
            ->get('http://acme.saas.local/')
            ->assertRedirect('http://acme.saas.local/panel');
    }
}
