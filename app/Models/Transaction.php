<?php

declare(strict_types=1);

namespace App\Models;

use App\Modules\PaymentMethod;
use App\Modules\TransactionType;
use App\Tenancy\Concerns\BelongsToTenant;
use Database\Factories\TransactionFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Transaction extends Model
{
    /** @use HasFactory<TransactionFactory> */
    use BelongsToTenant, HasFactory;

    protected $fillable = [
        'tenant_id',
        'type',
        'category_id',
        'amount_minor',
        'occurred_on',
        'description',
        'method',
        'source_type',
        'source_id',
        'created_by_id',
    ];

    protected function casts(): array
    {
        return [
            'type' => TransactionType::class,
            'method' => PaymentMethod::class,
            'amount_minor' => 'integer',
            'occurred_on' => 'date',
        ];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(TransactionCategory::class, 'category_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_id');
    }

    /**
     * Otomatik oluşan kayıtlar elle düzenlenemez; kaynağı değişirse tutarsızlık olur.
     */
    public function isAutomatic(): bool
    {
        return $this->source_type !== null;
    }

    public function sourceSale(): ?Sale
    {
        if ($this->source_type !== SalePayment::class || $this->source_id === null) {
            return null;
        }

        return SalePayment::query()->find($this->source_id)?->sale;
    }

    public function scopeIncome(Builder $query): Builder
    {
        return $query->where('type', TransactionType::Income);
    }

    public function scopeExpense(Builder $query): Builder
    {
        return $query->where('type', TransactionType::Expense);
    }

    public function scopeBetween(Builder $query, $baslangic, $bitis): Builder
    {
        return $query->whereBetween('occurred_on', [$baslangic, $bitis]);
    }
}
