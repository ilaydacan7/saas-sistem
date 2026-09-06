<?php

declare(strict_types=1);

namespace Tests\Feature\Ekip;

use App\Models\Invitation;
use App\Models\Tenant;
use App\Models\User;
use App\Notifications\TeamInvitation;
use App\Tenancy\TenantRole;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class EkipYonetimiTest extends TestCase
{
    use RefreshDatabase;

    private const HOST = 'http://acme.saas.local';

    private Tenant $tenant;

    private User $sahip;

    private User $yonetici;

    private User $uye;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tenant = Tenant::factory()->create(['slug' => 'acme']);
        $this->sahip = User::factory()->forTenant($this->tenant)->owner()->create(['name' => 'Sahip Kisi']);
        $this->yonetici = User::factory()->forTenant($this->tenant)->role(TenantRole::Admin)->create(['name' => 'Yonetici Kisi']);
        $this->uye = User::factory()->forTenant($this->tenant)->role(TenantRole::Member)->create(['name' => 'Uye Kisi']);
    }

    public function test_uye_ekip_sayfasini_goremez(): void
    {
        $this->actingAs($this->uye)->get(self::HOST.'/ekip')->assertForbidden();
    }

    public function test_yonetici_ekip_sayfasini_gorur(): void
    {
        $this->actingAs($this->yonetici)->get(self::HOST.'/ekip')
            ->assertOk()
            ->assertSee('Sahip Kisi')
            ->assertSee('Uye Kisi');
    }

    public function test_davet_gonderilir(): void
    {
        Notification::fake();

        $this->actingAs($this->sahip)
            ->post(self::HOST.'/ekip/davet', ['email' => 'yeni@ornek.com', 'role' => 'member'])
            ->assertSessionHasNoErrors();

        $davet = Invitation::forTenant($this->tenant)->where('email', 'yeni@ornek.com')->firstOrFail();

        $this->assertSame(TenantRole::Member, $davet->role);
        $this->assertSame($this->sahip->getKey(), $davet->invited_by_id);
        $this->assertTrue($davet->isPending());

        Notification::assertSentTo($davet, TeamInvitation::class);
    }

    public function test_yonetici_sahip_rolu_ile_davet_edemez(): void
    {
        $this->actingAs($this->yonetici)
            ->post(self::HOST.'/ekip/davet', ['email' => 'yeni@ornek.com', 'role' => 'owner'])
            ->assertSessionHasErrors('role');

        $this->assertSame(0, Invitation::forTenant($this->tenant)->count());
    }

    public function test_sahip_sahip_rolu_ile_davet_edebilir(): void
    {
        Notification::fake();

        $this->actingAs($this->sahip)
            ->post(self::HOST.'/ekip/davet', ['email' => 'yeni@ornek.com', 'role' => 'owner'])
            ->assertSessionHasNoErrors();

        $this->assertSame(
            TenantRole::Owner,
            Invitation::forTenant($this->tenant)->where('email', 'yeni@ornek.com')->firstOrFail()->role
        );
    }

    public function test_mevcut_uyeye_davet_gonderilemez(): void
    {
        $this->actingAs($this->sahip)
            ->post(self::HOST.'/ekip/davet', ['email' => $this->uye->email, 'role' => 'member'])
            ->assertSessionHasErrors('email');
    }

    public function test_ayni_epostaya_iki_kez_davet_gonderilemez(): void
    {
        Notification::fake();

        $this->actingAs($this->sahip)->post(self::HOST.'/ekip/davet', ['email' => 'yeni@ornek.com', 'role' => 'member']);

        $this->actingAs($this->sahip)
            ->post(self::HOST.'/ekip/davet', ['email' => 'yeni@ornek.com', 'role' => 'member'])
            ->assertSessionHasErrors('email');

        $this->assertSame(1, Invitation::forTenant($this->tenant)->count());
    }

    public function test_ayni_eposta_farkli_kiracilarda_davet_edilebilir(): void
    {
        Notification::fake();

        $beta = Tenant::factory()->create(['slug' => 'beta']);
        $betaSahip = User::factory()->forTenant($beta)->owner()->create();

        $this->actingAs($this->sahip)->post(self::HOST.'/ekip/davet', ['email' => 'ortak@ornek.com', 'role' => 'member']);

        $this->actingAs($betaSahip)
            ->post('http://beta.saas.local/ekip/davet', ['email' => 'ortak@ornek.com', 'role' => 'member'])
            ->assertSessionHasNoErrors();

        $this->assertSame(1, Invitation::forTenant($this->tenant)->count());
        $this->assertSame(1, Invitation::forTenant($beta)->count());
    }

    public function test_davet_iptal_edilir(): void
    {
        $davet = Invitation::factory()->create(['tenant_id' => $this->tenant->getKey()]);

        $this->actingAs($this->sahip)
            ->delete(self::HOST.'/ekip/davet/'.$davet->getKey())
            ->assertSessionHasNoErrors();

        $this->assertSame(0, Invitation::forTenant($this->tenant)->count());
    }

    public function test_baska_kiracinin_daveti_iptal_edilemez(): void
    {
        $beta = Tenant::factory()->create(['slug' => 'beta']);
        $davet = Invitation::factory()->create(['tenant_id' => $beta->getKey()]);

        $this->actingAs($this->sahip)
            ->delete(self::HOST.'/ekip/davet/'.$davet->getKey())
            ->assertNotFound();

        $this->assertSame(1, Invitation::forTenant($beta)->count());
    }

    public function test_rol_degistirilir(): void
    {
        $this->actingAs($this->sahip)
            ->patch(self::HOST.'/ekip/'.$this->uye->getKey().'/rol', ['role' => 'admin'])
            ->assertSessionHasNoErrors();

        $this->assertSame(TenantRole::Admin, $this->uye->fresh()->role);
    }

    public function test_yonetici_sahibin_rolunu_degistiremez(): void
    {
        $this->actingAs($this->yonetici)
            ->patch(self::HOST.'/ekip/'.$this->sahip->getKey().'/rol', ['role' => 'member'])
            ->assertForbidden();

        $this->assertSame(TenantRole::Owner, $this->sahip->fresh()->role);
    }

    public function test_kisi_kendi_rolunu_degistiremez(): void
    {
        $this->actingAs($this->sahip)
            ->patch(self::HOST.'/ekip/'.$this->sahip->getKey().'/rol', ['role' => 'member'])
            ->assertSessionHasErrors('role');

        $this->assertSame(TenantRole::Owner, $this->sahip->fresh()->role);
    }

    public function test_son_sahip_rolunu_kaybedemez(): void
    {
        $ikinciSahip = User::factory()->forTenant($this->tenant)->owner()->create();

        $this->actingAs($ikinciSahip)
            ->patch(self::HOST.'/ekip/'.$this->sahip->getKey().'/rol', ['role' => 'member'])
            ->assertSessionHasNoErrors();

        $this->assertSame(TenantRole::Member, $this->sahip->fresh()->role);

        $this->actingAs($ikinciSahip)
            ->patch(self::HOST.'/ekip/'.$ikinciSahip->getKey().'/rol', ['role' => 'member'])
            ->assertSessionHasErrors('role');
    }

    public function test_uye_ekipten_cikarilir(): void
    {
        $this->actingAs($this->sahip)
            ->delete(self::HOST.'/ekip/'.$this->uye->getKey())
            ->assertSessionHasNoErrors();

        $this->assertDatabaseMissing('users', ['id' => $this->uye->getKey()]);
    }

    public function test_kisi_kendini_cikaramaz(): void
    {
        $this->actingAs($this->sahip)
            ->delete(self::HOST.'/ekip/'.$this->sahip->getKey())
            ->assertSessionHasErrors('uye');

        $this->assertDatabaseHas('users', ['id' => $this->sahip->getKey()]);
    }

    public function test_yonetici_sahibi_cikaramaz(): void
    {
        $this->actingAs($this->yonetici)
            ->delete(self::HOST.'/ekip/'.$this->sahip->getKey())
            ->assertForbidden();

        $this->assertDatabaseHas('users', ['id' => $this->sahip->getKey()]);
    }

    public function test_baska_kiracinin_kullanicisi_cikarilamaz(): void
    {
        $beta = Tenant::factory()->create(['slug' => 'beta']);
        $betaUye = User::factory()->forTenant($beta)->create();

        $this->actingAs($this->sahip)
            ->delete(self::HOST.'/ekip/'.$betaUye->getKey())
            ->assertNotFound();

        $this->assertDatabaseHas('users', ['id' => $betaUye->getKey()]);
    }

    public function test_kayit_olan_ilk_kullanici_sahiptir(): void
    {
        $this->post('http://saas.local/kayit', [
            'company' => 'Yeni Firma',
            'slug' => 'yenifirma',
            'name' => 'Kurucu',
            'email' => 'kurucu@yenifirma.com',
            'password' => 'gizli12345',
            'password_confirmation' => 'gizli12345',
        ]);

        $tenant = Tenant::where('slug', 'yenifirma')->firstOrFail();

        $this->assertSame(TenantRole::Owner, User::ofTenant($tenant)->firstOrFail()->role);
    }
}
