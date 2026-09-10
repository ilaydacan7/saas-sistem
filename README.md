# FLOWZA

Farklı işletmelerin ortak ihtiyaçlarını tek yerden yöneten çok kiracılı (multi-tenant) işletme yönetim sistemi.

Her işletme aynı uygulamayı kendi adresinde, kendi verisiyle kullanır. İşletme yalnızca işine yarayan modülleri açar: bir hırdavatçı stok ve satış, bir danışmanlık firması yalnızca müşteri takibi açabilir.

## Modüller

| Modül | Kapsam | Durum |
|---|---|---|
| Müşteriler | Bireysel ve kurumsal cari kayıtları, iletişim bilgileri, notlar | Hazır |
| Ürünler ve Stok | Ürün ve hizmet kartları, depo, stok giriş/çıkış/sayım, kritik stok uyarısı | Hazır |
| Satışlar | Satış belgesi, stoktan düşme, KDV, vade ve tahsilat takibi | Hazır |
| Gelir ve Gider | Kasa hareketleri, kategoriler, dönem özeti | Hazır |
| Tahsilat | Vade takibi ve tahsilat ekranları | Planlandı |
| Randevu | Randevu takvimi ve iş planlaması | Planlandı |
| Personel | Personel kayıtları ve devam takibi | Planlandı |

## Mimari

**Kiracı izolasyonu.** Tüm işletmeler tek veritabanını paylaşır; ayrım satır düzeyinde `tenant_id` ile yapılır. `BelongsToTenant` özelliğini kullanan her model, aktif kiracıya otomatik olarak kapsanır. Kiracı bağlamı kurulmadan sorgu çalıştırılırsa istisna fırlatılır — sessizce tüm işletmelerin verisini döndürmek yerine gürültülü başarısızlık tercih edilmiştir.

**Kiracı çözümleme.** İstek, alan adına bakılarak kiracıya bağlanır: önce özel alan adı (`panel.musteri.com`), sonra alt alan adı (`musteri.flowza.com`). Merkezi alan adı kayıt, giriş kapısı ve yönetim paneli içindir.

**Modüller.** Hangi modülün açık olduğu kiracı bazında tutulur. Kapalı bir modülün rotaları 404 döner, menüde ve panelde görünmez.

**Para ve miktar.** Tutarlar kuruş cinsinden tam sayı olarak saklanır; ondalıklı sayı kullanılmaz. Miktarlar üç ondalık basamaklı `decimal` alanlardır. Sayı girişleri Türkçe yazımı kabul eder (`1.499,90`).

**Modüller arası iletişim.** Satış tahsilatı alındığında olay yayınlanır; finans modülü açıksa kasaya gelir olarak yazılır, kapalıysa hiçbir şey olmaz. Modüller birbirine doğrudan bağlı değildir.

## Gereksinimler

- PHP 8.2+ (`pdo_pgsql`, `intl`, `zlib` eklentileri)
- Composer 2
- Node.js 20+
- Docker (PostgreSQL, Redis ve Mailpit için)

## Kurulum

```bash
git clone https://github.com/ilaydacan7/saas-sistem.git
cd saas-sistem

cp .env.example .env

docker compose up -d          # PostgreSQL, Redis, Mailpit
composer install
npm install

php artisan key:generate
php artisan migrate --seed
npm run build
```

Geliştirme sunucusu:

```bash
php artisan serve
```

## Yerel adresler

Kiracılar alt alan adıyla çözümlendiği için birden fazla adrese ihtiyaç duyulur. `.localhost` uzantılı adresler tarayıcılar tarafından kendiliğinden `127.0.0.1` adresine çözüldüğü için `hosts` dosyasını düzenlemek gerekmez.

| Adres | İçerik |
|---|---|
| `http://saas.localhost:8000` | Tanıtım sayfası, kayıt ve giriş kapısı |
| `http://saas.localhost:8000/yonetim` | Sistem yöneticisi paneli |
| `http://yildiz.saas.localhost:8000` | Örnek işletme (stok ve satış verisiyle) |
| `http://localhost:8025` | Mailpit — giden e-postalar |

