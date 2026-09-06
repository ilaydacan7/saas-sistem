<?php

declare(strict_types=1);

namespace App\Models;

use App\Notifications\TeamInvitation;
use App\Tenancy\Concerns\BelongsToTenant;
use App\Tenancy\TenantRole;
use Database\Factories\InvitationFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;

class Invitation extends Model
{
    /** @use HasFactory<InvitationFactory> */
    use BelongsToTenant, HasFactory, Notifiable;

    protected $fillable = [
        'tenant_id',
        'email',
        'role',
        'token',
        'invited_by_id',
        'expires_at',
    ];

    protected function casts(): array
    {
        return [
            'role' => TenantRole::class,
            'expires_at' => 'datetime',
            'accepted_at' => 'datetime',
        ];
    }

    public static function freshToken(): string
    {
        return Str::random(64);
    }

    public function invitedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'invited_by_id');
    }

    public function scopePending(Builder $query): Builder
    {
        return $query->whereNull('accepted_at')->where('expires_at', '>', now());
    }

    public function isPending(): bool
    {
        return $this->accepted_at === null && $this->expires_at->isFuture();
    }

    public function routeNotificationForMail(): string
    {
        return $this->email;
    }

    public function sendInvitationNotification(): void
    {
        $this->notify(new TeamInvitation($this));
    }
}
