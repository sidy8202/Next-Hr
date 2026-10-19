<?php

namespace App\Services;

use App\Models\Department;
use App\Models\Employee;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class DepartmentService
{
    /**
     * Crée ou met à jour un département avec (facultativement) son chef, ses nouveaux membres et ses postes.
     * Tout est enregistré dans UNE transaction : tout ou rien. Les données doivent être validées AVANT.
     *
     * $data : name, code, is_active, head_id?, member_ids?, positions?
     */
    public function save(?Department $department, array $data): Department
    {
        return DB::transaction(function () use ($department, $data) {
            $department ??= new Department();
            $oldHeadId = $department->head_id;

            $department->fill([
                'name' => trim($data['name']),
                'code' => Str::upper(trim($data['code'])),
                'is_active' => (bool) ($data['is_active'] ?? true),
            ])->save(); // company_id : rempli automatiquement par le trait BelongsToCompany

            $headId = ! empty($data['head_id']) ? (int) $data['head_id'] : null;

            $memberIds = collect($data['member_ids'] ?? [])
                ->map(fn ($id) => (int) $id)
                ->reject(fn ($id) => $id === $headId)
                ->unique()->values()->all();

            // Membres rattachés (déplacés depuis un autre département le cas échéant).
            // Employee passe par le filtre automatique : impossible de toucher une autre entreprise.
            if ($memberIds) {
                $changes = ['department_id' => $department->id];
                if ($headId) {
                    $changes['manager_id'] = $headId;
                }

                Employee::whereIn('id', $memberIds)->where('department_id', '!=', $department->id)->update($changes);
            }

            if ($headId) {
                // Un employé n'est chef que d'un seul département.
                Department::where('head_id', $headId)->where('id', '!=', $department->id)->update(['head_id' => null]);

                Employee::whereKey($headId)->update(['department_id' => $department->id]);

                // Les employés qui dépendaient de l'ancien chef dépendent du nouveau.
                if ($oldHeadId && $oldHeadId !== $headId) {
                    Employee::where('department_id', $department->id)
                        ->where('manager_id', $oldHeadId)->where('id', '!=', $headId)
                        ->update(['manager_id' => $headId]);
                }

                // Les employés sans manager reçoivent le chef.
                Employee::where('department_id', $department->id)
                    ->whereNull('manager_id')->where('id', '!=', $headId)
                    ->update(['manager_id' => $headId]);

                $department->update(['head_id' => $headId]);
            } elseif (array_key_exists('head_id', $data) && $oldHeadId) {
                $department->update(['head_id' => null]); // chef retiré
            }

            foreach ($data['positions'] ?? [] as $title) {
                $title = trim((string) $title);

                if ($title !== '') {
                    $department->positions()->firstOrCreate(['title' => $title]);
                }
            }

            return $department->refresh();
        });
    }

    /** Raison pour laquelle le département ne peut pas être supprimé (ou null s'il le peut). */
    public function deletionBlocker(Department $department): ?string
    {
        if ($department->employees()->withTrashed()->exists()) {
            return "« {$department->name} » contient des employés : désactivez-le à la place.";
        }

        if ($department->positions()->exists()) {
            return "« {$department->name} » contient des postes : désactivez-le à la place.";
        }

        return null;
    }
}
