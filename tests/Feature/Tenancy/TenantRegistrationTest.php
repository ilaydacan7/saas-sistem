<?php

declare(strict_types=1);

namespace Tests\Feature\Tenancy;

use App\Models\Tenant;
use App\Models\User;
use App\Tenancy\TenantStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class TenantRegistrationTest extends TestCase
{
    use RefreshDatabase;

    private const MERKEZ = 'http://saas.local';

    private function gecerliVeri(array $degisiklik = []): array
    {
        return array_merge([
            'company' => 'Acme A.Ş.',
            'slug' => 'acme',
            'name' => 'İlayda Can',
            'email' => 'ilayda@acme.com',
            'password' => 'gizli12345',
            'password_confirmation' => 'gizli12345',
        ], $degisiklik);
    }

    public function test_kayit_formu_merkezi_hostta_acilir(): void
    {
        $this->get(self::MERKEZ.'/kayit')
            ->assertOk()
            ->assertSee('Şirketinizi kaydedin');
    }

    public function test_kayit_formu_tenant_hostunda_acilmaz(): void
    {
        Tenant::factory()->create(['slug' => 'baska']);

        $this->get('http://baska.saas.local/kayit')->assertNotFound();
    }

    public function test_kayit_tenant_ve_ilk_yoneticiyi_olusturur(): void
    {
        $this->post(self::MERKEZ.'/kayit', $this->gecerliVeri())
            ->assertRedirect('http://acme.saas.local/giris?kayit=tamam');

        $tenant = Tenant::where('slug', 'acme')->firstOrFail();

        $this->assertSame('Acme A.Ş.', $tenant->name);
        $this->assertSame(TenantStatus::Trialing, $tenant->status);
        $this->assertTrue($tenant->trial_ends_at->isFuture());

        $owner = User::ofTenant($tenant)->firstOrFail();

        $this->assertSame('ilayda@acme.com', $owner->email);
        $this->assertTrue(Hash::check('gizli12345', $owner->password));
        $this->assertFalse($owner->isSuperAdmin());
    }

    public function test_deneme_suresi_yapilandirmadan_okunur(): void
    {
        config(['billing.trial_days' => 30]);

        $this->post(self::MERKEZ.'/kayit', $this->gecerliVeri());

        $this->assertEqualsWithDelta(
            30,
            now()->diffInDays(Tenant::where('slug', 'acme')->firstOrFail()->trial_ends_at),
            0.01
        );
    }

    /**
     * @dataProvider gecersizSluglar
     */
    public function test_gecersiz_slug_reddedilir(string $slug): void
    {
        $this->post(self::MERKEZ.'/kayit', $this->gecerliVeri(['slug' => $slug]))
            ->assertSessionHasErrors('slug');

        $this->assertSame(0, Tenant::count());
    }

    public static function gecersizSluglar(): array
    {
        return [
            'rezerve kelime' => ['billing'],
            'rezerve subdomain' => ['www'],
            'cok kisa' => ['ab'],
            'harf ve rakam yok' => ['!!! ---'],
        ];
    }

    /**
     * Kullanıcı adresi Türkçe karakterle, boşlukla veya noktalamayla yazabilir.
     * Bunları reddetmek yerine geçerli bir adrese çeviriyoruz.
     *
     * @dataProvider duzeltilenSluglar
     */
    public function test_gecersiz_yazim_adrese_cevrilir(string $girdi, string $beklenen): void
    {
        $this->post(self::MERKEZ.'/kayit', $this->gecerliVeri(['slug' => $girdi]))
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('tenants', ['slug' => $beklenen]);
    }

    public static function duzeltilenSluglar(): array
    {
        return [
            'turkce karakter' => ['lefkoşa_gönyeli', 'lefkosa-gonyeli'],
            'bosluk' => ['Acme Ltd', 'acme-ltd'],
            'tire ile baslar' => ['-acme', 'acme'],
            'tire ile biter' => ['acme-', 'acme'],
            'nokta' => ['a.b.firma', 'abfirma'],
            'alt cizgi' => ['yildiz_hirdavat', 'yildiz-hirdavat'],
        ];
    }

    public function test_adres_bos_birakilirsa_sirket_adindan_uretilir(): void
    {
        $this->post(self::MERKEZ.'/kayit', $this->gecerliVeri([
            'company' => 'Yıldız Hırdavat',
            'slug' => '',
        ]))->assertSessionHasNoErrors();

        $this->assertDatabaseHas('tenants', ['slug' => 'yildiz-hirdavat']);
    }

    public function test_kullanilan_slug_reddedilir(): void
    {
        Tenant::factory()->create(['slug' => 'acme']);

        $this->post(self::MERKEZ.'/kayit', $this->gecerliVeri())
            ->assertSessionHasErrors('slug');

        $this->assertSame(1, Tenant::count());
    }

    public function test_slug_kucuk_harfe_cevrilir(): void
    {
        $this->post(self::MERKEZ.'/kayit', $this->gecerliVeri(['slug' => '  ACME  ', 'email' => 'BUYUK@acme.com']));

        $this->assertDatabaseHas('tenants', ['slug' => 'acme']);
        $this->assertDatabaseHas('users', ['email' => 'buyuk@acme.com']);
    }

    public function test_zayif_parola_reddedilir(): void
    {
        $this->post(self::MERKEZ.'/kayit', $this->gecerliVeri([
            'password' => 'kisa',
            'password_confirmation' => 'kisa',
        ]))->assertSessionHasErrors('password');

        $this->assertSame(0, Tenant::count());
    }

    public function test_parola_tekrari_uyusmazsa_reddedilir(): void
    {
        $this->post(self::MERKEZ.'/kayit', $this->gecerliVeri([
            'password_confirmation' => 'baskabir12345',
        ]))->assertSessionHasErrors('password');

        $this->assertSame(0, Tenant::count());
    }

    public function test_ayni_eposta_farkli_tenantlarda_kullanilabilir(): void
    {
        $this->post(self::MERKEZ.'/kayit', $this->gecerliVeri());
        $this->post(self::MERKEZ.'/kayit', $this->gecerliVeri([
            'company' => 'Beta Ltd',
            'slug' => 'beta',
        ]))->assertSessionHasNoErrors();

        $this->assertSame(2, User::whereNotNull('tenant_id')->where('email', 'ilayda@acme.com')->count());
    }

    public function test_dogrulama_hatasinda_hicbir_kayit_olusmaz(): void
    {
        $this->post(self::MERKEZ.'/kayit', $this->gecerliVeri(['email' => 'gecersiz']))
            ->assertSessionHasErrors('email');

        $this->assertSame(0, Tenant::count());
        $this->assertSame(0, User::count());
    }
}
