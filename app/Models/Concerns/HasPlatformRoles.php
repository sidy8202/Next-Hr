<?php

namespace App\Models\Concerns;

/**
 * Droits de l'équipe plateforme. À ajouter dans App\Models\User : use HasPlatformRoles;
 * Le propriétaire (platform_role = owner) a tous les droits et ne peut pas être restreint.
 */
trait HasPlatformRoles
{
    public function isPlatformOwner(): bool
    {
        return (bool) $this->is_platform_admin && $this->platform_role === 'owner';
    }

    public function platformPermissionList(): array
    {
        $raw = $this->platform_permissions;

        if (is_array($raw)) {
            return $raw;
        }

        return json_decode((string) $raw, true) ?: [];
    }

    public function platformCan(string $permission): bool
    {
        if (! $this->is_platform_admin || ! $this->is_active) {
            return false;
        }

        return $this->isPlatformOwner() || in_array($permission, $this->platformPermissionList(), true);
    }
}
