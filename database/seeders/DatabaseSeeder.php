<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Tenant;
use App\Models\User;
use App\Modules\Module;
use App\Tenancy\TenantRole;
use App\Tenancy\TenantStatus;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        User::factory()->superAdmin()->create([
            'name' => 'Sistem Yöneticisi',
            'email' => 'admin@saas.local',
            'password' => Hash::make('parola12345'),
        ]);

        // Farklı sektörlerden üç örnek işletme: aynı sistem, farklı ihtiyaçlar.
        $hirdavat = Tenant::factory()->create([
            'name' => 'Yıldız Hırdavat',
            'slug' => 'yildiz',
            'status' => TenantStatus::Active,
            'paid_until' => now()->addMonths(2),
        ]);

        $kuafor = Tenant::factory()->trialing()->create([
            'name' => 'Ece Kuaför',
            'slug' => 'ece',
        ]);

        $danismanlik = Tenant::factory()->pastDue()->create([
            'name' => 'Demir Danışmanlık',
            'slug' => 'demir',
            'paid_until' => now()->subWeek(),
        ]);

        foreach ([$hirdavat, $kuafor, $danismanlik] as $tenant) {
            foreach (Module::defaults() as $module) {
                $tenant->enableModule($module);
            }

            User::factory()->forTenant($tenant)->owner()->create([
                'name' => 'Şirket Sahibi',
                'email' => 'yonetici@ornek.com',
                'password' => Hash::make('parola12345'),
            ]);

            User::factory()->forTenant($tenant)->role(TenantRole::Member)->create([
                'name' => 'Ekip Üyesi',
                'email' => 'uye@ornek.com',
                'password' => Hash::make('parola12345'),
            ]);
        }

        $this->call(DemoInventorySeeder::class);
    }
}
