<?php

declare(strict_types=1);

namespace App\Models;

use App\Modules\PaymentStatus;
use App\Modules\SaleStatus;
use App\Tenancy\Concerns\BelongsToTenant;
use Database\Factories\SaleFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Sale extends Model
{
    /** @use HasFactory<SaleFactory> */
    use BelongsToTenant, HasFactory, SoftDeletes;

    protected $fillable = [
        'tenant_id',
        'number',
        'customer_id',
        'warehouse_id',
        'status',
        'subtotal_minor',
        'discount_minor',
        'vat_minor',
        'total_minor',
        'paid_minor',
        'sold_at',
        'due_on',
        'note',
        'created_by_id',
    ];

    protected $attributes = [
        'status' => 'confirmed',
        'subtotal_minor' => 0,
        'discount_minor' => 0,
        'vat_minor' => 0,
        'total_minor' => 0,
        'paid_minor' => 0,
    ];

    protected function casts(): array
    {
        return [
            'status' => SaleStatus::class,
            'subtotal_minor' => 'integer',
            'discount_minor' => 'integer',
            'vat_minor' => 'integer',
            'total_minor' => 'integer',
            'paid_minor' => 'integer',
            'sold_at' => 'datetime',
            'due_on' => 'date',
        ];
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_id');
    }

    public function lines(): HasMany
    {
        return $this->hasMany(SaleLine::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(SalePayment::class);
    }

    public function paymentStatus(): PaymentStatus
    {
        return PaymentStatus::forAmounts($this->total_minor, $this->paid_minor);
    }

    public function remainingMinor(): int
    {
        return max(0, $this->total_minor - $this->paid_minor);
    }

    public function customerLabel(): string
    {
        return $this->customer?->displayName() ?? 'Perakende satış';
    }

    public function isOverdue(): bool
    {
        return $this->status === SaleStatus::Confirmed
            && $this->due_on !== null
            && $this->due_on->isPast()
            && $this->remainingMinor() > 0;
    }

    public function scopeConfirmed(Builder $query): Builder
    {
        return $query->where('status', SaleStatus::Confirmed);
    }

    public function scopeUnpaid(Builder $query): Builder
    {
        return $query->confirmed()->whereColumn('paid_minor', '<', 'total_minor');
    }

    public function scopeBetween(Builder $query, $baslangic, $bitis): Builder
    {
        return $query->whereBetween('sold_at', [$baslangic, $bitis]);
    }
}
