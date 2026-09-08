<?php

declare(strict_types=1);

namespace App\Models;

use App\Modules\TransactionType;
use App\Tenancy\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TransactionCategory extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'tenant_id',
        'type',
        'name',
        'is_system',
    ];

    protected $attributes = [
        'is_system' => false,
    ];

    protected function casts(): array
    {
        return [
            'type' => TransactionType::class,
            'is_system' => 'boolean',
        ];
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class, 'category_id');
    }

    public function scopeOfType(Builder $query, TransactionType $type): Builder
    {
        return $query->where('type', $type);
    }
}
