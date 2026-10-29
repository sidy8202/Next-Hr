<?php

namespace App\Services;

use App\Models\Employee;
use Illuminate\Support\Collection;

/**
 * Sélection des participants : ne renvoie QUE l'identité
 * (nom, matricule, poste, département). Jamais salaire, contrat, congés, données perso.
 */
final class MissionEmployeeLookup
{
    private function base(int $companyId)
    {
        return Employee::query()
            ->where('company_id', $companyId)
            ->where('status', 'active')
            ->with(['department:id,name', 'position:id,title']);
    }

    private function row(Employee $e): array
    {
        return [
            'employee_id' => $e->id,
            'name' => $e->full_name,
            'matricule' => $e->matricule,
            'position' => $e->position?->title,
            'department_id' => $e->department_id,
            'department' => $e->department?->name,
        ];
    }

    private const COLUMNS = ['id', 'company_id', 'matricule', 'first_name', 'last_name', 'department_id', 'position_id'];

    public function search(int $companyId, string $term, array $exclude = [], int $limit = 10): Collection
    {
        $term = trim($term);

        if (mb_strlen($term) < 3) {
            return collect();
        }

        return $this->base($companyId)
            ->whereNotIn('id', $exclude)
            ->where(function ($q) use ($term) {
                $q->where('matricule', $term)
                    ->orWhere('first_name', 'like', "%{$term}%")
                    ->orWhere('last_name', 'like', "%{$term}%");
            })
            ->orderBy('last_name')->limit($limit)->get(self::COLUMNS)
            ->map(fn (Employee $e) => $this->row($e));
    }

    public function byDepartment(int $companyId, int $departmentId, array $exclude = []): Collection
    {
        return $this->base($companyId)
            ->where('department_id', $departmentId)
            ->whereNotIn('id', $exclude)
            ->orderBy('last_name')->limit(60)->get(self::COLUMNS)
            ->map(fn (Employee $e) => $this->row($e));
    }

    public function find(int $companyId, int $id): ?array
    {
        $e = $this->base($companyId)->whereKey($id)->first(self::COLUMNS);

        return $e ? $this->row($e) : null;
    }
}