`hosts` dosyasını düzenlemeyi tercih ederseniz `.env` içindeki `TENANCY_BASE_DOMAIN` değerini değiştirmeniz yeterlidir.

## Örnek hesaplar

Seeder üç örnek işletme oluşturur. Tüm hesapların parolası `parola12345`.

| Adres | İşletme | Giriş | Durum |
|---|---|---|---|
| `saas.localhost:8000/yonetim/giris` | — | `admin@saas.local` | Sistem yöneticisi |
| `yildiz.saas.localhost:8000` | Yıldız Hırdavat | `yonetici@ornek.com` | Aktif, satış ve stok verisi dolu |
| `ece.saas.localhost:8000` | Ece Kuaför | `yonetici@ornek.com` | Deneme sürümü |
| `demir.saas.localhost:8000` | Demir Danışmanlık | `yonetici@ornek.com` | Ödemesi gecikmiş |

Her işletmede `uye@ornek.com` adresiyle sınırlı yetkili bir kullanıcı da bulunur.

## Testler

```bash
php artisan test
```

Testler bellek içi SQLite üzerinde çalışır, geliştirme veritabanına dokunmaz.

## Bakım komutları

```bash
php artisan yedek:al                     # sıkıştırılmış veritabanı yedeği
php artisan tenants:expire-trials        # süresi dolan denemeleri işaretle
php artisan tenants:expire-subscriptions # dönemi biten abonelikleri işaretle
```

Zamanlanmış işler `routes/console.php` içinde tanımlıdır. Sunucuda tek bir cron kaydı yeterlidir:

```
* * * * * cd /proje/yolu && php artisan schedule:run >> /dev/null 2>&1
```

## Yedekleme

`yedek:al` komutu veritabanının sıkıştırılmış dökümünü alır ve saklama süresini aşan eski yedekleri siler. Yedekler her gece 02:00'de çalışacak şekilde zamanlanmıştır.

Veritabanı konteynerde çalıştığı için döküm komutu `.env` üzerinden yönlendirilir:

```
BACKUP_DUMP_COMMAND="docker exec -i -e PGPASSWORD saas_postgres pg_dump"
BACKUP_PATH=/yedeklerin/tutulacagi/klasor
BACKUP_KEEP_DAYS=14
```

Sunucuda PostgreSQL istemcisi kuruluysa `BACKUP_DUMP_COMMAND=pg_dump` yeterlidir. Yedek klasörünü uygulama sunucusunun dışında bir yerde tutun.

## Güvenlik

- Girişte hesap başına 5, IP başına 20 başarısız denemeden sonra geçici kilit uygulanır
- Parola sıfırlama, kayıt, davet kabul ve adres hatırlatma uçlarında istek sınırı vardır
- Parola sıfırlama belirteçleri kiracı kapsamındadır; bir işletmedeki belirteç başka işletmedeki aynı e-postalı hesabı etkilemez
- Oturum çerezleri şifrelenir; üretimde `SESSION_SECURE_COOKIE=true` ile yalnızca HTTPS üzerinden gönderilir
- Yedek dosyaları ve `.env` sürüm kontrolüne dâhil edilmez

## Üretime alırken

- `APP_ENV=production`, `APP_DEBUG=false`
- HTTPS zorunlu; alt alan adları için joker (wildcard) sertifika gerekir
- `SESSION_SECURE_COOKIE=true`
- Gerçek SMTP bilgileri
- `php artisan schedule:run` için cron kaydı
- Kuyruk işçisi: `php artisan queue:work`
- Yedeklerin sunucu dışına kopyalanması

## Dizin yapısı

```
app/
├── Auth/          Kiracı kapsamlı parola sıfırlama ve giriş sınırı
├── Billing/       Abonelik ödemelerinin kaydı
├── Finance/       Kasa hareketleri
├── Inventory/     Stok hesabı ve depo hazırlığı
├── Modules/       Modül kataloğu ve alan türleri
├── Reporting/     Panel özetleri
├── Sales/         Satış, tahsilat ve iptal iş akışı
└── Tenancy/       Kiracı bağlamı, çözümleyiciler, kapsam
```

## Lisans

Bu depo özel kullanım içindir.
