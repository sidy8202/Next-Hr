<?php

namespace App\Services\Payroll;

/**
 * Calcul PUR des alertes du tableau de bord paie (aucune base de données).
 *
 * Une alerte ne contient que des NOMBRES et du texte : jamais de nom ni de salaire individuel. Elle porte, si possible, la cible où la traiter ;
 * l'écran n'en fait un lien que si l'utilisateur a le droit de lire cette ressource.
 * Ordre d'affichage : critique, puis à surveiller, puis information ; à gravité égale, l'ordre de la liste ci-dessous.
 */
final class PayrollAlerts
{
    public const CRITICAL = 'critical';
    public const WARNING = 'warning';
    public const INFO = 'info';

    /**
     * @param array<string, mixed> $f
     *   period_status · employees · employees_with_salary · pending_checks · skipped (employés non calculés) · pending_variables
     *   legal_state (missing | to_validate | validated) · legal_total · legal_validated · off_grid · active_loans · installments_count · installments_amount
     *
     * @return array<int, array{key: string, severity: string, count: int, title: string, detail: string, target: ?array{route: string, anchor: string, resource: string, label: string}}>
     */
    public static function build(array $f): array
    {
        $status = (string) ($f['period_status'] ?? 'none');
        $employees = max(0, (int) ($f['employees'] ?? 0));
        $withSalary = max(0, (int) ($f['employees_with_salary'] ?? 0));
        $alerts = [];

        $missing = $employees - $withSalary;

        if ($employees > 0 && $missing > 0) {
            $alerts[] = self::alert('missing_salary', self::CRITICAL, $missing,
                self::plural($missing, 'employé sans salaire en vigueur', 'employés sans salaire en vigueur'),
                'Sans salaire, le bulletin n\'est pas calculé.',
                ['route' => 'payroll.people', 'anchor' => 'salaries', 'resource' => 'employee_salaries', 'label' => 'Saisir les salaires']);
        }

        if (($f['legal_state'] ?? 'validated') === 'missing') {
            $alerts[] = self::alert('legal_missing', self::CRITICAL, 0,
                'Règles légales non chargées',
                'La plateforme n\'a pas encore chargé les règles de paie de votre pays : aucun bulletin ne peut être calculé.',
                null);
        }

        $pendingChecks = max(0, (int) ($f['pending_checks'] ?? 0));

        if ($status === 'calculated' && $pendingChecks > 0) {
            $alerts[] = self::alert('pending_checks', self::CRITICAL, $pendingChecks,
                self::plural($pendingChecks, 'bulletin au net différent du net connu', 'bulletins au net différent du net connu'),
                'À traiter ou à accepter avant la clôture.',
                ['route' => 'payroll.month', 'anchor' => 'month', 'resource' => 'payroll_periods', 'label' => 'Traiter les écarts']);
        }

        $skipped = max(0, (int) ($f['skipped'] ?? 0));

        if ($skipped > 0 && in_array($status, ['calculated', 'closed'], true)) {
            $alerts[] = self::alert('skipped', self::WARNING, $skipped,
                self::plural($skipped, 'employé non calculé', 'employés non calculés'),
                'Une donnée indispensable manque : le détail est dans le traitement du mois.',
                ['route' => 'payroll.month', 'anchor' => 'month', 'resource' => 'payroll_periods', 'label' => 'Voir le détail']);
        }

        $pendingVariables = max(0, (int) ($f['pending_variables'] ?? 0));

        if ($pendingVariables > 0 && $status !== 'closed') {
            $alerts[] = self::alert('pending_variables', self::WARNING, $pendingVariables,
                self::plural($pendingVariables, 'variable du mois à confirmer', 'variables du mois à confirmer'),
                'Une variable proposée ne compte dans la paie qu\'une fois confirmée.',
                ['route' => 'payroll.month', 'anchor' => 'variables', 'resource' => 'payroll_variables', 'label' => 'Confirmer les variables']);
        }

        if (($f['legal_state'] ?? 'validated') === 'to_validate') {
            $total = max(0, (int) ($f['legal_total'] ?? 0));
            $validated = max(0, (int) ($f['legal_validated'] ?? 0));
            $alerts[] = self::alert('legal_to_validate', self::WARNING, $total - $validated,
                'Règles légales : ' . $validated . ' validée' . ($validated > 1 ? 's' : '') . ' sur ' . $total,
                'Les calculs s\'appuient sur des règles que la plateforme n\'a pas encore marquées comme validées.',
                null);
        }

        $offGrid = max(0, (int) ($f['off_grid'] ?? 0));

        if ($offGrid > 0) {
            $alerts[] = self::alert('off_grid', self::INFO, $offGrid,
                self::plural($offGrid, 'salaire de base différent du montant de son échelon', 'salaires de base différents du montant de leur échelon'),
                'À vérifier : écart volontaire, ou grille modifiée depuis la saisie.',
                ['route' => 'payroll.people', 'anchor' => 'salaries', 'resource' => 'employee_salaries', 'label' => 'Voir les salaires']);
        }

        $loans = max(0, (int) ($f['active_loans'] ?? 0));

        if ($loans > 0) {
            $count = max(0, (int) ($f['installments_count'] ?? 0));
            $amount = max(0, (int) ($f['installments_amount'] ?? 0));
            $alerts[] = self::alert('loans', self::INFO, $loans,
                self::plural($loans, 'prêt ou avance en cours', 'prêts ou avances en cours'),
                $count > 0
                    ? 'Échéances de ce mois : ' . PayrollDashboardStats::number($amount) . ' FCFA (' . self::plural($count, 'échéance', 'échéances') . ').'
                    : 'Aucune échéance à prélever ce mois.',
                ['route' => 'payroll.month', 'anchor' => 'loans', 'resource' => 'employee_loans', 'label' => 'Voir les prêts']);
        }

        // Tri stable par gravité (l'ordre d'insertion départage les alertes de même gravité).
        $rank = [self::CRITICAL => 0, self::WARNING => 1, self::INFO => 2];
        $indexed = [];

        foreach ($alerts as $i => $a) {
            $indexed[] = [$rank[$a['severity']], $i, $a];
        }

        usort($indexed, fn ($x, $y) => [$x[0], $x[1]] <=> [$y[0], $y[1]]);

        return array_map(fn ($row) => $row[2], $indexed);
    }

    /** @param ?array<string, string> $target */
    protected static function alert(string $key, string $severity, int $count, string $title, string $detail, ?array $target): array
    {
        return ['key' => $key, 'severity' => $severity, 'count' => $count, 'title' => $title, 'detail' => $detail, 'target' => $target];
    }

    protected static function plural(int $n, string $one, string $many): string
    {
        return $n . ' ' . ($n > 1 ? $many : $one);
    }
}
