<?php

namespace App\Approvals;

use App\Models\ApprovalStep;
use Illuminate\Database\Eloquent\Model;

/** Un type de demande qui utilise le moteur de validation (congé, jour travaillé, et plus tard permission, mission…). */
abstract class ApprovalHandler
{
    /** Clé stockée dans approval_steps.approvable_type. */
    abstract public function type(): string;

    /** Ressource de droits dont le droit « Modifier » désigne la RH validatrice. */
    abstract public function resource(): string;

    abstract public function find(int $id): ?Model;

    abstract public function employeeId(Model $subject): int;

    /** Résumé affiché dans la boîte de validation. */
    abstract public function label(Model $subject): string;

    abstract public function url(Model $subject): string;

    /** Étapes dans l'ordre : 'manager' (chef ou intérimaire) puis 'hr'. */
    public function chain(Model $subject): array
    {
        return ['manager', 'hr'];
    }

    /** Contrôles avant d'enregistrer une décision (peut lever une ValidationException). */
    public function beforeDecide(Model $subject, ApprovalStep $step, string $decision, array $options): void
    {
    }

    /** Dernière validation obtenue (appelée dans la transaction). */
    abstract public function onApproved(Model $subject, ApprovalStep $last, array $options): void;

    abstract public function onRejected(Model $subject, ApprovalStep $step): void;
}
