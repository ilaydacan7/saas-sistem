<?php

declare(strict_types=1);

use App\Modules\Module;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $simdi = now();

        foreach (DB::table('tenants')->pluck('id') as $tenantId) {
            foreach (Module::defaults() as $module) {
                DB::table('tenant_modules')->insertOrIgnore([
                    'tenant_id' => $tenantId,
                    'module' => $module->value,
                    'created_at' => $simdi,
                    'updated_at' => $simdi,
                ]);
            }
        }
    }

    public function down(): void
    {
        DB::table('tenant_modules')->truncate();
    }
};
