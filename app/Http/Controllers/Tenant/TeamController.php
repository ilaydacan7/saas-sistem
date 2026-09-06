<?php

declare(strict_types=1);

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Http\Requests\InviteTeamMemberRequest;
use App\Models\Invitation;
use App\Models\User;
use App\Tenancy\TenantContext;
use App\Tenancy\TenantRole;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpException;

class TeamController extends Controller
{
    public function __construct(private readonly TenantContext $context) {}

    public function index(Request $request): View
    {
        $tenant = $this->context->getOrFail();

        return view('ekip.index', [
            'tenant' => $tenant,
            'uyeler' => User::ofTenant($tenant)->orderBy('name')->get(),
            'davetler' => Invitation::query()->pending()->with('invitedBy')->orderByDesc('created_at')->get(),
            'atanabilirRoller' => $request->user()->role->assignableRoles(),
        ]);
    }

    public function invite(InviteTeamMemberRequest $request): RedirectResponse
    {
        $davet = Invitation::create([
            'tenant_id' => $this->context->getOrFail()->getKey(),
            'email' => $request->validated('email'),
            'role' => $request->role(),
            'token' => Invitation::freshToken(),
            'invited_by_id' => $request->user()->getKey(),
            'expires_at' => now()->addDays(7),
        ]);

        $davet->sendInvitationNotification();

        return back()->with('durum', $davet->email.' adresine davet gönderildi.');
    }

    public function cancelInvite(Invitation $invitation): RedirectResponse
    {
        $invitation->delete();

        return back()->with('durum', 'Davet iptal edildi.');
    }

    public function updateRole(Request $request, User $user): RedirectResponse
    {
        $this->kiraciUyesiOlmali($user);

        $yapan = $request->user();

        $veri = $request->validate([
            'role' => ['required', Rule::in(array_map(
                fn (TenantRole $rol) => $rol->value,
                $yapan->role->assignableRoles()
            ))],
        ], [], ['role' => 'rol']);

        $yeniRol = TenantRole::from($veri['role']);

        if (! $yapan->role->canActOn($user->role)) {
            throw new HttpException(403, 'Bu kullanıcı üzerinde işlem yapma yetkiniz yok.');
        }

        if ($yapan->is($user)) {
            throw ValidationException::withMessages(['role' => 'Kendi rolünüzü değiştiremezsiniz.']);
        }

        if ($user->isOwner() && $yeniRol !== TenantRole::Owner && $this->sahipSayisi() === 1) {
            throw ValidationException::withMessages(['role' => 'Şirketin en az bir sahibi olmalı.']);
        }

        $user->forceFill(['role' => $yeniRol])->save();

        return back()->with('durum', $user->name.' artık '.$yeniRol->label().'.');
    }

    public function remove(Request $request, User $user): RedirectResponse
    {
        $this->kiraciUyesiOlmali($user);

        $yapan = $request->user();

        if ($yapan->is($user)) {
            throw ValidationException::withMessages(['uye' => 'Kendinizi ekipten çıkaramazsınız.']);
        }

        if (! $yapan->role->canActOn($user->role)) {
            throw new HttpException(403, 'Bu kullanıcı üzerinde işlem yapma yetkiniz yok.');
        }

        if ($user->isOwner() && $this->sahipSayisi() === 1) {
            throw ValidationException::withMessages(['uye' => 'Şirketin en az bir sahibi olmalı.']);
        }

        $isim = $user->name;
        $user->delete();

        return back()->with('durum', $isim.' ekipten çıkarıldı.');
    }

    private function kiraciUyesiOlmali(User $user): void
    {
        if ($user->tenant_id !== $this->context->getOrFail()->getKey()) {
            throw new HttpException(404);
        }
    }

    private function sahipSayisi(): int
    {
        return User::ofTenant($this->context->getOrFail())
            ->where('role', TenantRole::Owner)
            ->count();
    }
}
