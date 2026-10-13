<?php

namespace App\Models\Concerns;

use App\Models\Permission;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use InvalidArgumentException;

trait HasResourcePermissions
{
    protected ?Collection $resolvedPermissions = null;

    /**
     * La ligne de permission de l'utilisateur pour une ressource (ou null).
     * Toutes les permissions sont chargées une seule fois par requête.
     */
    public function permissionFor(string $resource): ?Permission
    {
        $this->resolvedPermissions ??= $this->permissions()->get()->keyBy('resource');

        return $this->resolvedPermissions->get($resource);
    }

    /**
     * L'utilisateur a-t-il le droit d'effectuer cette action sur cette ressource ?
     * $action : create, read, update ou delete.
     */
    public function canDo(string $action, string $resource): bool
    {
        if (! in_array($action, ['create', 'read', 'update', 'delete'], true)) {
            throw new InvalidArgumentException("Action inconnue : {$action}");
        }

        if (! array_key_exists($resource, config('permissions.resources'))) {
            throw new InvalidArgumentException("Ressource inconnue : {$resource}");
        }

        if (! $this->is_active || ! $this->company_id) {
            return false; // compte désactivé, ou administrateur de la plateforme
        }

        if ($this->is_company_owner) {
            return true;
        }

        $permission = $this->permissionFor($resource);

        return $permission !== null && (bool) $permission->{'can_' . $action};
    }

    public function flushPermissionCache(): void
    {
        $this->resolvedPermissions = null;
    }

    /**
     * Retourne la portée (scope) de la permission pour la ressource donnée.
     * Répondu : 'all', 'team', 'own' ou null.
     */
    public function permissionScope(string $resource): ?string
    {
        if (! array_key_exists($resource, config('permissions.resources'))) {
            throw new InvalidArgumentException("Ressource inconnue : {$resource}");
        }

        if (! $this->is_active || ! $this->company_id) {
            return null; // Compte inactif ou administrateur de plateforme
        }

        if ($this->is_company_owner) {
            return 'all';
        }

        $permission = $this->permissionFor($resource);

        return $permission ? $permission->scope : null;
    }
    /**
     * Vérifie si l'utilisateur peut effectuer une action sur un enregistrement spécifique selon sa portée.
     */
    public function canAccessRecord(string $action, string $resource, Model $record): bool
    {
        // 1. Vérification globale du droit d'action sur la ressource
        if (! $this->canDo($action, $resource)) {
            return false;
        }

        $scope = $this->permissionScope($resource);

        // 2. Évaluation selon la portée
        if ($scope === 'all') {
            return true;
        }

        // Si l'utilisateur n'a pas de fiche employé associée, il ne peut accéder à rien en scope 'own' ou 'team'
        if (! $this->employee_id) {
            return false;
        }

        // Identification de l'ID de l'employé concerné par l'enregistrement
        $targetEmployeeId = ($resource === 'employees') ? $record->id : $record->employee_id;

        if ($scope === 'own') {
            return (int) $targetEmployeeId === (int) $this->employee_id;
        }

        if ($scope === 'team') {
            // Un employé a toujours accès à ses propres enregistrements
            if ((int) $targetEmployeeId === (int) $this->employee_id) {
                return true;
            }

            // Récupération des IDs des subordonnés directs
            if ($this->relationLoaded('employee') && $this->employee) {
                $subordinateIds = $this->employee->subordinates()->pluck('id')->toArray();
            } else {
                // Chargement ponctuel s'il n'est pas pré-chargé
                $subordinateIds = \App\Models\Employee::where('manager_id', $this->employee_id)->pluck('id')->toArray();
            }

            return in_array((int) $targetEmployeeId, array_map('intval', $subordinateIds), true);
        }

        return false;
    }
}
