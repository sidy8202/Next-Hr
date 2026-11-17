<?php

namespace App\Services\Payroll;

/**
 * Calcul PUR de la progression de l'assistant « Démarrer la paie » (aucune base de données).
 * L'état de chaque étape est recalculé à chaque ouverture à partir de « faits » (comptes et booléens) fournis par SetupFacts.
 *
 * Statuts : done (fait) · partial (en cours, n sur N) · todo (à faire, obligatoire) · optional (facultatif, pas encore fait) · skipped (facultatif ignoré).
 * La progression ne compte QUE les étapes obligatoires.
 */
class SetupProgress
{
    public const DONE = 'done';
    public const PARTIAL = 'partial';
    public const TODO = 'todo';
    public const OPTIONAL = 'optional';
    public const SKIPPED = 'skipped';

    /**
     * @param array<string, mixed> $facts
     *   settings_confirmed, variable_settings_confirmed (bool) · grids, system_items, profiles, employees, employees_with_salary,
     *   employees_with_profile, default_templates, periods_calculated (int) · simulation_done (bool) · skipped (string[])
     *
     * @return array{steps: array<int, array<string, mixed>>, required_total: int, required_done: int, remaining: int, percent: int, complete: bool, next: ?string}
     */
    public static function evaluate(array $facts): array
    {
        $skipped = array_map('strval', (array) ($facts['skipped'] ?? []));
        $steps = [];

        foreach (SetupSteps::all() as $def) {
            $steps[] = [
                'key' => $def['key'],
                'title' => $def['title'],
                'required' => $def['required'],
            ] + self::statusOf($def['key'], $def['required'], $facts, $skipped);
        }

        $required = array_filter($steps, fn ($s) => $s['required']);
        $requiredTotal = count($required);
        $requiredDone = count(array_filter($required, fn ($s) => $s['status'] === self::DONE));
        $next = null;

        foreach ($required as $s) {
            if ($s['status'] !== self::DONE) {
                $next = $s['key'];
                break;
            }
        }

        return [
            'steps' => $steps,
            'required_total' => $requiredTotal,
            'required_done' => $requiredDone,
            'remaining' => $requiredTotal - $requiredDone,
            'percent' => $requiredTotal > 0 ? (int) round($requiredDone * 100 / $requiredTotal) : 100,
            'complete' => $requiredDone === $requiredTotal,
            'next' => $next,
        ];
    }

    /**
     * Statut des règles légales de la plateforme pour le pays de l'entreprise.
     *
     * @param array<int, array<string, mixed>> $rules règles en vigueur (PayrollRule::activeOn)
     *
     * @return array{state: string, total: int, validated: int}  state : missing | to_validate | validated
     */
    public static function legalState(array $rules): array
    {
        $total = count($rules);
        $validated = count(array_filter($rules, fn ($r) => ($r['status'] ?? '') === 'validated'));

        $state = $total === 0 ? 'missing' : ($validated === $total ? 'validated' : 'to_validate');

        return ['state' => $state, 'total' => $total, 'validated' => $validated];
    }

    /**
     * @param array<string, mixed> $facts
     * @param array<int, string> $skipped
     *
     * @return array{status: string, detail: string}
     */
    protected static function statusOf(string $key, bool $required, array $facts, array $skipped): array
    {
        $employees = (int) ($facts['employees'] ?? 0);
        $withSalary = (int) ($facts['employees_with_salary'] ?? 0);
        $withProfile = (int) ($facts['employees_with_profile'] ?? 0);
        $profiles = (int) ($facts['profiles'] ?? 0);

        // Étape facultative ignorée par l'utilisateur : ne compte pas, mais reste réactivable.
        if (! $required && in_array($key, $skipped, true)) {
            return ['status' => self::SKIPPED, 'detail' => 'Étape ignorée.'];
        }

        switch ($key) {
            case 'settings':
                return ! empty($facts['settings_confirmed'])
                    ? self::done('Valeurs confirmées.')
                    : self::open($required, 'Valeurs de départ à vérifier puis à confirmer.');

            case 'variable_settings':
                return ! empty($facts['variable_settings_confirmed'])
                    ? self::done('Valeurs confirmées.')
                    : self::open($required, 'Des valeurs de départ s\'appliquent : confirmez-les ou modifiez-les.');

            case 'grid':
                $grids = (int) ($facts['grids'] ?? 0);

                return $grids > 0
                    ? self::done($grids . ($grids > 1 ? ' grilles actives.' : ' grille active.'))
                    : self::open($required, 'Aucune grille. Créez-en une, ou ignorez l\'étape pour travailler en salaires libres.');

            case 'items':
                // Les rubriques de départ comprennent les rubriques fournies par le moteur (is_system).
                return (int) ($facts['system_items'] ?? 0) > 0
                    ? self::done('Rubriques de départ chargées.')
                    : self::open($required, 'Rubriques de départ non chargées.');

            case 'profiles':
                return $profiles > 0
                    ? self::done($profiles . ($profiles > 1 ? ' modèles actifs.' : ' modèle actif.'))
                    : self::open($required, 'Aucun modèle de traitement.');

            case 'salaries':
                if ($employees === 0) {
                    return self::open($required, 'Aucun employé dans l\'entreprise : ajoutez d\'abord vos employés.');
                }

                return self::counted($withSalary, $employees, 'ont un salaire en vigueur', 'Aucun salaire saisi.');

            case 'assignments':
                if ($employees === 0) {
                    return self::open($required, 'Aucun employé dans l\'entreprise : ajoutez d\'abord vos employés.');
                }

                if ($profiles === 0 && $withProfile === 0) {
                    return self::open($required, 'Créez d\'abord au moins un modèle de traitement.');
                }

                return self::counted($withProfile, $employees, 'ont un modèle en vigueur', 'Aucun modèle affecté.');

            case 'template':
                return (int) ($facts['default_templates'] ?? 0) > 0
                    ? self::done('Un modèle de bulletin par défaut existe.')
                    : self::open($required, 'Pas de modèle par défaut : le bulletin utilise des réglages standard.');

            case 'simulation':
                if (! empty($facts['simulation_done'])) {
                    return self::done('Une simulation de contrôle a été lancée.');
                }

                return self::open($required, $withSalary === 0 ? 'Renseignez d\'abord au moins un salaire.' : 'Aucune simulation lancée.');

            case 'first_run':
                $periods = (int) ($facts['periods_calculated'] ?? 0);

                return $periods > 0
                    ? self::done($periods . ($periods > 1 ? ' mois calculés ou clôturés.' : ' mois calculé ou clôturé.'))
                    : self::open($required, 'Aucun mois calculé.');
        }

        return self::open($required, '');
    }

    /** @return array{status: string, detail: string} */
    protected static function done(string $detail): array
    {
        return ['status' => self::DONE, 'detail' => $detail];
    }

    /** @return array{status: string, detail: string} */
    protected static function open(bool $required, string $detail): array
    {
        return ['status' => $required ? self::TODO : self::OPTIONAL, 'detail' => $detail];
    }

    /** « 9 sur 12 ont un salaire » : fait seulement si tous les employés sont couverts. */
    protected static function counted(int $have, int $total, string $label, string $none): array
    {
        if ($have >= $total) {
            return self::done("Tous les employés ($total sur $total) $label.");
        }

        if ($have <= 0) {
            return ['status' => self::TODO, 'detail' => $none];
        }

        return ['status' => self::PARTIAL, 'detail' => "$have sur $total $label."];
    }
}
