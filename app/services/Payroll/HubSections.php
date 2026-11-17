<?php

namespace App\Services\Payroll;

/**
 * Les trois pôles de la paie et leurs sections (accordéons). Fichier de correspondance PUR (aucune base de données) :
 * c'est ici que l'on sait quel écran existant s'ouvre dans quelle section, et où redirigent les anciennes routes.
 *
 * Chaque section charge un écran existant, imbriqué dans l'accordéon, seulement à la première ouverture.
 * 'alias' = nom Livewire de l'écran (dossier en minuscules, classe en kebab-case) ; 'resource' = droit de lecture requis.
 */
class HubSections
{
    public const MONTH = 'payroll.month';
    public const PEOPLE = 'payroll.people';
    public const CONFIG = 'payroll.config';

    /** @return array<int, array{key: string, title: string, hint: string, alias: string, resource: string}> */
    public static function month(): array
    {
        return [
            ['key' => 'month', 'title' => 'Traitement du mois', 'hint' => 'Calculer la paie, contrôler les nets, clôturer, puis envoyer les bulletins.', 'alias' => 'payroll.payroll-month', 'resource' => 'payroll_periods'],
            ['key' => 'variables', 'title' => 'Variables du mois', 'hint' => 'Absences, mises à pied, heures supplémentaires et jours travaillés à confirmer avant le calcul.', 'alias' => 'payroll.monthly-variables', 'resource' => 'payroll_variables'],
            ['key' => 'loans', 'title' => 'Prêts et avances', 'hint' => 'Les échéances retenues sur la paie du mois.', 'alias' => 'payroll.employee-loans', 'resource' => 'employee_loans'],
            ['key' => 'simulation', 'title' => 'Simulation de paie', 'hint' => 'Calculer un bulletin pour un employé sans rien enregistrer, et le comparer au net connu.', 'alias' => 'payroll.payroll-simulation', 'resource' => 'payroll_simulation'],
        ];
    }

    /** @return array<int, array{key: string, title: string, hint: string, alias: string, resource: string}> */
    public static function people(): array
    {
        return [
            ['key' => 'salaries', 'title' => 'Salaires des employés', 'hint' => 'Le salaire daté de chaque employé, son net connu et son mode (brut fixé ou net garanti).', 'alias' => 'payroll.employee-salaries', 'resource' => 'employee_salaries'],
            ['key' => 'assignments', 'title' => 'Affectation des modèles', 'hint' => 'Donner à chaque employé son modèle de traitement, avec une date d\'effet.', 'alias' => 'payroll.pay-assignments', 'resource' => 'pay_profiles'],
            ['key' => 'bonuses', 'title' => 'Primes attribuées', 'hint' => 'Les primes et retenues propres à un employé, en plus de son modèle.', 'alias' => 'payroll.employee-pay-items', 'resource' => 'employee_pay_items'],
        ];
    }

    /** @return array<int, array{key: string, title: string, hint: string, alias: string, resource: string}> */
    public static function config(): array
    {
        return [
            ['key' => 'settings', 'title' => 'Paramètres de paie', 'hint' => 'Pays, convention collective, base de jours, heures du mois et arrondi.', 'alias' => 'payroll.settings', 'resource' => 'payroll_settings'],
            ['key' => 'packages', 'title' => 'Démarrage rapide par secteur', 'hint' => 'Créer en un clic les rubriques et modèles de départ de votre secteur.', 'alias' => 'payroll.packages', 'resource' => 'payroll_settings'],
            ['key' => 'items', 'title' => 'Rubriques de paie', 'hint' => 'Les lignes du bulletin : salaire de base, primes, retenues.', 'alias' => 'payroll.pay-items', 'resource' => 'pay_items'],
            ['key' => 'profiles', 'title' => 'Modèles de traitement', 'hint' => 'Les rubriques qui s\'appliquent à une catégorie de personnel.', 'alias' => 'payroll.pay-profiles', 'resource' => 'pay_profiles'],
            ['key' => 'grids', 'title' => 'Grilles salariales', 'hint' => 'Catégories et échelons de votre convention (facultatif : salaires libres possibles).', 'alias' => 'payroll.salary-grids', 'resource' => 'salary_grids'],
            ['key' => 'templates', 'title' => 'Modèles de bulletin', 'hint' => 'L\'apparence du bulletin : codes, regroupement, pied de page.', 'alias' => 'payroll.payslip-templates', 'resource' => 'payroll_settings'],
            ['key' => 'variable-settings', 'title' => 'Réglages des variables', 'hint' => 'Arrivées et départs en cours de mois, jours de repos, majorations des heures supplémentaires.', 'alias' => 'payroll.variable-settings', 'resource' => 'payroll_settings'],
        ];
    }

    /** @return array<string, array<int, array{key: string, title: string, hint: string, alias: string, resource: string}>> sections de chaque pôle, par nom de route */
    public static function all(): array
    {
        return [self::MONTH => self::month(), self::PEOPLE => self::people(), self::CONFIG => self::config()];
    }

    /**
     * Anciennes routes de la paie : elles redirigent vers le bon pôle, section ouverte.
     *
     * @return array<string, array{route: string, anchor: string}>
     */
    public static function redirects(): array
    {
        return [
            'payroll.variables' => ['route' => self::MONTH, 'anchor' => 'variables'],
            'payroll.loans' => ['route' => self::MONTH, 'anchor' => 'loans'],
            'payroll.simulation' => ['route' => self::MONTH, 'anchor' => 'simulation'],
            'payroll.salaries' => ['route' => self::PEOPLE, 'anchor' => 'salaries'],
            'payroll.assignments' => ['route' => self::PEOPLE, 'anchor' => 'assignments'],
            'payroll.bonuses' => ['route' => self::PEOPLE, 'anchor' => 'bonuses'],
            'payroll.settings' => ['route' => self::CONFIG, 'anchor' => 'settings'],
            'payroll.packages' => ['route' => self::CONFIG, 'anchor' => 'packages'],
            'payroll.items' => ['route' => self::CONFIG, 'anchor' => 'items'],
            'payroll.profiles' => ['route' => self::CONFIG, 'anchor' => 'profiles'],
            'payroll.grids' => ['route' => self::CONFIG, 'anchor' => 'grids'],
            'payroll.templates' => ['route' => self::CONFIG, 'anchor' => 'templates'],
            'payroll.variable-settings' => ['route' => self::CONFIG, 'anchor' => 'variable-settings'],
        ];
    }

    /** @return array{route: string, anchor: string}|null */
    public static function redirectFor(string $routeName): ?array
    {
        return self::redirects()[$routeName] ?? null;
    }

    public static function hasSection(string $hubRoute, string $key): bool
    {
        foreach (self::all()[$hubRoute] ?? [] as $section) {
            if ($section['key'] === $key) {
                return true;
            }
        }

        return false;
    }
}
