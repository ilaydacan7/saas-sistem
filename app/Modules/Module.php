<?php

declare(strict_types=1);

namespace App\Modules;

enum Module: string
{
    case Customers = 'musteriler';
    case Catalog = 'katalog';
    case Finance = 'finans';
    case Appointments = 'randevu';
    case Staff = 'personel';

    public function label(): string
    {
        return match ($this) {
            self::Customers => 'Müşteriler',
            self::Catalog => 'Ürün ve Hizmetler',
            self::Finance => 'Gelir ve Gider',
            self::Appointments => 'Randevular',
            self::Staff => 'Personel',
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::Customers => 'Müşteri ve tedarikçi kayıtları, iletişim bilgileri, notlar.',
            self::Catalog => 'Sattığınız ürün ve hizmetler, fiyat listesi.',
            self::Finance => 'Kasa hareketleri, tahsilat ve ödemeler.',
            self::Appointments => 'Randevu takvimi ve iş planlaması.',
            self::Staff => 'Personel kayıtları ve devam takibi.',
        };
    }

    public function routeName(): string
    {
        return $this->value.'.index';
    }

    /**
     * Diğer modüllerin bağlandığı temel modül; kapatılamaz.
     */
    public function isCore(): bool
    {
        return $this === self::Customers;
    }

    /**
     * Henüz geliştirilmemiş modüller ayarlarda görünür ama açılamaz.
     */
    public function isAvailable(): bool
    {
        return $this === self::Customers;
    }

    /**
     * Yeni kaydolan bir işletmede varsayılan olarak açık gelen modüller.
     *
     * @return list<self>
     */
    public static function defaults(): array
    {
        return [self::Customers];
    }
}
