<?php

namespace App\Services\Reports;

use App\Services\Payroll\PayrollBreakdown;

/**
 * Calculs PURS du rapport « Écarts à la grille » : classement de chaque salaire par rapport à la catégorie du poste,
 * regroupement par service et par catégorie avec masquage des petits groupes, phrases d'interprétation.
 *
 * Confidentialité : le rapport ne donne que des NOMBRES, jamais un nom ni un montant individuel. Un groupe de moins de
 * MIN_GROUP personnes comparées n'est jamais affiché seul (regroupé en « Autres », ou masqué s'il reste trop petit).
 * Aucune base de données.
 */
final class GridGapReport
{
    public const MIN_GROUP = PayrollBreakdown::MIN_GROUP;

    public const NO_CATEGORY = 'Sans catégorie';

    /**
     * Classe un salaire de base par rapport à la catégorie du poste.
     *
     * @param array{base: int, step_amount: ?int} $employee step_amount : montant de l'échelon auquel la ligne de salaire est rattachée, s'il y en a un
     * @param ?array{min: int, max: int} $grade fourchette de la catégorie du poste (plus petit et plus grand échelon), null si le poste n'en a pas
     *
     * @return string no_grade | below | above | step_mismatch | ok
     */
    public static function classify(array $employee, ?array $grade): string
    {
        if ($grade === null) {
            return 'no_grade';
        }

        if ($employee['base'] < $grade['min']) {
            return 'below';
        }

        if ($employee['base'] > $grade['max']) {
            return 'above';
        }

        return $employee['step_amount'] !== null && $employee['base'] !== $employee['step_amount'] ? 'step_mismatch' : 'ok';
    }

    /**
     * @param array<int, array{department: ?string, category: ?string, status: string}> $rows un élément par employé avec salaire en vigueur
     * @param int $noSalary employés en poste sans salaire en vigueur
     *
     * @return array{
     *     too_small: bool, compared: int, below: int, above: int, step_mismatch: int, ok: int, no_grade: int, no_salary: int,
     *     departments: array{items: array<int, array<string, mixed>>, hidden_people: int, hidden_groups: int},
     *     categories: array{items: array<int, array<string, mixed>>, hidden_people: int, hidden_groups: int}
     * }
     */
    public static function summarize(array $rows, int $noSalary): array
    {
        $count = ['below' => 0, 'above' => 0, 'step_mismatch' => 0, 'ok' => 0, 'no_grade' => 0];
        $dept = [];
        $cat = [];

        foreach ($rows as $r) {
            $count[$r['status']]++;

            if ($r['status'] === 'no_grade') {
                continue;
            }

            $d = trim((string) ($r['department'] ?? '')) === '' ? PayrollBreakdown::NO_DEPARTMENT : trim((string) $r['department']);
            $c = trim((string) ($r['category'] ?? '')) === '' ? self::NO_CATEGORY : trim((string) $r['category']);

            self::add($dept, $d, $r['status']);
            self::add($cat, $c, $r['status']);
        }

        $compared = $count['below'] + $count['above'] + $count['step_mismatch'] + $count['ok'];
        $tooSmall = $compared < self::MIN_GROUP;

        return $count + [
            'too_small' => $tooSmall, 'compared' => $compared, 'no_salary' => $noSalary,
            'departments' => $tooSmall ? self::empty() : self::mask($dept, 'Autres services'),
            'categories' => $tooSmall ? self::empty() : self::mask($cat, 'Autres catégories'),
        ];
    }

