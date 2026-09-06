<?php

declare(strict_types=1);

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Tenancy\TenantContext;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;

class ForgotPasswordController extends Controller
{
    public function __construct(private readonly TenantContext $context) {}

    public function create(): View
    {
        return view('auth.parola-unuttum', [
            'tenant' => $this->context->getOrFail(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate(
            ['email' => ['required', 'string', 'email']],
            [],
            ['email' => 'e-posta']
        );

        $durum = Password::sendResetLink([
            'email' => Str::lower($request->string('email')->toString()),
            'tenant_id' => $this->context->getOrFail()->getKey(),
        ]);

        if ($durum === Password::RESET_THROTTLED) {
            return back()->withInput()->withErrors([
                'email' => 'Kısa süre önce bir bağlantı gönderdik. Lütfen birkaç dakika bekleyin.',
            ]);
        }

        return back()->with('durum', 'Bu e-posta adresine kayıtlı bir hesap varsa, sıfırlama bağlantısı gönderildi.');
    }
}
