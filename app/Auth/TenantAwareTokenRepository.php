<?php

declare(strict_types=1);

namespace App\Auth;

use Illuminate\Auth\Passwords\DatabaseTokenRepository;
use Illuminate\Contracts\Auth\CanResetPassword as CanResetPasswordContract;
use Illuminate\Database\Query\Builder;

class TenantAwareTokenRepository extends DatabaseTokenRepository
{
    public function create(CanResetPasswordContract $user)
    {
        $this->deleteExisting($user);

        $token = $this->createNewToken();

        $this->getTable()->insert(
            $this->getPayload($user->getEmailForPasswordReset(), $token)
            + ['tenant_id' => $this->tenantId($user)]
        );

        return $token;
    }

    protected function deleteExisting(CanResetPasswordContract $user)
    {
        return $this->forUser($user)->delete();
    }

    public function exists(CanResetPasswordContract $user, #[\SensitiveParameter] $token)
    {
        $record = (array) $this->forUser($user)->first();

        return $record
            && ! $this->tokenExpired($record['created_at'])
            && $this->hasher->check($token, $record['token']);
    }

    public function recentlyCreatedToken(CanResetPasswordContract $user)
    {
        $record = (array) $this->forUser($user)->first();

        return $record && $this->tokenRecentlyCreated($record['created_at']);
    }

    private function forUser(CanResetPasswordContract $user): Builder
    {
        $tenantId = $this->tenantId($user);

        return $this->getTable()
            ->where('email', $user->getEmailForPasswordReset())
            ->when(
                $tenantId === null,
                fn (Builder $q) => $q->whereNull('tenant_id'),
                fn (Builder $q) => $q->where('tenant_id', $tenantId)
            );
    }

    private function tenantId(CanResetPasswordContract $user): ?int
    {
        return $user->tenant_id;
    }
}
