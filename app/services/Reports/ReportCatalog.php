<?php

namespace App\Services\Reports;

/**
 * Catalogue des rapports : chaque rapport est une FICHE (objectif, jeu de données, filtres, lecture, droits requis).
 * Fonctions pures : les droits sont fournis par l'appelant sous forme de fonction ($can('read', 'hr_reports')).
 *
 * « available » : le rapport s'ouvre dans l'écran. « soon » : annoncé, avec le lot qui le livre ; sa phrase d'exemple est illustrative.
 * Un rapport qui parle de saisonnalité porte needs_history_months = 12 : sous 12 mois d'historique, il se tait (voir HistoryGuard).
 */
final class ReportCatalog
{
    public const FAMILIES = [
        'hr' => 'Effectif et mouvements',
        'payroll' => 'Paie et rémunération',
        'leave' => 'Congés, absences et temps de travail',
        'exits' => 'Départs et rotation',
        'contracts' => 'Contrats',
    ];

    /** @return array<string, array<string, mixed>> */
    public static function all(): array
    {
        $hr = [['read', 'hr_reports']];
        $pay = [['read', 'hr_reports'], ['read', 'dashboard_payroll']];

        return [
            'synthesis' => [
                'key' => 'synthesis', 'family' => 'hr', 'status' => 'available', 'title' => 'Synthèse',
                'objective' => 'Voir en un coup d’œil l’effectif, les mouvements de personnel et les alertes.',
                'dataset' => 'Employés, contrats, sorties, congés', 'filters' => ['Période', 'Comparaison', 'Département', 'Poste', 'Contrat', 'Sexe'],
                'reading' => 'Effectif en fin de période, arrivées, départs, rotation, ancienneté.', 'requires' => $hr, 'needs_history_months' => null, 'savable' => true,
            ],
            'workforce' => [
                'key' => 'workforce', 'family' => 'hr', 'status' => 'available', 'title' => 'Effectif',
                'objective' => 'Connaître la composition du personnel.',
                'dataset' => 'Employés, contrats', 'filters' => ['Période', 'Département', 'Poste', 'Contrat', 'Sexe'],
                'reading' => 'Répartition par département, contrat, sexe, ancienneté et âge.', 'requires' => $hr, 'needs_history_months' => null, 'savable' => true,
            ],
            'payroll_cost' => [
                'key' => 'payroll_cost', 'family' => 'payroll', 'status' => 'available', 'title' => 'Masse salariale et coût employeur',
                'objective' => 'Piloter le budget.',
                'dataset' => 'Bulletins de paie calculés', 'filters' => ['Période', 'Comparaison', 'Département', 'Indicateur'],
                'reading' => 'Coût de la période, évolution mois par mois, effet des embauches et des départs, répartition par service.',
                'requires' => $pay, 'needs_history_months' => null, 'savable' => true,
            ],
            'grid_gaps' => [
                'key' => 'grid_gaps', 'family' => 'payroll', 'status' => 'available', 'title' => 'Écarts à la grille',
                'objective' => 'Vérifier la conformité.',
                'dataset' => 'Salaires en vigueur, grilles salariales, catégories des postes', 'filters' => ['Date (fin de période)', 'Département', 'Catégorie'],
                'reading' => 'Salaires sous le minimum ou au-dessus du maximum de leur catégorie, écarts à l’échelon.',
                'requires' => $pay, 'needs_history_months' => null, 'savable' => true,
            ],
            'leave_seasonality' => self::soon('leave_seasonality', 'leave', 'Saisonnalité des congés', 'Prévoir les remplacements.', 'Congés approuvés', ['Période', 'Département', 'Type de congé'], '« Juillet et août concentrent 38 % des jours de congé, 1,6 fois la moyenne mensuelle. »', '18b', 12),
            'departure_seasonality' => self::soon('departure_seasonality', 'exits', 'Saisonnalité des départs', 'Savoir quand beaucoup partent.', 'Sorties d’effectif', ['Période', 'Motif', 'Département'], '« Les départs culminent en janvier : 3 mois sur 24 dépassent 1,5 fois la moyenne. »', '19c', 12),
            'early_departures' => self::soon('early_departures', 'exits', 'Rotation et départs précoces', 'Repérer un problème d’intégration.', 'Sorties d’effectif', ['Ancienneté', 'Département'], '« 40 % des départs surviennent avant 12 mois d’ancienneté. »', '19c', null),
            'high_leave_balances' => self::soon('high_leave_balances', 'leave', 'Soldes de congés élevés', 'Limiter la dette de congés et l’épuisement.', 'Soldes de congés', ['Seuil de jours', 'Département'], '« 12 employés dépassent 30 jours, soit environ 2,1 millions FCFA de congés dus. »', '18b', null),
            'absenteeism' => self::soon('absenteeism', 'leave', 'Absentéisme', 'Détecter les schémas.', 'Absences', ['Période', 'Justifiée ou non'], '« Les absences non justifiées sont 2 fois plus fréquentes le lundi. »', '18b', null),
            'contracts_expiring' => self::soon('contracts_expiring', 'contracts', 'Contrats à échéance', 'Décider à temps.', 'Contrats', ['Horizon (30, 60, 90 jours)'], '« 7 contrats arrivent à échéance d’ici 90 jours, dont 4 le même mois. »', '18b', null),
            'overtime' => self::soon('overtime', 'leave', 'Heures supplémentaires', 'Repérer la sur-sollicitation.', 'Heures supplémentaires', ['Période', 'Département'], '« Le département Logistique concentre 55 % des heures supplémentaires. »', '18b', null),
        ];
    }

    /** @return ?array<string, mixed> */
    public static function find(string $key): ?array
    {
        return self::all()[$key] ?? null;
    }

    /** @return array<int, string> clés des rapports ouverts dans l'écran */
    public static function availableKeys(): array
    {
        return array_keys(array_filter(self::all(), fn ($f) => $f['status'] === 'available'));
    }

    /** @return array<int, string> clés des rapports qu'on peut enregistrer comme « mon rapport » */
    public static function savableKeys(): array
    {
        return array_keys(array_filter(self::all(), fn ($f) => $f['status'] === 'available' && $f['savable']));
    }

    /**
     * Le rapport est-il ouvrable ? Tous les droits requis doivent être accordés.
     *
     * @param callable(string, string): bool $can
     */
    public static function canOpen(string $key, callable $can): bool
    {
        $fiche = self::find($key);

        if ($fiche === null || $fiche['status'] !== 'available') {
            return false;
        }

        foreach ($fiche['requires'] as [$action, $resource]) {
            if (! $can($action, $resource)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Rapports disponibles et ouvrables par cet utilisateur.
     *
     * @param callable(string, string): bool $can
     *
     * @return array<string, array<string, mixed>>
     */
    public static function visible(callable $can): array
    {
        return array_filter(self::all(), fn ($f) => self::canOpen($f['key'], $can));
    }

    /**
     * @param array<int, string> $filters
     *
     * @return array<string, mixed>
     */
    private static function soon(string $key, string $family, string $title, string $objective, string $dataset, array $filters, string $example, string $lot, ?int $history): array
    {
        return [
            'key' => $key, 'family' => $family, 'status' => 'soon', 'title' => $title, 'objective' => $objective, 'dataset' => $dataset, 'filters' => $filters,
            'reading' => null, 'example' => $example, 'lot' => $lot, 'requires' => [['read', 'hr_reports']], 'needs_history_months' => $history, 'savable' => false,
        ];
    }
}
