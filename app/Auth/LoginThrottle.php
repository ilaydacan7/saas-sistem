<?php

declare(strict_types=1);

namespace App\Auth;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * Parola deneme sınırı.
 *
 * Anahtar e-posta, IP ve işletme birlikte alınır:
 *  - Yalnızca IP'ye bakmak, aynı ofisten çalışan kullanıcıları birbirine
 *    kilitlerdi.
 *  - Yalnızca e-postaya bakmak, saldırganın bir kullanıcıyı kasten
 *    kilitlemesine (hesap kilitleme saldırısı) izin verirdi.
 *
 * Ayrıca IP başına daha geniş bir sınır uygulanır: saldırgan e-posta
 * değiştirerek sınırı aşmasın.
 */
class LoginThrottle
{
    private const DENEME_SINIRI = 5;

    private const IP_SINIRI = 20;

    private const KILIT_SANIYE = 60;

    public function ensureIsNotLimited(Request $request, string $email, ?int $tenantId = null): void
    {
        foreach ([$this->anahtar($request, $email, $tenantId) => self::DENEME_SINIRI, $this->ipAnahtari($request) => self::IP_SINIRI] as $anahtar => $sinir) {
            if (RateLimiter::tooManyAttempts($anahtar, $sinir)) {
                $kalan = RateLimiter::availableIn($anahtar);

                throw ValidationException::withMessages([
                    'email' => $this->mesaj($kalan),
                ]);
            }
        }
    }

    public function hit(Request $request, string $email, ?int $tenantId = null): void
    {
        RateLimiter::hit($this->anahtar($request, $email, $tenantId), self::KILIT_SANIYE);
        RateLimiter::hit($this->ipAnahtari($request), self::KILIT_SANIYE);
    }

    public function clear(Request $request, string $email, ?int $tenantId = null): void
    {
        RateLimiter::clear($this->anahtar($request, $email, $tenantId));
    }

    private function anahtar(Request $request, string $email, ?int $tenantId): string
    {
        return 'giris:'.sha1(Str::lower($email).'|'.$request->ip().'|'.($tenantId ?? 'merkez'));
    }

    private function ipAnahtari(Request $request): string
    {
        return 'giris-ip:'.sha1((string) $request->ip());
    }

    private function mesaj(int $kalanSaniye): string
    {
        if ($kalanSaniye >= 60) {
            $dakika = (int) ceil($kalanSaniye / 60);

            return "Çok fazla başarısız deneme yapıldı. {$dakika} dakika sonra tekrar deneyin.";
        }

        return "Çok fazla başarısız deneme yapıldı. {$kalanSaniye} saniye sonra tekrar deneyin.";
    }
}
