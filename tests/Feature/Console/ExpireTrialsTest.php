<?php

declare(strict_types=1);

namespace Tests\Feature\Console;

use App\Models\Tenant;
use App\Tenancy\TenantStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExpireTrialsTest extends TestCase
{
    use RefreshDatabase;

    public function test_suresi_dolan_deneme_odeme_bekliyora_alinir(): void
    {
        $dolmus = Tenant::factory()->create([
            'status' => TenantStatus::Trialing,
            'trial_ends_at' => now()->subDay(),
        ]);

        $this->artisan('tenants:expire-trials')
            ->expectsOutputToContain('1 deneme hesabı')
            ->assertSuccessful();

        $this->assertSame(TenantStatus::PastDue, $dolmus->fresh()->status);
    }

    public function test_devam_eden_deneme_etkilenmez(): void
    {
        $suruyor = Tenant::factory()->trialing()->create();

        $this->artisan('tenants:expire-trials')->assertSuccessful();

        $this->assertSame(TenantStatus::Trialing, $suruyor->fresh()->status);
    }

    public function test_aktif_kiraci_etkilenmez(): void
    {
        $aktif = Tenant::factory()->create([
            'status' => TenantStatus::Active,
            'trial_ends_at' => now()->subMonth(),
        ]);

        $this->artisan('tenants:expire-trials')->assertSuccessful();

        $this->assertSame(TenantStatus::Active, $aktif->fresh()->status);
    }

    public function test_suresi_dolan_kiraci_faturalandirmaya_kilitlenir(): void
    {
        Tenant::factory()->create([
            'slug' => 'suresidolmus',
            'status' => TenantStatus::Trialing,
            'trial_ends_at' => now()->subDay(),
        ]);

        $this->artisan('tenants:expire-trials');

        $this->get('http://suresidolmus.saas.local/giris')
            ->assertRedirect(route('billing.overdue'));
    }
}
