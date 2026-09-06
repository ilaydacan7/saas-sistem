<?php

declare(strict_types=1);

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\Invitation;
use App\Models\User;
use App\Tenancy\TenantContext;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rules\Password;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class InvitationAcceptController extends Controller
{
    public function __construct(private readonly TenantContext $context) {}

    public function create(string $token): View
    {
        $davet = $this->bekleyenDavet($token);

        return view('auth.davet', [
            'tenant' => $this->context->getOrFail(),
            'davet' => $davet,
        ]);
    }

    public function store(Request $request, string $token): RedirectResponse
    {
        $davet = $this->bekleyenDavet($token);

        $veri = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'password' => ['required', 'confirmed', Password::min(8)->letters()->numbers()],
        ], [], [
            'name' => 'ad soyad',
            'password' => 'parola',
        ]);

        $kullanici = DB::transaction(function () use ($davet, $veri) {
            $kullanici = User::make([
                'tenant_id' => $davet->tenant_id,
                'name' => $veri['name'],
                'email' => $davet->email,
                'password' => $veri['password'],
                'role' => $davet->role,
            ]);

            $kullanici->email_verified_at = now();
            $kullanici->save();

            $davet->forceFill(['accepted_at' => now()])->save();

            return $kullanici;
        });

        Auth::login($kullanici);
        $request->session()->regenerate();

        return redirect()->route('panel')->with('durum', 'Aramıza hoş geldiniz.');
    }

    private function bekleyenDavet(string $token): Invitation
    {
        $davet = Invitation::query()->where('token', $token)->first();

        if ($davet === null || ! $davet->isPending()) {
            throw new NotFoundHttpException;
        }

        return $davet;
    }
}
