<?php

declare(strict_types=1);

namespace Tests\Feature\Tenancy;

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Tests\TestCase;

class ParolaSifirlamaTest extends TestCase
{
    use RefreshDatabase;

    private Tenant $acme;

    private Tenant $beta;

    private User $acmeKullanici;

    private User $betaKullanici;

    protected function setUp(): void
    {
        parent::setUp();

        $this->acme = Tenant::factory()->create(['slug' => 'acme']);
        $this->beta = Tenant::factory()->create(['slug' => 'beta']);

        $this->acmeKullanici = User::factory()->forTenant($this->acme)->create([
            'email' => 'ortak@ornek.com',
            'password' => Hash::make('eskiparola123'),
        ]);

        $this->betaKullanici = User::factory()->forTenant($this->beta)->create([
            'email' => 'ortak@ornek.com',
            'password' => Hash::make('eskiparola123'),
        ]);
    }

    public function test_sifirlama_baglantisi_gonderilir(): void
    {
        Notification::fake();

        $this->post('http://acme.saas.local/parola/unuttum', ['email' => 'ortak@ornek.com'])
            ->assertSessionHas('durum');

        Notification::assertSentTo($this->acmeKullanici, ResetPassword::class);
        Notification::assertNotSentTo($this->betaKullanici, ResetPassword::class);
    }

    public function test_kayitsiz_eposta_ayni_mesaji_dondurur(): void
    {
        Notification::fake();

        $this->post('http://acme.saas.local/parola/unuttum', ['email' => 'yok@ornek.com'])
            ->assertSessionHas('durum')
            ->assertSessionHasNoErrors();

        Notification::assertNothingSent();
    }

    public function test_parola_sifirlanir(): void
    {
        $token = Password::broker()->createToken($this->acmeKullanici);

        $this->post('http://acme.saas.local/parola/sifirla', [
            'token' => $token,
            'email' => 'ortak@ornek.com',
            'password' => 'yeniparola123',
            'password_confirmation' => 'yeniparola123',
        ])->assertRedirect('http://acme.saas.local/giris');

        $this->assertTrue(Hash::check('yeniparola123', $this->acmeKullanici->fresh()->password));
    }

    public function test_baska_kiracinin_tokeni_kullanilamaz(): void
    {
        $betaToken = Password::broker()->createToken($this->betaKullanici);

        $this->post('http://acme.saas.local/parola/sifirla', [
            'token' => $betaToken,
            'email' => 'ortak@ornek.com',
            'password' => 'saldirgan12345',
            'password_confirmation' => 'saldirgan12345',
        ])->assertSessionHasErrors('email');

        $this->assertTrue(Hash::check('eskiparola123', $this->acmeKullanici->fresh()->password));
    }

    public function test_iki_kiracinin_tokeni_birbirini_gecersiz_kilmaz(): void
    {
        $acmeToken = Password::broker()->createToken($this->acmeKullanici);
        $betaToken = Password::broker()->createToken($this->betaKullanici);

        $this->post('http://acme.saas.local/parola/sifirla', [
            'token' => $acmeToken,
            'email' => 'ortak@ornek.com',
            'password' => 'acmeparola123',
            'password_confirmation' => 'acmeparola123',
        ])->assertSessionHasNoErrors();

        $this->post('http://beta.saas.local/parola/sifirla', [
            'token' => $betaToken,
            'email' => 'ortak@ornek.com',
            'password' => 'betaparola123',
            'password_confirmation' => 'betaparola123',
        ])->assertSessionHasNoErrors();

        $this->assertTrue(Hash::check('acmeparola123', $this->acmeKullanici->fresh()->password));
        $this->assertTrue(Hash::check('betaparola123', $this->betaKullanici->fresh()->password));
    }

    public function test_suresi_dolmus_token_reddedilir(): void
    {
        $token = Password::broker()->createToken($this->acmeKullanici);

        $this->travel(61)->minutes();

        $this->post('http://acme.saas.local/parola/sifirla', [
            'token' => $token,
            'email' => 'ortak@ornek.com',
            'password' => 'yeniparola123',
            'password_confirmation' => 'yeniparola123',
        ])->assertSessionHasErrors('email');

        $this->assertTrue(Hash::check('eskiparola123', $this->acmeKullanici->fresh()->password));
    }

    public function test_token_tek_kullanimlik(): void
    {
        $token = Password::broker()->createToken($this->acmeKullanici);

        $veri = [
            'token' => $token,
            'email' => 'ortak@ornek.com',
            'password' => 'yeniparola123',
            'password_confirmation' => 'yeniparola123',
        ];

        $this->post('http://acme.saas.local/parola/sifirla', $veri)->assertSessionHasNoErrors();
        $this->post('http://acme.saas.local/parola/sifirla', $veri)->assertSessionHasErrors('email');
    }

    public function test_zayif_parola_reddedilir(): void
    {
        $token = Password::broker()->createToken($this->acmeKullanici);

        $this->post('http://acme.saas.local/parola/sifirla', [
            'token' => $token,
            'email' => 'ortak@ornek.com',
            'password' => 'kisa',
            'password_confirmation' => 'kisa',
        ])->assertSessionHasErrors('password');

        $this->assertTrue(Hash::check('eskiparola123', $this->acmeKullanici->fresh()->password));
    }

    public function test_sifirlama_sayfalari_merkezi_hostta_acilmaz(): void
    {
        $this->get('http://saas.local/parola/unuttum')->assertNotFound();
        $this->get('http://saas.local/parola/sifirla/abc')->assertNotFound();
    }

    public function test_baglanti_dogru_kiraci_adresini_icerir(): void
    {
        Notification::fake();

        $this->post('http://acme.saas.local/parola/unuttum', ['email' => 'ortak@ornek.com']);

        Notification::assertSentTo($this->acmeKullanici, ResetPassword::class, function ($notification) {
            $mail = $notification->toMail($this->acmeKullanici);
            $adres = $mail->actionUrl;

            return str_contains($adres, 'acme.saas.local')
                && str_contains($adres, '/parola/sifirla/')
                && $mail->subject === 'Parola sıfırlama';
        });
    }
}
