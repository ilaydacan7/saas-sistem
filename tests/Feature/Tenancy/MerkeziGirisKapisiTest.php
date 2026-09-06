<?php

declare(strict_types=1);

namespace Tests\Feature\Tenancy;

use App\Models\Tenant;
use App\Models\User;
use App\Notifications\WorkspaceReminder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class MerkeziGirisKapisiTest extends TestCase
{
    use RefreshDatabase;

    private const MERKEZ = 'http://saas.local';

    private Tenant $acme;

    protected function setUp(): void
    {
        parent::setUp();

        $this->acme = Tenant::factory()->create(['slug' => 'acme', 'name' => 'Acme A.Ş.']);
    }

    public function test_merkezde_giris_kapisi_acilir(): void
    {
        $this->get(self::MERKEZ.'/giris')
            ->assertOk()
            ->assertSee('Şirket adresiniz')
            ->assertSee('Şirket adresimi hatırlamıyorum');
    }

    public function test_kiraci_adresinde_normal_giris_formu_acilir(): void
    {
        $this->get('http://acme.saas.local/giris')
            ->assertOk()
            ->assertSee('Acme A.Ş.')
            ->assertSee('Parolamı unuttum');
    }

    public function test_slug_ile_kiraci_adresine_yonlendirir(): void
    {
        $this->post(self::MERKEZ.'/giris', ['adres' => 'acme'])
            ->assertRedirect('http://acme.saas.local/giris');
    }

    public function test_tam_adres_yazilinca_da_calisir(): void
    {
        $this->post(self::MERKEZ.'/giris', ['adres' => 'ACME.saas.local'])
            ->assertRedirect('http://acme.saas.local/giris');
    }

    public function test_url_yapistirilinca_da_calisir(): void
    {
        $this->post(self::MERKEZ.'/giris', ['adres' => 'http://acme.saas.local/panel'])
            ->assertRedirect('http://acme.saas.local/giris');
    }

    public function test_ozel_alan_adi_ile_bulunur(): void
    {
        Tenant::factory()->withCustomDomain('panel.musteri.com')->create(['slug' => 'musteri']);

        $this->post(self::MERKEZ.'/giris', ['adres' => 'panel.musteri.com'])
            ->assertRedirect('http://panel.musteri.com/giris');
    }

    public function test_olmayan_adres_hata_verir(): void
    {
        $this->post(self::MERKEZ.'/giris', ['adres' => 'yokboylesirket'])
            ->assertSessionHasErrors('adres');
    }

    public function test_bos_adres_reddedilir(): void
    {
        $this->post(self::MERKEZ.'/giris', ['adres' => ''])
            ->assertSessionHasErrors('adres');
    }

    public function test_adres_hatirlatma_epostasi_gonderilir(): void
    {
        Notification::fake();

        $beta = Tenant::factory()->create(['slug' => 'beta', 'name' => 'Beta Ltd']);

        User::factory()->forTenant($this->acme)->create(['email' => 'ortak@ornek.com']);
        User::factory()->forTenant($beta)->create(['email' => 'ortak@ornek.com']);

        $this->post(self::MERKEZ.'/adresimi-bul', ['email' => 'ortak@ornek.com'])
            ->assertSessionHas('durum');

        Notification::assertSentOnDemand(WorkspaceReminder::class, function ($notification, $channels, $notifiable) {
            return $notifiable->routes['mail'] === 'ortak@ornek.com';
        });
    }

    public function test_kayitsiz_eposta_ayni_mesaji_dondurur(): void
    {
        Notification::fake();

        $this->post(self::MERKEZ.'/adresimi-bul', ['email' => 'yok@ornek.com'])
            ->assertSessionHas('durum')
            ->assertSessionHasNoErrors();

        Notification::assertNothingSent();
    }

    public function test_superadmin_hesabi_adres_hatirlatmasina_dahil_edilmez(): void
    {
        Notification::fake();

        User::factory()->superAdmin()->create(['email' => 'admin@saas.local']);

        $this->post(self::MERKEZ.'/adresimi-bul', ['email' => 'admin@saas.local']);

        Notification::assertNothingSent();
    }

    public function test_giris_yapmis_kullanici_giris_kapisina_ugramaz(): void
    {
        $kullanici = User::factory()->forTenant($this->acme)->create();

        $this->actingAs($kullanici)->get('http://acme.saas.local/giris')
            ->assertRedirect('http://acme.saas.local/panel');
    }

    public function test_karsilama_sayfasinda_giris_baglantisi_var(): void
    {
        $this->get(self::MERKEZ.'/')
            ->assertOk()
            ->assertSee('Giriş yap')
            ->assertSee(route('giris'), false);
    }
}
