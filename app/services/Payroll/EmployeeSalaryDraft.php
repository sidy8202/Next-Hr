<?php

namespace App\Services\Payroll;

/**
 * Décide, à la création d'un employé, QUELLE ligne de salaire datée créer. Fonction pure : le formulaire fournit les faits et les droits.
 *
 * Règles :
 *  - poste avec catégorie : on choisit un ÉCHELON (le montant vient de la grille). Un montant libre hors échelon n'est permis qu'avec le droit
 *    de modifier les salaires, et l'enregistrement le signale (« override ») pour la journalisation ;
 *  - poste sans catégorie : montant libre, comme avant ;
 *  - net : seulement avec le droit de modifier les salaires. « guarantee » = net garanti (le moteur calcule le sursalaire) ;
 *    « reference » (défaut) = simple référence du contrôle, sans garantie. Sans net, la paie de l'employé n'est pas vérifiée.
 */
final class EmployeeSalaryDraft
{
    /**
     * @param array{
     *     position_has_grade: bool,
     *     step: ?array{id: int, grade_id: int, amount: int},
     *     free_base: int,
     *     can_override: bool,
     *     net: ?int,
     *     net_mode: ?string,
     *     can_set_net: bool
     * } $in
     *
     * @return array{ok: bool, error: ?string, field: ?string, create: bool, source: string, base: int, grade_id: ?int, step_id: ?int, mode: string, target_net: ?int, override: bool}
     */
    public static function resolve(array $in): array
    {
        $out = ['ok' => true, 'error' => null, 'field' => null, 'create' => false, 'source' => 'none', 'base' => 0, 'grade_id' => null, 'step_id' => null, 'mode' => 'gross', 'target_net' => null, 'override' => false];

        $step = $in['step'] ?? null;
        $free = max(0, (int) ($in['free_base'] ?? 0));

        if (! empty($in['position_has_grade'])) {
            if ($step !== null) {
                $out['source'] = 'grid';
                $out['base'] = (int) $step['amount'];
                $out['grade_id'] = (int) $step['grade_id'];
                $out['step_id'] = (int) $step['id'];
            } elseif ($free > 0 && ! empty($in['can_override'])) {
                $out['source'] = 'free';
                $out['base'] = $free;
                $out['override'] = true;
            } elseif ($free > 0) {
                return self::fail($out, 'form.salary_step_id', 'Ce poste a une catégorie salariale : choisissez un échelon. Un montant libre est réservé à ceux qui peuvent modifier les salaires.');
            } else {
                return self::fail($out, 'form.salary_step_id', 'Choisissez un échelon pour ce poste.');
            }
        } else {
            $out['source'] = 'free';
            $out['base'] = $free;
        }

        $out['create'] = $out['base'] > 0;

        $net = ! empty($in['can_set_net']) && ($in['net'] ?? null) !== null && (int) $in['net'] > 0 ? (int) $in['net'] : null;

        if ($net !== null && $out['create']) {
            $out['target_net'] = $net;
            $out['mode'] = ($in['net_mode'] ?? 'reference') === 'guarantee' ? 'net_target' : 'gross';
        }

        return $out;
    }

    /** @param array<string, mixed> $out */
    private static function fail(array $out, string $field, string $message): array
    {
        return ['ok' => false, 'error' => $message, 'field' => $field] + $out;
    }
}
