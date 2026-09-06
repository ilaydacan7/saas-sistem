<?php

declare(strict_types=1);

namespace App\Http\Controllers\Central;

use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class SuperAdminLoginController extends Controller
{
    public function create(): View
    {
        return view('yonetim.giris');
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
        $credentials['tenant_id'] = null;
        $credentials['is_super_admin'] = true;

        if (! Auth::attempt($credentials, $request->boolean('remember'))) {
            throw ValidationException::withMessages([
                'email' => 'Bu bilgilerle eşleşen bir hesap bulunamadı.',
            ]);
        }

        $request->session()->regenerate();

        return redirect()->intended(route('yonetim.index'));
    }

    public function destroy(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('yonetim.giris');
    }
}
