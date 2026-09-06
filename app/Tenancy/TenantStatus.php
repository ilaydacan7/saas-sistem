<?php

declare(strict_types=1);

namespace App\Tenancy;

enum TenantStatus: string
{
    case Trialing = 'trialing';

    case Active = 'active';

    case PastDue = 'past_due';

    case Suspended = 'suspended';

    case Cancelled = 'cancelled';

    public function canAccess(): bool
    {
        return match ($this) {
            self::Trialing, self::Active, self::PastDue => true,
            self::Suspended, self::Cancelled => false,
        };
    }

    public function isBillingRestricted(): bool
    {
        return $this === self::PastDue;
    }

    public function label(): string
    {
        return match ($this) {
            self::Trialing => 'Deneme sürümü',
            self::Active => 'Aktif',
            self::PastDue => 'Ödeme gecikmiş',
            self::Suspended => 'Askıya alınmış',
            self::Cancelled => 'İptal edilmiş',
        };
    }
}
