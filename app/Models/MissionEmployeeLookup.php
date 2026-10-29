<?php

namespace App\Services;

use App\Models\Employee;
use Illuminate\Support\Collection;

/**
 * Recherche d'un participant par le demandeur.
 * Ne renvoie QUE l'identité : jamais salaire, contrat, congés, données perso.
 *
 * À ADAPTER aux colonnes réelles de la table employees
 * (matricule, first_name, last_name, job_title, department_id).
 */
final class MissionEmployeeLookup
{
    public function search(int $companyId, string $term, int $limit = 10): Collection
    {
        $term = trim($term);

        if (mb_strlen($term) < 3) {
            return collect();
        }

        return Employee::query()
            ->where('company_id', $companyId)
            ->where(function ($q) use ($term) {
                $q->where('matricule', $term)
                    ->orWhere('first_name', 'like', "%{$term}%")
                    ->orWhere('last_name', 'like', "%{$term}%");
            })
            ->with('department:id,name')
            ->limit($limit)
            ->get(['id', 'matricule', 'first_name', 'last_name', 'job_title', 'department_id'])
            ->map(fn (Employee $e) => [
                'id' => $e->id,
                'matricule' => $e->matricule,
                'name' => trim("{$e->first_name} {$e->last_name}"),
                'position_id' => $e->position_id,
                'position' => $e->position?->title,
                'department_id' => $e->department_id,
                'department' => $e->department?->name,
            ]);
    }
}
