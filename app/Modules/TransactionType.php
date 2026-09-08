<?php

declare(strict_types=1);

namespace App\Modules;

enum TransactionType: string
{
    case Income = 'income';
    case Expense = 'expense';

    public function label(): string
    {
        return match ($this) {
            self::Income => 'Gelir',
            self::Expense => 'Gider',
        };
    }

    public function sign(): int
    {
        return $this === self::Income ? 1 : -1;
    }

    /**
     * Yeni bir işletmede hazır gelen kategoriler.
     *
     * @return list<string>
     */
    public function defaultCategories(): array
    {
        return match ($this) {
            self::Income => ['Satış', 'Hizmet', 'Diğer gelir'],
            self::Expense => ['Kira', 'Elektrik', 'Su', 'İnternet', 'Personel', 'Mal alımı', 'Reklam', 'Vergi', 'Diğer gider'],
        };
    }

    /**
     * Otomatik kayıtların bağlandığı, silinemeyen kategori.
     */
    public function systemCategory(): ?string
    {
        return $this === self::Income ? 'Satış' : null;
    }
}
