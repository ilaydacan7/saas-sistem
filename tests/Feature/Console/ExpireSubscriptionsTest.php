<?php

declare(strict_types=1);

namespace Tests\Feature\Console;

use App\Models\Tenant;
use App\Tenancy\TenantStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExpireSubscriptionsTest extends TestCase
{
    use RefreshDatabase;

    public function test_donemi_biten_abonelik_odeme_bekliyora_alinir(): void
    {
        $tenant = Tenant::factory()->create([
            'status' => TenantStatus::Active,
            'paid_until' => now()->subDay(),
        ]);

        $this->artisan('tenants:expire-subscriptions')
            ->expectsOutputToContain('1 abonelik')
            ->assertSuccessful();

        $this->assertSame(TenantStatus::PastDue, $tenant->fresh()->status);
    }

    public function test_suren_abonelik_etkilenmez(): void
    {
        $tenant = Tenant::factory()->create([
            'status' => TenantStatus::Active,
            'paid_until' => now()->addMonth(),
        ]);

        $this->artisan('tenants:expire-subscriptions')->assertSuccessful();

        $this->assertSame(TenantStatus::Active, $tenant->fresh()->status);
    }

    public function test_askidaki_kiraci_etkilenmez(): void
    {
        $tenant = Tenant::factory()->suspended()->create(['paid_until' => now()->subDay()]);

        $this->artisan('tenants:expire-subscriptions')->assertSuccessful();

        $this->assertSame(TenantStatus::Suspended, $tenant->fresh()->status);
    }

    public function test_zamanlamada_kayitli(): void
    {
        $this->artisan('schedule:list')
            ->expectsOutputToContain('tenants:expire-subscriptions')
            ->assertSuccessful();
    }
}
