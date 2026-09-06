<?php

declare(strict_types=1);

namespace App\Models;

use App\Tenancy\Concerns\BelongsToTenant;
use Database\Factories\PaymentFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use NumberFormatter;

/**
 * @property int $amount_minor
 * @property string $currency
 */
class Payment extends Model
{
    /** @use HasFactory<PaymentFactory> */
    use BelongsToTenant, HasFactory;

    protected $fillable = [
        'tenant_id',
        'amount_minor',
        'currency',
        'method',
        'period_starts_at',
        'period_ends_at',
        'recorded_by_id',
        'note',
    ];

    protected function casts(): array
    {
        return [
            'amount_minor' => 'integer',
            'period_starts_at' => 'datetime',
            'period_ends_at' => 'datetime',
        ];
    }

    public function recordedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by_id');
    }

    public function amount(): float
    {
        return $this->amount_minor / 100;
    }

    public function formattedAmount(): string
    {
        $formatter = new NumberFormatter(config('app.locale'), NumberFormatter::CURRENCY);

        return $formatter->formatCurrency($this->amount(), $this->currency);
    }
}
