<?php

declare(strict_types=1);

namespace App\Models;

use App\Modules\ProductType;
use App\Tenancy\Concerns\BelongsToTenant;
use Database\Factories\ProductFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Product extends Model
{
    /** @use HasFactory<ProductFactory> */
    use BelongsToTenant, HasFactory, SoftDeletes;

    protected $fillable = [
        'tenant_id',
        'type',
        'sku',
        'name',
        'unit_id',
        'purchase_price_minor',
        'sale_price_minor',
        'vat_rate',
        'tracks_stock',
        'min_stock',
        'is_active',
        'note',
    ];

    protected $attributes = [
        'type' => 'product',
        'tracks_stock' => true,
        'is_active' => true,
        'purchase_price_minor' => 0,
        'sale_price_minor' => 0,
        'vat_rate' => 20,
        'min_stock' => 0,
    ];

    protected function casts(): array
    {
        return [
            'type' => ProductType::class,
            'purchase_price_minor' => 'integer',
            'sale_price_minor' => 'integer',
            'vat_rate' => 'integer',
            'tracks_stock' => 'boolean',
            'min_stock' => 'decimal:3',
            'is_active' => 'boolean',
        ];
    }

    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class);
    }

    public function stockLevels(): HasMany
    {
        return $this->hasMany(StockLevel::class);
    }

    public function movements(): HasMany
    {
        return $this->hasMany(StockMovement::class);
    }

    public function purchasePrice(): float
    {
        return $this->purchase_price_minor / 100;
    }

    public function salePrice(): float
    {
        return $this->sale_price_minor / 100;
    }

    /**
     * Tüm depolardaki toplam stok.
     */
    public function totalStock(): float
    {
        return (float) ($this->stock_quantity ?? $this->stockLevels()->sum('quantity'));
    }

    public function isBelowMinimum(): bool
    {
        return $this->tracks_stock && $this->totalStock() < (float) $this->min_stock;
    }

    public function isOutOfStock(): bool
    {
        return $this->tracks_stock && $this->totalStock() <= 0;
    }

    public function unitLabel(): string
    {
        return $this->unit?->short_name ?? 'adet';
    }

    public function scopeSearch(Builder $query, string $terim): Builder
    {
        $terim = trim($terim);

        if ($terim === '') {
            return $query;
        }

        $desen = '%'.str_replace(['%', '_'], ['\%', '\_'], $terim).'%';

        return $query->where(function (Builder $q) use ($desen) {
            $q->where('name', 'like', $desen)->orWhere('sku', 'like', $desen);
        });
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * Listelerde her ürün için stok toplamını tek sorguda getirir.
     */
    public function scopeWithStock(Builder $query): Builder
    {
        return $query->withSum('stockLevels as stock_quantity', 'quantity');
    }
}
