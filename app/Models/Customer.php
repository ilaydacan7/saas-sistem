<?php

declare(strict_types=1);

namespace App\Models;

use App\Modules\CustomerType;
use App\Tenancy\Concerns\BelongsToTenant;
use Database\Factories\CustomerFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Customer extends Model
{
    /** @use HasFactory<CustomerFactory> */
    use BelongsToTenant, HasFactory, SoftDeletes;

    protected $fillable = [
        'tenant_id',
        'type',
        'name',
        'company_name',
        'tax_office',
        'tax_number',
        'phone',
        'email',
        'city',
        'address',
        'note',
        'is_active',
        'created_by_id',
    ];

    protected $attributes = [
        'type' => 'individual',
        'is_active' => true,
    ];

    protected function casts(): array
    {
        return [
            'type' => CustomerType::class,
            'is_active' => 'boolean',
        ];
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_id');
    }

    /**
     * Listede gösterilecek ad: kurumsal kayıtlarda unvan öne çıkar.
     */
    public function displayName(): string
    {
        if ($this->type === CustomerType::Company && $this->company_name !== null && $this->company_name !== '') {
            return $this->company_name;
        }

        return $this->name;
    }

    public function scopeSearch(Builder $query, string $terim): Builder
    {
        $terim = trim($terim);

        if ($terim === '') {
            return $query;
        }

        $desen = '%'.str_replace(['%', '_'], ['\%', '\_'], $terim).'%';

        return $query->where(function (Builder $q) use ($desen) {
            $q->where('name', 'like', $desen)
                ->orWhere('company_name', 'like', $desen)
                ->orWhere('phone', 'like', $desen)
                ->orWhere('email', 'like', $desen)
                ->orWhere('tax_number', 'like', $desen);
        });
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }
}
