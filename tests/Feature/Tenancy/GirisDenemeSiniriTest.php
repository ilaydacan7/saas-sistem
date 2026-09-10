<?php

declare(strict_types=1);

namespace Tests\Feature\Tenancy;

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Tests\TestCase;

class GirisDenemeSiniriTest extends TestCase
{
    use RefreshDatabase;

    private Tenant $acme;

    private User $kullanici;

    protected function setUp(): void
    {
        parent::setUp();

        RateLimiter::clear('');

        $this->acme = Tenant::factory()->create(['slug' => 'acme']);

        $this->kullanici = User::factory()->forTenant($this->acme)->create([
            'email' => 'kurban@ornek.com',
            'password' => Hash::make('dogruparola123'),
        ]);
    }

    private function dene(string $parola, string $host = 'http://acme.saas.local', string $eposta = 'kurban@ornek.com')
    {
        return $this->post($host.'/giris', ['email' => $eposta, 'password' => $parola]);
    }

    public function test_bes_basarisiz_denemeden_sonra_kilitlenir(): void
    {
        for ($i = 0; $i < 5; $i++) {
            $this->dene('yanlis'.$i)->assertSessionHasErrors('email');
        }

        $yanit = $this->dene('yinelenlis');

        $yanit->assertSessionHasErrors('email');
        $this->assertStringContainsString(
            'Çok fazla başarısız deneme',
            session('errors')->first('email')
        );
    }

    public function test_kilitliyken_dogru_parola_da_calismaz(): void
    {
        for ($i = 0; $i < 5; $i++) {
            $this->dene('yanlis'.$i);
        }

        $this->dene('dogruparola123')->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_basarili_giristen_sonra_sayac_sifirlanir(): void
    {
        for ($i = 0; $i < 3; $i++) {
            $this->dene('yanlis'.$i);
        }

        $this->dene('dogruparola123')->assertRedirect('http://acme.saas.local/panel');
        $this->assertAuthenticatedAs($this->kullanici);

        // Sayaç sıfırlandığı için yeniden beş hakkı olmalı.
        $this->post('http://acme.saas.local/cikis');

        for ($i = 0; $i < 4; $i++) {
            $this->dene('yanlis'.$i)->assertSessionHasErrors('email');
        }

        $this->dene('dogruparola123')->assertRedirect('http://acme.saas.local/panel');
    }

    public function test_bir_kullanicinin_kilitlenmesi_digerini_etkilemez(): void
    {
        $ikinci = User::factory()->forTenant($this->acme)->create([
            'email' => 'digeri@ornek.com',
            'password' => Hash::make('baskaparola123'),
        ]);

        for ($i = 0; $i < 5; $i++) {
            $this->dene('yanlis'.$i);
        }

        // Aynı IP'den farklı hesap hâlâ girebilmeli: hesap kilitleme saldırısına
        // karşı koruma ile kullanılabilirlik dengesi.
        $this->dene('baskaparola123', eposta: 'digeri@ornek.com')
            ->assertRedirect('http://acme.saas.local/panel');

        $this->assertAuthenticatedAs($ikinci);
    }

    public function test_ayni_eposta_farkli_kiracida_kilitlenmez(): void
    {
        $beta = Tenant::factory()->create(['slug' => 'beta']);

        $betaKullanici = User::factory()->forTenant($beta)->create([
            'email' => 'kurban@ornek.com',
            'password' => Hash::make('betaparola123'),
        ]);

        for ($i = 0; $i < 5; $i++) {
            $this->dene('yanlis'.$i);
        }

        $this->dene('betaparola123', host: 'http://beta.saas.local')
            ->assertRedirect('http://beta.saas.local/panel');

        $this->assertAuthenticatedAs($betaKullanici);
    }

    public function test_ip_basina_genis_sinir_eposta_degistirerek_asilamaz(): void
    {
        // Saldırgan her denemede farklı e-posta kullanarak hesap sınırını atlar,
        // ama IP sınırına takılmalı.
        for ($i = 0; $i < 20; $i++) {
            $this->dene('yanlis', eposta: "deneme{$i}@ornek.com");
        }

        $this->dene('dogruparola123')->assertSessionHasErrors('email');

        $this->assertStringContainsString(
            'Çok fazla başarısız deneme',
            session('errors')->first('email')
        );
        $this->assertGuest();
    }

    public function test_yonetim_girisi_de_sinirlanir(): void
    {
        User::factory()->superAdmin()->create([
            'email' => 'admin@saas.local',
            'password' => Hash::make('yoneticiparola123'),
        ]);

        for ($i = 0; $i < 5; $i++) {
            $this->post('http://saas.local/yonetim/giris', [
                'email' => 'admin@saas.local',
                'password' => 'yanlis'.$i,
            ]);
        }

        $this->post('http://saas.local/yonetim/giris', [
            'email' => 'admin@saas.local',
            'password' => 'yoneticiparola123',
        ])->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_parola_sifirlama_talebi_sinirlanir(): void
    {
        for ($i = 0; $i < 5; $i++) {
            $this->post('http://acme.saas.local/parola/unuttum', ['email' => 'kurban@ornek.com'])
                ->assertStatus(302);
        }

        $this->post('http://acme.saas.local/parola/unuttum', ['email' => 'kurban@ornek.com'])
            ->assertStatus(429);
    }

    public function test_adres_hatirlatma_sinirlanir(): void
    {
        for ($i = 0; $i < 5; $i++) {
            $this->post('http://saas.local/adresimi-bul', ['email' => "deneme{$i}@ornek.com"])
                ->assertStatus(302);
        }

        $this->post('http://saas.local/adresimi-bul', ['email' => 'son@ornek.com'])
            ->assertStatus(429);
    }
}
