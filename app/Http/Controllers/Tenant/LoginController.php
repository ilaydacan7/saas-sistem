<?php

declare(strict_types=1);

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Tenancy\TenantContext;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class LoginController extends Controller
{
    public function __construct(private readonly TenantContext $context) {}

    public function create(Request $request): View
    {
        return view('auth.login', [
            'tenant' => $this->context->getOrFail(),
            'justRegistered' => $request->query('kayit') === 'tamam',
            'merkezGirisUrl' => $this->merkezGirisUrl($request),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
        ], [], [
            'email' => 'e-posta',
            'password' => 'parola',
        ]);

        $credentials['email'] = Str::lower($credentials['email']);
        $credentials['tenant_id'] = $this->context->getOrFail()->getKey();

        if (! Auth::attempt($credentials, $request->boolean('remember'))) {
            throw ValidationException::withMessages([
                'email' => 'Bu bilgilerle eşleşen bir hesap bulunamadı.',
            ]);
        }

        $request->session()->regenerate();

        return redirect()->intended(route('panel'));
    }

    /**
     * Yanlış şirkete gelen kullanıcı merkezdeki giriş kapısına dönebilsin.
     */
    private function merkezGirisUrl(Request $request): string
    {
        $port = $request->getPort();
        $ek = in_array($port, [80, 443], true) ? '' : ':'.$port;

        return $request->getScheme().'://'.config('tenancy.base_domain').$ek.'/giris';
    }

    public function destroy(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('giris');
    }
}
