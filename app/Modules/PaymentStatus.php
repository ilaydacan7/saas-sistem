<?php

declare(strict_types=1);

namespace App\Modules;

enum PaymentStatus: string
{
    case Pending = 'pending';
    case Partial = 'partial';
    case Paid = 'paid';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Bekliyor',
            self::Partial => 'Kısmi',
            self::Paid => 'Ödendi',
        };
    }

    public static function forAmounts(int $totalMinor, int $paidMinor): self
    {
        if ($paidMinor <= 0) {
            return self::Pending;
        }

        return $paidMinor >= $totalMinor ? self::Paid : self::Partial;
    }
}
