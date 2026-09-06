<?php

declare(strict_types=1);

namespace Tests\Feature\Moduller;

use App\Models\Tenant;
use App\Models\User;
use App\Modules\Module;
use App\Tenancy\TenantRole;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ModulAyarlariTest extends TestCase
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

    public function test_ayarlar_ekrani_modulleri_listeler(): void
    {
        $this->actingAs($this->sahip)->get(self::HOST.'/ayarlar/moduller')
            ->assertOk()
            ->assertSee('Müşteriler')
            ->assertSee('Randevular')
            ->assertSee('Yakında');
    }

    public function test_uye_modul_ayarlarina_erisemez(): void
    {
        $uye = User::factory()->forTenant($this->tenant)->role(TenantRole::Member)->create();

        $this->actingAs($uye)->get(self::HOST.'/ayarlar/moduller')->assertForbidden();
    }

    public function test_temel_modul_kapatilamaz(): void
    {
        $this->actingAs($this->sahip)
            ->patch(self::HOST.'/ayarlar/moduller', ['module' => 'musteriler', 'acik' => 0])
            ->assertSessionHasErrors('module');

        $this->assertTrue($this->tenant->fresh()->hasModule(Module::Customers));
    }

    public function test_hazir_olmayan_modul_acilamaz(): void
    {
        $this->actingAs($this->sahip)
            ->patch(self::HOST.'/ayarlar/moduller', ['module' => 'randevu', 'acik' => 1])
            ->assertSessionHasErrors('module');

        $this->assertFalse($this->tenant->fresh()->hasModule(Module::Appointments));
    }

    public function test_bilinmeyen_modul_reddedilir(): void
    {
        $this->actingAs($this->sahip)
            ->patch(self::HOST.'/ayarlar/moduller', ['module' => 'olmayan', 'acik' => 1])
            ->assertSessionHasErrors('module');
    }

    public function test_kayit_olan_kiracida_varsayilan_moduller_acilir(): void
    {
        $this->post('http://saas.local/kayit', [
            'company' => 'Yeni Firma',
            'slug' => 'yenifirma',
            'name' => 'Kurucu',
            'email' => 'kurucu@yenifirma.com',
            'password' => 'gizli12345',
            'password_confirmation' => 'gizli12345',
        ]);

        $yeni = Tenant::where('slug', 'yenifirma')->firstOrFail();

        foreach (Module::defaults() as $module) {
            $this->assertTrue($yeni->hasModule($module), $module->value.' acik olmali');
        }
    }

    public function test_panelde_yalnizca_acik_moduller_gorunur(): void
    {
        $this->actingAs($this->sahip)->get(self::HOST.'/panel')
            ->assertOk()
            ->assertSee('Müşteriler')
            ->assertDontSee('Gelir ve Gider');
    }

    public function test_modul_kapaliyken_menude_gorunmez(): void
    {
        $beta = Tenant::factory()->create(['slug' => 'beta']);
        $betaSahip = User::factory()->forTenant($beta)->owner()->create();

        $this->actingAs($betaSahip)->get('http://beta.saas.local/panel')
            ->assertOk()
            ->assertDontSee('href="http://beta.saas.local/musteriler"', false);
    }
}
