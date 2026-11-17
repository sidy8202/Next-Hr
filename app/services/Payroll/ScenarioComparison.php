<?php

namespace App\Services\Payroll;

/**
 * Tableau comparatif des scénarios du simulateur : une colonne par scénario, une ligne par indicateur.
 * Le premier scénario calculé sert de base ; chaque autre colonne indique son écart avec lui. Aucune base de données.
 */
final class ScenarioComparison
{
    /** Indicateurs de HypotheticalPay::summarize() affichés, dans l'ordre. */
    public const ROWS = [
        'base' => 'Salaire de base',
        'gross' => 'Brut',
        'social' => 'Cotisations sociales (part salarié)',
        'its' => 'Impôt sur salaire (ITS)',
        'net' => 'Net du contrat',
        'supplement' => 'Sursalaire calculé',
        'employer_cost' => 'Coût employeur (mois)',
        'annual_cost' => 'Coût employeur (12 mois)',
    ];

    /**
     * @param array<int, array{label: string, summary: ?array<string, mixed>, error: ?string}> $results un élément par scénario ; summary vaut null s'il n'est pas calculé
     *
     * @return array{baseline: ?int, rows: array<int, array{key: string, label: string, cells: array<int, array{value: ?string, delta: string, direction: string}>}>}
     */
    public static function table(array $results): array
    {
        $baseline = null;

        foreach ($results as $i => $r) {
            if (($r['summary'] ?? null) !== null) {
                $baseline = $i;
                break;
            }
        }

        $rows = [];

        foreach (self::ROWS as $key => $label) {
            $cells = [];

            foreach ($results as $i => $r) {
                $summary = $r['summary'] ?? null;

                if ($summary === null) {
                    $cells[] = ['value' => null, 'delta' => '', 'direction' => 'none'];

                    continue;
                }

                $value = (int) $summary[$key];

                if ($i === $baseline) {
                    $cells[] = ['value' => PayrollDashboardStats::number($value), 'delta' => '', 'direction' => 'base'];

                    continue;
                }

                $delta = PayrollDashboardStats::delta($value, (int) $results[$baseline]['summary'][$key]);

                $cells[] = ['value' => PayrollDashboardStats::number($value), 'delta' => PayrollDashboardStats::deltaShort($delta), 'direction' => $delta['direction'] ?? 'none'];
            }

            $rows[] = ['key' => $key, 'label' => $label, 'cells' => $cells];
        }

        return ['baseline' => $baseline, 'rows' => $rows];
    }

    /**
     * Phrases sous le tableau pour les scénarios partis d'un brut ou d'un net : cible atteinte, dépassée de quelques FCFA, ou impossible.
     *
     * @param array<int, array{label: string, summary: ?array<string, mixed>, error: ?string}> $results
     *
     * @return array<int, array{label: string, level: string, text: string}> level = ok | warning
     */
    public static function solveNotes(array $results): array
    {
        $notes = [];

        foreach ($results as $r) {
            $summary = $r['summary'] ?? null;
            $solve = $summary['solve'] ?? null;

            if ($solve === null) {
                continue;
            }

            $what = $solve['kind'] === 'gross' ? 'brut' : 'net';
            $target = PayrollDashboardStats::number((int) $solve['target']);
            $base = PayrollDashboardStats::number((int) $summary['base']);
            $gap = (int) $solve['gap'];

            if (! $solve['reached']) {
                $level = 'warning';
                $text = "Le {$what} voulu ({$target} FCFA) ne peut pas être atteint : le plus proche est " . PayrollDashboardStats::number((int) $summary[$solve['kind'] === 'gross' ? 'gross' : 'net']) . " FCFA, avec un salaire de base de {$base} FCFA. Vérifiez les rubriques du modèle de traitement.";
            } elseif ($gap > 0 && (int) $summary['base'] === 0) {
                $level = 'warning';
                $text = "Même avec un salaire de base nul, le {$what} dépasse le {$what} voulu ({$target} FCFA) de " . PayrollDashboardStats::number($gap) . ' FCFA : ce sont les rubriques du modèle de traitement qui le portent.';
            } elseif ($gap > 0) {
                $level = 'ok';
                $text = "{$what} voulu ({$target} FCFA) atteint, avec " . PayrollDashboardStats::number($gap) . " FCFA de plus (arrondis, ou tranches de l'impôt). Salaire de base nécessaire : {$base} FCFA.";
            } else {
                $level = 'ok';
                $text = "{$what} voulu ({$target} FCFA) atteint exactement. Salaire de base nécessaire : {$base} FCFA.";
            }

            $notes[] = ['label' => $r['label'], 'level' => $level, 'text' => ucfirst($text)];
        }

        return $notes;
    }
}
