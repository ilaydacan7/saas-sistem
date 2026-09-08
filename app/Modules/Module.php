<?php

declare(strict_types=1);

namespace App\Modules;

enum Module: string
{
    case Customers = 'musteriler';
    case Inventory = 'stok';
    case Sales = 'satis';
    case Finance = 'finans';
    case Collections = 'tahsilat';
    case Appointments = 'randevu';
    case Staff = 'personel';

    public function label(): string
    {
        return match ($this) {
            self::Customers => 'Müşteriler',
            self::Inventory => 'Ürünler ve Stok',
            self::Sales => 'Satışlar',
            self::Finance => 'Gelir ve Gider',
            self::Collections => 'Tahsilat',
            self::Appointments => 'Randevular',
            self::Staff => 'Personel',
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::Customers => 'Müşteri ve tedarikçi kayıtları, iletişim bilgileri, notlar.',
            self::Inventory => 'Ürün ve hizmet kartları, depo, stok giriş çıkış ve kritik stok uyarısı.',
            self::Sales => 'Satış kaydı, sipariş takibi ve satıştan doğan alacak.',
            self::Finance => 'Gelir ve gider hareketleri, kategoriler, kasa durumu.',
            self::Collections => 'Bekleyen, kısmi ve tamamlanan tahsilatlar, vade takibi.',
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
        return in_array($this, [self::Customers, self::Inventory, self::Sales, self::Finance], true);
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
