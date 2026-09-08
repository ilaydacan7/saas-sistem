<?php

declare(strict_types=1);

namespace App\Modules;

enum SaleStatus: string
{
    case Draft = 'draft';
    case Confirmed = 'confirmed';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Taslak',
            self::Confirmed => 'Tamamlandı',
            self::Cancelled => 'İptal',
        };
    }

    /**
     * Bu durumdaki satış stoğu ve cari bakiyeyi etkiler mi?
     */
    public function affectsStock(): bool
    {
        return $this === self::Confirmed;
    }
}
