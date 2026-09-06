<?php

declare(strict_types=1);

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Tenancy\TenantContext;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password as ParolaKurali;

class ResetPasswordController extends Controller
{
    public function __construct(private readonly TenantContext $context) {}

    public function create(Request $request, string $token): View
    {
        return view('auth.parola-sifirla', [
            'tenant' => $this->context->getOrFail(),
            'token' => $token,
            'email' => (string) $request->query('email', ''),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'token' => ['required', 'string'],
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'confirmed', ParolaKurali::min(8)->letters()->numbers()],
        ], [], [
            'email' => 'e-posta',
            'password' => 'parola',
        ]);

        $durum = Password::reset(
            [
                'email' => Str::lower($request->string('email')->toString()),
                'password' => $request->string('password')->toString(),
                'password_confirmation' => $request->string('password_confirmation')->toString(),
                'token' => $request->string('token')->toString(),
                'tenant_id' => $this->context->getOrFail()->getKey(),
            ],
            function (User $user, string $password): void {
                $user->forceFill([
                    'password' => $password,
                    'remember_token' => Str::random(60),
                ])->save();

                event(new PasswordReset($user));
            }
        );

        if ($durum !== Password::PASSWORD_RESET) {
            return back()->withInput($request->only('email'))->withErrors([
                'email' => 'Bu sıfırlama bağlantısı geçersiz veya süresi dolmuş. Lütfen yeniden talep edin.',
            ]);
        }

        return redirect()->route('giris')->with('durum', 'Parolanız güncellendi, giriş yapabilirsiniz.');
    }
}
