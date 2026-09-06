<?php

declare(strict_types=1);

namespace Tests\Feature\Yonetim;

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class SuperAdminAccessTest extends TestCase
{
    use RefreshDatabase;

    private const MERKEZ = 'http://saas.local';

    public function test_yonetim_girisi_merkezde_acilir(): void
    {
        $this->get(self::MERKEZ.'/yonetim/giris')->assertOk()->assertSee('Yönetim');
    }

    public function test_yonetim_girisi_tenant_hostunda_acilmaz(): void
    {
        Tenant::factory()->create(['slug' => 'acme']);

        $this->get('http://acme.saas.local/yonetim/giris')->assertNotFound();
    }

    public function test_superadmin_giris_yapabilir(): void
    {
        $admin = User::factory()->superAdmin()->create([
            'email' => 'admin@saas.local',
            'password' => Hash::make('gizli12345'),
        ]);

        $this->post(self::MERKEZ.'/yonetim/giris', [
            'email' => 'admin@saas.local',
            'password' => 'gizli12345',
        ])->assertRedirect(self::MERKEZ.'/yonetim');

        $this->assertAuthenticatedAs($admin);
    }

    public function test_kiraci_kullanicisi_yonetim_hesabi_olarak_giris_yapamaz(): void
    {
        $tenant = Tenant::factory()->create();

        User::factory()->forTenant($tenant)->create([
            'email' => 'kiraci@ornek.com',
            'password' => Hash::make('gizli12345'),
        ]);

        $this->post(self::MERKEZ.'/yonetim/giris', [
            'email' => 'kiraci@ornek.com',
            'password' => 'gizli12345',
        ])->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_bayragi_olmayan_merkezi_kullanici_giris_yapamaz(): void
    {
        User::factory()->create([
            'tenant_id' => null,
            'is_super_admin' => false,
            'email' => 'sahte@saas.local',
            'password' => Hash::make('gizli12345'),
        ]);

        $this->post(self::MERKEZ.'/yonetim/giris', [
            'email' => 'sahte@saas.local',
            'password' => 'gizli12345',
        ])->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_yonetim_paneli_giris_yapmadan_acilmaz(): void
    {
        $this->get(self::MERKEZ.'/yonetim')->assertRedirect(self::MERKEZ.'/yonetim/giris');
    }

    public function test_kiraci_kullanicisi_yonetim_panelini_goremez(): void
    {
        $tenant = Tenant::factory()->create();
        $kullanici = User::factory()->forTenant($tenant)->create();

        $this->actingAs($kullanici)->get(self::MERKEZ.'/yonetim')->assertNotFound();
        $this->actingAs($kullanici)->get(self::MERKEZ.'/yonetim/'.$tenant->slug)->assertNotFound();
    }

    public function test_kiraci_kullanicisi_yonetim_islemi_yapamaz(): void
    {
        $tenant = Tenant::factory()->create();
        $kullanici = User::factory()->forTenant($tenant)->create();

        $this->actingAs($kullanici)
            ->post(self::MERKEZ.'/yonetim/'.$tenant->slug.'/askiya-al')
            ->assertNotFound();

        $this->assertTrue($tenant->fresh()->canAccess());
    }

    public function test_superadmin_kiracilari_listeler(): void
    {
        $admin = User::factory()->superAdmin()->create();
        Tenant::factory()->create(['name' => 'Acme A.Ş.', 'slug' => 'acme']);
        Tenant::factory()->suspended()->create(['name' => 'Beta Ltd', 'slug' => 'beta']);

        $this->actingAs($admin)->get(self::MERKEZ.'/yonetim')
            ->assertOk()
            ->assertSee('Acme A.Ş.')
            ->assertSee('Beta Ltd')
            ->assertSee('Askıya alınmış');
    }

    public function test_liste_ada_gore_filtrelenir(): void
    {
        $admin = User::factory()->superAdmin()->create();
        Tenant::factory()->create(['name' => 'Acme A.Ş.', 'slug' => 'acme']);
        Tenant::factory()->create(['name' => 'Beta Ltd', 'slug' => 'beta']);

        $this->actingAs($admin)->get(self::MERKEZ.'/yonetim?q=acme')
            ->assertOk()
            ->assertSee('Acme A.Ş.')
            ->assertDontSee('Beta Ltd');
    }

    public function test_liste_duruma_gore_filtrelenir(): void
    {
        $admin = User::factory()->superAdmin()->create();
        Tenant::factory()->create(['name' => 'Aktif Firma', 'slug' => 'aktif']);
        Tenant::factory()->suspended()->create(['name' => 'Askidaki Firma', 'slug' => 'askida']);

        $this->actingAs($admin)->get(self::MERKEZ.'/yonetim?durum=suspended')
            ->assertOk()
            ->assertSee('Askidaki Firma')
            ->assertDontSee('Aktif Firma');
    }
}
