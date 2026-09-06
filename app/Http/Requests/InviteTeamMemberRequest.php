<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Models\Invitation;
use App\Models\User;
use App\Tenancy\TenantContext;
use App\Tenancy\TenantRole;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class InviteTeamMemberRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge(['email' => Str::lower(trim((string) $this->input('email')))]);
    }

    public function rules(): array
    {
        $atanabilir = array_map(
            fn (TenantRole $rol) => $rol->value,
            $this->user()->role->assignableRoles()
        );

        return [
            'email' => ['required', 'string', 'email', 'max:255'],
            'role' => ['required', Rule::in($atanabilir)],
        ];
    }

    public function after(): array
    {
        return [
            function (Validator $validator): void {
                $tenant = app(TenantContext::class)->getOrFail();
                $email = (string) $this->input('email');

                if (User::ofTenant($tenant)->where('email', $email)->exists()) {
                    $validator->errors()->add('email', 'Bu e-posta ile bir ekip üyesi zaten var.');

                    return;
                }

                if (Invitation::query()->pending()->where('email', $email)->exists()) {
                    $validator->errors()->add('email', 'Bu e-postaya zaten bekleyen bir davet gönderilmiş.');
                }
            },
        ];
    }

    public function attributes(): array
    {
        return [
            'email' => 'e-posta',
            'role' => 'rol',
        ];
    }

    public function role(): TenantRole
    {
        return TenantRole::from($this->validated('role'));
    }
}
