<?php

namespace App\Services\Payroll;

/**
 * Les étapes de l'assistant « Démarrer la paie » : texte d'aide, exemple, écran vers lequel mène le bouton.
 * Fichier de correspondance « étape → écran » : au lot 16a-3 (pôles à accordéons), seul ce fichier change pour viser les pôles.
 * Aucune dépendance à la base : testable sans Laravel.
 */
class SetupSteps
{
    /**
     * @return array<int, array<string, mixed>> clés : key, title, required, why, example, route, route_label, resource, confirmable ; facultatives : anchor (section du pôle à ouvrir), extra_route, extra_anchor, extra_label, extra_resource (second bouton)
     */
    public static function all(): array
    {
        return [
            [
                'key' => 'settings', 'title' => 'Paramètres de paie', 'required' => true, 'confirmable' => true,
                'why' => "Pays, convention collective, base de jours, heures du mois et arrondi : ces choix servent à tous les calculs. Les taux légaux (INPS, AMO, ITS) ne se règlent pas ici : la plateforme les maintient.",
                'example' => 'Mali, base de 30 jours, 173,33 heures (40 h par semaine), arrondi au franc le plus proche.',
                'route' => 'payroll.config', 'anchor' => 'settings', 'route_label' => 'Ouvrir les paramètres', 'resource' => 'payroll_settings',
            ],
            [
                'key' => 'variable_settings', 'title' => 'Réglages du mois', 'required' => false, 'confirmable' => true,
                'why' => "Que se passe-t-il quand un salarié arrive ou part en cours de mois ? Quels sont vos jours de repos ? Des valeurs de départ sont proposées : elles sont visibles et modifiables, à confirmer. Aucun taux de majoration des heures supplémentaires n'est prérempli.",
                'example' => "Arrivée le 3 avec un seuil à 5 : mois entier. Arrivée le 12 : les 11 premiers jours sont retirés, soit 19/30 du salaire.",
                'route' => 'payroll.config', 'anchor' => 'variable-settings', 'route_label' => 'Ouvrir les réglages', 'resource' => 'payroll_settings',
            ],
            [
                'key' => 'grid', 'title' => 'Grille salariale (ou salaires libres)', 'required' => false, 'confirmable' => false,
                'why' => "Si votre convention fixe des catégories et des montants, saisissez-les ici. Sinon, choisissez « salaires libres » : vous saisirez chaque salaire à la main, sans grille.",
                'example' => 'Catégorie E8B, échelon 1 : le montant est celui de votre convention, à saisir une fois.',
                'route' => 'payroll.config', 'anchor' => 'grids', 'route_label' => 'Ouvrir les grilles', 'resource' => 'salary_grids',
            ],
            [
                'key' => 'items', 'title' => 'Rubriques de paie', 'required' => true, 'confirmable' => false,
                'why' => "Les rubriques sont les lignes du bulletin : salaire de base, sursalaire, primes, retenues. Chargez d'abord les rubriques de départ, puis ajoutez les vôtres, ou gagnez du temps avec le « Démarrage rapide » de votre secteur. Aucun montant n'est prérempli et leur qualification (cotisable, imposable) est à confirmer par votre comptable.",
                'example' => 'Indemnité de transport : un gain, soumis ou non aux cotisations, imposable ou non, au montant que vous fixez.',
                'route' => 'payroll.config', 'anchor' => 'items', 'route_label' => 'Ouvrir les rubriques', 'resource' => 'pay_items',
                'extra_route' => 'payroll.config', 'extra_anchor' => 'packages', 'extra_label' => 'Démarrage rapide par secteur', 'extra_resource' => 'payroll_settings',
            ],
            [
                'key' => 'profiles', 'title' => 'Modèles de traitement', 'required' => true, 'confirmable' => false,
                'why' => "Un modèle regroupe les rubriques qui s'appliquent à une catégorie de personnel. Vous l'affectez ensuite à chaque employé, au lieu de tout saisir employé par employé.",
                'example' => 'Modèle « Employés » : indemnité de transport et indemnité spéciale. Modèle « Cadres » : les mêmes plus l\'indemnité de fonction.',
                'route' => 'payroll.config', 'anchor' => 'profiles', 'route_label' => 'Ouvrir les modèles', 'resource' => 'pay_profiles',
            ],
            [
                'key' => 'salaries', 'title' => 'Salaires des employés', 'required' => true, 'confirmable' => false,
                'why' => "Chaque employé a besoin d'un salaire daté. Vous pouvez les initialiser depuis les contrats, puis importer les nets connus : ils serviront de référence pour contrôler le calcul.",
                'example' => 'Employé 001 : salaire de base et sursalaire à partir du 1er janvier, net connu saisi pour le contrôle.',
                'route' => 'payroll.people', 'anchor' => 'salaries', 'route_label' => 'Ouvrir les salaires', 'resource' => 'employee_salaries',
            ],
            [
                'key' => 'assignments', 'title' => 'Affectation des modèles', 'required' => true, 'confirmable' => false,
                'why' => "Chaque employé doit avoir un modèle de traitement en vigueur. L'écran permet d'affecter un modèle à plusieurs employés d'un coup, avec une date d'effet.",
                'example' => 'Les 9 employés du département Ventes reçoivent le modèle « Employés » à partir du 1er du mois.',
                'route' => 'payroll.people', 'anchor' => 'assignments', 'route_label' => 'Ouvrir les affectations', 'resource' => 'pay_profiles',
            ],
            [
                'key' => 'template', 'title' => 'Modèle de bulletin et en-tête', 'required' => false, 'confirmable' => false,
                'why' => "Choisissez l'apparence du bulletin (codes des rubriques, regroupement, pied de page). Sans modèle par défaut, le bulletin utilise des réglages standard et l'en-tête reprend la fiche de l'entreprise.",
                'example' => 'Un modèle « Standard » avec codes de rubriques affichés et part patronale masquée.',
                'route' => 'payroll.config', 'anchor' => 'templates', 'route_label' => 'Ouvrir les modèles de bulletin', 'resource' => 'payroll_settings',
            ],
            [
                'key' => 'simulation', 'title' => 'Simulation de contrôle sur un employé', 'required' => true, 'confirmable' => false,
                'why' => "Avant la première paie, calculez le bulletin d'un employé dont vous connaissez le net réel et comparez. Un petit écart d'arrondi est normal ; un grand écart signale une rubrique ou un salaire à corriger.",
                'example' => 'Net connu 225 000, net calculé 224 955 : écart de 45, sous la tolérance proposée de 100. Contrôle réussi.',
                'route' => 'payroll.simulation', 'route_label' => 'Lancer une simulation', 'resource' => 'payroll_simulation',
            ],
            [
                'key' => 'first_run', 'title' => 'Première paie du mois', 'required' => true, 'confirmable' => false,
                'why' => "Ouvrez le mois puis suivez les étapes : confirmer les variables, calculer, contrôler les nets, clôturer. Une paie clôturée ne se rouvre pas : une erreur se corrige par une régularisation.",
                'example' => 'Novembre : calcul de tous les bulletins, contrôle des nets, puis clôture.',
                'route' => 'payroll.month', 'anchor' => 'month', 'route_label' => 'Ouvrir la paie du mois', 'resource' => 'payroll_periods',
            ],
        ];
    }

    /** @return array<int, string> */
    public static function keys(): array
    {
        return array_column(self::all(), 'key');
    }

    /** @return array<string, array<string, mixed>> */
    public static function byKey(): array
    {
        $map = [];

        foreach (self::all() as $step) {
            $map[$step['key']] = $step;
        }

        return $map;
    }
}
