<?php

namespace App\Support;

use App\Models\Employee;
use App\Models\Permission;
use App\Models\User;

/** Qui voit les demandes de qui, selon la portée (own / team / all) accordée sur une ressource. */
final class LeaveVisibility
{
    /** Portée enregistrée dans la matrice des droits (/droits), lue directement : own, team, all ou null. */
    public static function storedScope(User $user, string $resource): ?string
    {
        return Permission::where('user_id', $user->id)->where('resource', $resource)->value('scope');
    }

    public static function scope(User $user, string $resource): string
    {
        if ($user->is_company_owner) {
            return 'all';
        }

        return $user->canDo('read', $resource)
            ? (self::storedScope($user, $resource) ?? $user->permissionScope($resource) ?? 'own')
            : 'none';
    }

    /**
     * « Ressources humaines » pour une ressource de congés : propriétaire de l'entreprise, ou droit « Modifier » avec la portée « Tout ».
     * Un droit « Modifier » limité à « Mes données » ou « Mon équipe » ne donne PAS accès aux données des autres.
     */
    public static function isHr(User $user, string $resource = 'leave_requests'): bool
    {
        if ($user->is_company_owner) {
            return true;
        }

        return $user->canDo('update', $resource) && self::storedScope($user, $resource) === 'all';
    }

    /**
     * Identifiants des employés visibles : null = tous, tableau = seulement ceux-là.
     * « team » = soi-même + les employés dont on est le manager direct.
     *
     * @return array<int, int>|null
     */
    public static function employeeIds(User $user, string $resource): ?array
    {
        $scope = self::scope($user, $resource);

        if ($scope === 'all') {
            return null;
        }
        if ($scope === 'none' || ! $user->employee_id) {
            return [];
        }
        if ($scope === 'team') {
            return Employee::where('manager_id', $user->employee_id)->pluck('id')->push((int) $user->employee_id)->unique()->values()->all();
        }

        return [(int) $user->employee_id];
    }

    public static function canSee(User $user, string $resource, int $employeeId): bool
    {
        $ids = self::employeeIds($user, $resource);

        return $ids === null || in_array($employeeId, $ids, true);
    }
}
