<?php

declare(strict_types=1);

namespace App\Tenancy;

enum TenantRole: string
{
    case Owner = 'owner';
    case Admin = 'admin';
    case Member = 'member';

    public function label(): string
    {
        return match ($this) {
            self::Owner => 'Sahip',
            self::Admin => 'Yönetici',
            self::Member => 'Üye',
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::Owner => 'Her şeyi yapabilir; şirketi ve faturalandırmayı yönetir.',
            self::Admin => 'Ekibi yönetebilir, sahip rolünü veremez ve sahibi çıkaramaz.',
            self::Member => 'Yalnızca kendi işini görür, ekip yönetimine erişemez.',
        };
    }

    public function canManageTeam(): bool
    {
        return $this !== self::Member;
    }

    public function canManageBilling(): bool
    {
        return $this === self::Owner;
    }

    /**
     * Bu rol, hedef roldeki bir kullanıcı üzerinde işlem yapabilir mi?
     */
    public function canActOn(self $hedef): bool
    {
        if (! $this->canManageTeam()) {
            return false;
        }

        return $this === self::Owner || $hedef !== self::Owner;
    }

    /**
     * Bu rol, verilen rolü başkasına atayabilir mi?
     */
    public function canAssign(self $rol): bool
    {
        if (! $this->canManageTeam()) {
            return false;
        }

        return $this === self::Owner || $rol !== self::Owner;
    }

    /**
     * @return list<self>
     */
    public function assignableRoles(): array
    {
        return array_values(array_filter(self::cases(), fn (self $rol) => $this->canAssign($rol)));
    }
}
