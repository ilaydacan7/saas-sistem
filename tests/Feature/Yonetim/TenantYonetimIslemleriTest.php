<?php

declare(strict_types=1);

namespace Tests\Feature\Yonetim;

use App\Models\Payment;
use App\Models\Tenant;
use App\Models\User;
use App\Tenancy\TenantStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TenantYonetimIslemleriTest extends TestCase
{
    use RefreshDatabase;

    private const MERKEZ = 'http://saas.local';

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->superAdmin()->create();
    }

    private function yol(Tenant $tenant, string $islem = ''): string
    {
        return self::MERKEZ.'/yonetim/'.$tenant->slug.($islem === '' ? '' : '/'.$islem);
    }

    public function test_odeme_kaydedilir_ve_kiraci_aktiflesir(): void
    {
        $tenant = Tenant::factory()->pastDue()->create();

        $this->actingAs($this->admin)
            ->post($this->yol($tenant, 'odeme'), ['tutar' => '499,00', 'ay' => 1, 'not' => 'Havale #123'])
            ->assertRedirect();

        $tenant->refresh();

        $this->assertSame(TenantStatus::Active, $tenant->status);
        $this->assertTrue($tenant->paid_until->isFuture());

        $payment = Payment::forTenant($tenant)->firstOrFail();

        $this->assertSame(49900, $payment->amount_minor);
        $this->assertSame('TRY', $payment->currency);
        $this->assertSame('Havale #123', $payment->note);
        $this->assertSame($this->admin->getKey(), $payment->recorded_by_id);
    }

    public function test_ust_uste_odeme_donemi_uzatir(): void
    {
        $tenant = Tenant::factory()->create(['paid_until' => now()->addDays(10)]);
        $ilkBitis = $tenant->paid_until;

        $this->actingAs($this->admin)->post($this->yol($tenant, 'odeme'), ['tutar' => 499, 'ay' => 1]);

        $this->assertEqualsWithDelta(
            $ilkBitis->copy()->addMonth()->timestamp,
            $tenant->fresh()->paid_until->timestamp,
            2
        );
    }

    public function test_gecersiz_tutar_reddedilir(): void
    {
        $tenant = Tenant::factory()->pastDue()->create();

        $this->actingAs($this->admin)
            ->post($this->yol($tenant, 'odeme'), ['tutar' => 0, 'ay' => 1])
            ->assertSessionHasErrors('tutar');

        $this->assertSame(0, Payment::forTenant($tenant)->count());
        $this->assertSame(TenantStatus::PastDue, $tenant->fresh()->status);
    }

    public function test_askiya_alma_erisimi_kapatir(): void
    {
        $tenant = Tenant::factory()->create(['slug' => 'acme']);

        $this->actingAs($this->admin)->post($this->yol($tenant, 'askiya-al'))->assertRedirect();

        $tenant->refresh();
        $this->assertSame(TenantStatus::Suspended, $tenant->status);
        $this->assertNotNull($tenant->suspended_at);

        $this->get('http://acme.saas.local/giris')->assertForbidden();
    }

    public function test_askidan_cikarma_odenmis_doneme_gore_durum_secer(): void
    {
        $tenant = Tenant::factory()->suspended()->create(['paid_until' => now()->addMonth()]);

        $this->actingAs($this->admin)->post($this->yol($tenant, 'aktiflestir'));

        $tenant->refresh();
        $this->assertSame(TenantStatus::Active, $tenant->status);
        $this->assertNull($tenant->suspended_at);
    }

    public function test_odemesi_olmayan_kiraci_askidan_cikinca_odeme_bekler(): void
    {
        $tenant = Tenant::factory()->suspended()->create(['paid_until' => null, 'trial_ends_at' => null]);

        $this->actingAs($this->admin)->post($this->yol($tenant, 'aktiflestir'));

        $this->assertSame(TenantStatus::PastDue, $tenant->fresh()->status);
    }

    public function test_askidaki_kiraci_odeme_ile_kendiliginden_acilmaz(): void
    {
        $tenant = Tenant::factory()->suspended()->create();

        $this->actingAs($this->admin)->post($this->yol($tenant, 'odeme'), ['tutar' => 499, 'ay' => 1]);

        $tenant->refresh();
        $this->assertSame(TenantStatus::Suspended, $tenant->status);
        $this->assertTrue($tenant->paid_until->isFuture());
    }

    public function test_deneme_suresi_uzatilir(): void
    {
        $tenant = Tenant::factory()->pastDue()->create(['trial_ends_at' => now()->subDay()]);

        $this->actingAs($this->admin)->post($this->yol($tenant, 'deneme-uzat'), ['gun' => 7]);

        $tenant->refresh();
        $this->assertSame(TenantStatus::Trialing, $tenant->status);
        $this->assertEqualsWithDelta(7, now()->diffInDays($tenant->trial_ends_at), 0.01);
    }

    public function test_detay_ekrani_odeme_gecmisini_gosterir(): void
    {
        $tenant = Tenant::factory()->create();
        Payment::factory()->create(['tenant_id' => $tenant->getKey(), 'note' => 'Ilk odeme']);

        $this->actingAs($this->admin)->get($this->yol($tenant))
            ->assertOk()
            ->assertSee($tenant->name)
            ->assertSee('Ilk odeme');
    }

    public function test_tutar_hem_virgul_hem_nokta_ile_yazilabilir(): void
    {
        foreach ([
            '1.234,50' => 123450,
            '499,00' => 49900,
            '499.00' => 49900,
            '499' => 49900,
        ] as $girdi => $beklenen) {
            $tenant = Tenant::factory()->create();

            $this->actingAs($this->admin)
                ->post($this->yol($tenant, 'odeme'), ['tutar' => $girdi, 'ay' => 1])
                ->assertSessionHasNoErrors();

            $this->assertSame(
                $beklenen,
                Payment::forTenant($tenant)->firstOrFail()->amount_minor,
                "Girdi: {$girdi}"
            );
        }
    }
}