    /**
     * Phrases d'interprétation, chacune avec sa règle chiffrée.
     *
     * @param array<string, mixed> $s résultat de summarize()
     * @param array<string, int> $thresholds résultat de ReportThresholds::merge()
     *
     * @return array<int, array{text: string, rule: string}>
     */
    public static function sentences(array $s, array $thresholds): array
    {
        $out = [];

        if ($s['too_small']) {
            return [['text' => 'Moins de ' . self::MIN_GROUP . ' personnes sont comparées : les chiffres ne sont pas affichés, pour ne pas révéler un salaire individuel.', 'rule' => 'Un groupe de moins de ' . self::MIN_GROUP . ' personnes est masqué.']];
        }

        $compared = $s['compared'];
        $alert = $thresholds['grid_gap_alert_percent'];
        $pct = self::pct($s['below'], $compared);

        if ($s['below'] === 0) {
            $out[] = ['text' => 'Aucun employé n\'est payé sous le minimum de sa catégorie.', 'rule' => 'Salaire de base inférieur au plus petit échelon de la catégorie du poste.'];
        } else {
            $txt = self::count($s['below'], 'employé est payé', 'employés sont payés') . ' sous le minimum de leur catégorie, soit ' . $pct . ' % des ' . $compared . ' comparés.';
            $txt = $s['below'] === 1 ? str_replace('de leur catégorie', 'de sa catégorie', $txt) : $txt;

            if ($pct >= $alert) {
                $txt .= ' Cette part dépasse le seuil de ' . $alert . ' % fixé pour ce rapport.';
            }

            $out[] = ['text' => $txt, 'rule' => 'Salaire de base inférieur au plus petit échelon de la catégorie du poste ; part préoccupante à partir de ' . $alert . ' %.'];
        }

        if ($s['above'] > 0) {
            $out[] = ['text' => self::count($s['above'], 'employé est payé', 'employés sont payés') . ' au-dessus du maximum de ' . ($s['above'] === 1 ? 'sa' : 'leur') . ' catégorie.', 'rule' => 'Salaire de base supérieur au plus grand échelon de la catégorie du poste.'];
        }

        if ($s['step_mismatch'] > 0) {
            $out[] = ['text' => self::count($s['step_mismatch'], 'employé a', 'employés ont') . ' un salaire de base différent du montant de ' . ($s['step_mismatch'] === 1 ? 'son' : 'leur') . ' échelon, tout en restant dans la fourchette de la catégorie.', 'rule' => 'Salaire de base différent du montant de l\'échelon rattaché à la ligne de salaire.'];
        }

        if ($s['no_grade'] > 0) {
            $out[] = ['text' => self::count($s['no_grade'], 'employé occupe', 'employés occupent') . ' un poste sans catégorie salariale : ' . ($s['no_grade'] === 1 ? 'il n\'est pas comparé' : 'ils ne sont pas comparés') . '.', 'rule' => 'Poste sans catégorie : pas de minimum de référence.'];
        }

        if ($s['no_salary'] > 0) {
            $out[] = ['text' => self::count($s['no_salary'], 'employé en poste n\'a', 'employés en poste n\'ont') . ' aucun salaire en vigueur à cette date.', 'rule' => 'Aucune ligne de salaire datée couvrant la date du rapport.'];
        }

        return $out;
    }

    /**
     * Masque les groupes de moins de MIN_GROUP personnes comparées : regroupés en « Autres » s'ils atteignent ensemble le minimum, sinon cachés.
     *
     * @param array<string, array{count: int, below: int, above: int, step_mismatch: int}> $groups
     *
     * @return array{items: array<int, array<string, mixed>>, hidden_people: int, hidden_groups: int}
     */
    public static function mask(array $groups, string $otherLabel): array
    {
        $items = [];
        $small = ['count' => 0, 'below' => 0, 'above' => 0, 'step_mismatch' => 0];
        $smallGroups = 0;

        foreach ($groups as $name => $g) {
            if ($g['count'] >= self::MIN_GROUP) {
                $items[] = ['name' => (string) $name] + $g;
            } else {
                foreach ($small as $k => $_) {
                    $small[$k] += $g[$k];
                }

                $smallGroups++;
            }
        }

        $hiddenPeople = 0;
        $hiddenGroups = 0;

        if ($smallGroups > 0) {
            if ($small['count'] >= self::MIN_GROUP) {
                $items[] = ['name' => $otherLabel] + $small;
            } else {
                $hiddenPeople = $small['count'];
                $hiddenGroups = $smallGroups;
            }
        }

        usort($items, fn ($a, $b) => [$b['count'], $a['name']] <=> [$a['count'], $b['name']]);

        return ['items' => $items, 'hidden_people' => $hiddenPeople, 'hidden_groups' => $hiddenGroups];
    }

    /** @param array<string, array{count: int, below: int, above: int, step_mismatch: int}> $bucket */
    private static function add(array &$bucket, string $name, string $status): void
    {
        $bucket[$name] ??= ['count' => 0, 'below' => 0, 'above' => 0, 'step_mismatch' => 0];
        $bucket[$name]['count']++;

        if ($status !== 'ok') {
            $bucket[$name][$status]++;
        }
    }

    /** @return array{items: array, hidden_people: int, hidden_groups: int} */
    private static function empty(): array
    {
        return ['items' => [], 'hidden_people' => 0, 'hidden_groups' => 0];
    }

    private static function count(int $n, string $one, string $many): string
    {
        return $n . ' ' . ($n > 1 ? $many : $one);
    }

    /** Pourcentage entier à la demi-unité supérieure. */
    private static function pct(int $part, int $total): int
    {
        return $total > 0 ? intdiv($part * 200 + $total, 2 * $total) : 0;
    }
}
