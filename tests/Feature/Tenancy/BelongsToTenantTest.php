<?php

declare(strict_types=1);

namespace Tests\Feature\Tenancy;

use App\Models\Tenant;
use App\Tenancy\Concerns\BelongsToTenant;
use App\Tenancy\Exceptions\TenantContextMissingException;
use App\Tenancy\TenantContext;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class TenantScopedProject extends Model
{
    use BelongsToTenant;

    protected $table = 'test_projects';

    protected $fillable = ['name', 'tenant_id'];
}

class BelongsToTenantTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('test_projects', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->timestamps();
        });
    }

    private function context(): TenantContext
    {
        return app(TenantContext::class);
    }

    public function test_sorgular_aktif_tenant_ile_kapsanir(): void
    {
        [$a, $b] = [Tenant::factory()->create(), Tenant::factory()->create()];

        $this->context()->runFor($a, fn () => TenantScopedProject::create(['name' => 'A projesi']));
        $this->context()->runFor($b, fn () => TenantScopedProject::create(['name' => 'B projesi']));

        $gorulen = $this->context()->runFor($a, fn () => TenantScopedProject::pluck('name')->all());

        $this->assertSame(['A projesi'], $gorulen);
    }

    public function test_yeni_kayit_aktif_tenant_a_yazilir(): void
    {
        $tenant = Tenant::factory()->create();

        $proje = $this->context()->runFor($tenant, fn () => TenantScopedProject::create(['name' => 'X']));

        $this->assertSame($tenant->getKey(), $proje->tenant_id);
    }

    public function test_baglam_yokken_sorgu_istisna_firlatir(): void
    {
        $this->expectException(TenantContextMissingException::class);

        TenantScopedProject::count();
    }

    public function test_baglam_yokken_kayit_istisna_firlatir(): void
    {
        $this->expectException(TenantContextMissingException::class);

        $this->context()->runWithoutTenant(fn () => TenantScopedProject::create(['name' => 'sahipsiz']));
    }

    public function test_run_without_tenant_tum_tenantlari_gorur(): void
    {
        [$a, $b] = [Tenant::factory()->create(), Tenant::factory()->create()];

        $this->context()->runFor($a, fn () => TenantScopedProject::create(['name' => 'A']));
        $this->context()->runFor($b, fn () => TenantScopedProject::create(['name' => 'B']));

        $this->assertSame(2, $this->context()->runWithoutTenant(fn () => TenantScopedProject::count()));
    }

    public function test_run_without_tenant_onceki_baglami_geri_yukler(): void
    {
        $tenant = Tenant::factory()->create();

        $this->context()->set($tenant);
        $this->context()->runWithoutTenant(fn () => null);

        $this->assertTrue($this->context()->has());
        $this->assertSame($tenant->getKey(), $this->context()->id());
    }

    public function test_for_tenant_kapsami_baska_tenanti_sorgular(): void
    {
        [$a, $b] = [Tenant::factory()->create(), Tenant::factory()->create()];

        $this->context()->runFor($b, fn () => TenantScopedProject::create(['name' => 'B']));

        $sonuc = $this->context()->runFor($a, fn () => TenantScopedProject::forTenant($b)->pluck('name')->all());

        $this->assertSame(['B'], $sonuc);
    }
}
