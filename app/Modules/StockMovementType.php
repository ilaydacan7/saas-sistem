<?php

declare(strict_types=1);

namespace App\Modules;

enum StockMovementType: string
{
    case In = 'in';
    case Out = 'out';
    case Adjustment = 'adjustment';

    public function label(): string
    {
        return match ($this) {
            self::In => 'Giriş',
            self::Out => 'Çıkış',
            self::Adjustment => 'Düzeltme',
        };
    }

    /**
     * Hareket stok miktarını artırıyor mu, azaltıyor mu?
     * Düzeltmede miktar mutlak sayım sonucudur, işaret taşımaz.
     */
    public function sign(): int
    {
        return match ($this) {
            self::In => 1,
            self::Out => -1,
            self::Adjustment => 0,
        };
    }
}
