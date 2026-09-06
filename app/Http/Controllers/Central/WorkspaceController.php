<?php

declare(strict_types=1);

namespace App\Http\Controllers\Central;

use App\Http\Controllers\Controller;
use App\Models\Tenant;
use App\Models\User;
use App\Notifications\WorkspaceReminder;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * Merkezdeki giriş kapısı.
 *
 * Girişler kiracının kendi adresinde yapılır; müşteri adresini bilmiyorsa
 * buradan bulur ve yönlendirilir.
 */
class WorkspaceController extends Controller
{
    public function create(): View
    {
        return view('central.giris', [
            'baseDomain' => config('tenancy.base_domain'),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate(
            ['adres' => ['required', 'string', 'max:255']],
            [],
            ['adres' => 'şirket adresi']
        );

        $tenant = $this->resolve((string) $request->input('adres'));

        if ($tenant === null) {
            throw ValidationException::withMessages([
                'adres' => 'Bu adrese sahip bir şirket bulunamadı. Adresinizi hatırlamıyorsanız aşağıdan e-posta ile bulabilirsiniz.',
            ]);
        }

        return redirect()->away($this->loginUrl($tenant));
    }

    public function findCreate(): View
    {
        return view('central.adresimi-bul');
    }

    public function findStore(Request $request): RedirectResponse
    {
        $veri = $request->validate(
            ['email' => ['required', 'string', 'email', 'max:255']],
            [],
            ['email' => 'e-posta']
        );

        $email = Str::lower(trim($veri['email']));

        $tenants = Tenant::query()
            ->whereIn('id', User::query()
                ->whereNotNull('tenant_id')
                ->where('email', $email)
                ->pluck('tenant_id'))
            ->orderBy('name')
            ->get();

        if ($tenants->isNotEmpty()) {
            Notification::route('mail', $email)->notify(new WorkspaceReminder($tenants, $this->port(), $this->scheme()));
        }

        // Kayıtlı olmayan adres için de aynı yanıt: hesap var mı yok mu sızdırılmaz.
        return back()->with('durum', 'Bu e-posta adresine kayıtlı şirket varsa, adresleri e-posta ile gönderildi.');
    }

    /**
     * Kullanıcı "acme", "acme.saas.localhost" veya tam URL yazmış olabilir.
     */
    private function resolve(string $girdi): ?Tenant
    {
        $temiz = Str::lower(trim($girdi));
        $temiz = preg_replace('#^https?://#', '', $temiz) ?? $temiz;
        $temiz = explode('/', $temiz)[0];
        $temiz = explode(':', $temiz)[0];

        if ($temiz === '') {
            return null;
        }

        $tenant = Tenant::query()->where('custom_domain', $temiz)->first();

        if ($tenant !== null) {
            return $tenant;
        }

        $baseDomain = Str::lower((string) config('tenancy.base_domain'));

        if ($baseDomain !== '' && str_ends_with($temiz, '.'.$baseDomain)) {
            $temiz = substr($temiz, 0, -(strlen($baseDomain) + 1));
        }

        if (str_contains($temiz, '.')) {
            return null;
        }

        return Tenant::query()->where('slug', $temiz)->first();
    }

    private function loginUrl(Tenant $tenant): string
    {
        return $this->scheme().'://'.$tenant->host().$this->port().'/giris';
    }

    private function port(): string
    {
        $port = request()->getPort();

        return in_array($port, [80, 443], true) ? '' : ':'.$port;
    }

    private function scheme(): string
    {
        return request()->getScheme();
    }
}
