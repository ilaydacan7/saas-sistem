<?php

declare(strict_types=1);

namespace App\Models;

use App\Tenancy\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Unit extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'tenant_id',
        'name',
        'short_name',
        'allows_fraction',
    ];

    protected $attributes = [
        'allows_fraction' => false,
    ];

    protected function casts(): array
    {
        return [
            'allows_fraction' => 'boolean',
        ];
    }

    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }

    /**
     * Yeni bir işletme için varsayılan birim listesi.
     *
     * @return list<array{name: string, short_name: string, allows_fraction: bool}>
     */
    public static function defaults(): array
    {
        return [
            ['name' => 'Adet', 'short_name' => 'adet', 'allows_fraction' => false],
            ['name' => 'Kilogram', 'short_name' => 'kg', 'allows_fraction' => true],
            ['name' => 'Litre', 'short_name' => 'lt', 'allows_fraction' => true],
            ['name' => 'Metre', 'short_name' => 'm', 'allows_fraction' => true],
            ['name' => 'Paket', 'short_name' => 'paket', 'allows_fraction' => false],
            ['name' => 'Saat', 'short_name' => 'saat', 'allows_fraction' => true],
        ];
    }
}
