
<?php

/*
|--------------------------------------------------------------------------
| Menu latéral
|--------------------------------------------------------------------------
| Un lien apparaît si sa route existe et si l'utilisateur possède le droit
| « read » sur sa ressource.
| Une ressource absente ou définie à null laisse le lien accessible
| sans vérification de permission dans le Sidebar.
*/

return [
    'groups' => [

        // Accès principal
        [
            'label' => null,
            'items' => [
                [
                    'label' => 'Tableau de bord',
                    'icon' => 'home',
                    'route' => 'dashboard',
                    'resource' => null,
                ],
                [
                    'label' => 'Notifications',
                    'icon' => 'bell',
                    'route' => 'notifications.index',
                    'resource' => null,
                ],
            ],
        ],

        // Organisation
        [
            'label' => 'Organisation',
            'items' => [
                [
                    'label' => 'Départements',
                    'icon' => 'building-office',
                    'route' => 'departments.index',
                    'resource' => 'departments',
                ],
                [
                    'label' => 'Postes',
                    'icon' => 'briefcase',
                    'route' => 'positions.index',
                    'resource' => 'positions',
                ],
                [
                    'label' => 'Employés',
                    'icon' => 'users',
                    'route' => 'employees.index',
                    'resource' => 'employees',
                ],
                [
                    'label' => 'Affectations',
                    'icon' => 'document-text',
                    'route' => 'assignments.index',
                    'resource' => 'assignments',
                    'action' => 'read',
                ],
                [
                    'label' => 'Absences',
                    'icon' => 'clock',
                    'route' => 'leaves.absences',
                    'resource' => 'absences',
                    'action' => 'read',
                ],
                [
                    'label' => 'À valider',
                    'icon' => 'inbox',
                    'route' => 'leaves.inbox',
                    'resource' => null,
                    'badge' => null,
                ],
                [
                    'label' => 'Toutes les demandes',
                    'icon' => 'document-text',
                    'route' => 'leaves.index',
                    'resource' => 'leave_requests',
                    'action' => 'read',
                ],
            ],
        ],

      // Paie et rémunération
       // Groupe 1 : Paie du mois
        [
            'label' => 'Paie du mois',
            'items' => [
                ['label' => 'Paie du mois', 'icon' => 'banknotes', 'route' => 'payroll.month', 'resource' => 'payroll_periods', 'action' => 'read'],
                ['label' => 'Variables du mois', 'icon' => 'clock', 'route' => 'payroll.variables', 'resource' => 'payroll_variables', 'action' => 'read'],
                ['label' => 'Prêts et avances', 'icon' => 'inbox', 'route' => 'payroll.loans', 'resource' => 'employee_loans', 'action' => 'read'],
                ['label' => 'Simulation de paie', 'icon' => 'scale', 'route' => 'payroll.simulation', 'resource' => 'payroll_simulation', 'action' => 'read'],
            ],
        ],

        // Groupe 2 : Paie : employés
        [
            'label' => 'Paie : employés',
            'items' => [
                ['label' => 'Salaires des employés', 'icon' => 'banknotes', 'route' => 'payroll.salaries', 'resource' => 'employee_salaries', 'action' => 'read'],
                ['label' => 'Affectations de modèles', 'icon' => 'users', 'route' => 'payroll.assignments', 'resource' => 'pay_profiles', 'action' => 'read'],
                ['label' => 'Primes attribuées', 'icon' => 'identification', 'route' => 'payroll.bonuses', 'resource' => 'employee_pay_items', 'action' => 'read'],
                ['label' => 'Mes bulletins', 'icon' => 'banknotes', 'route' => 'payslips.mine', 'resource' => 'payslips', 'action' => 'read'],
            ],
        ],

        // Groupe 3 : Paie : configuration
        [
            'label' => 'Paie : configuration',
            'items' => [
                ['label' => 'Paramètres de paie', 'icon' => 'cog-6-tooth', 'route' => 'payroll.settings', 'resource' => 'payroll_settings', 'action' => 'read'],
                ['label' => 'Grilles salariales', 'icon' => 'squares-2x2', 'route' => 'payroll.grids', 'resource' => 'salary_grids', 'action' => 'read'],
                ['label' => 'Rubriques de paie', 'icon' => 'scale', 'route' => 'payroll.items', 'resource' => 'pay_items', 'action' => 'read'],
                ['label' => 'Modèles de traitement', 'icon' => 'briefcase', 'route' => 'payroll.profiles', 'resource' => 'pay_profiles', 'action' => 'read'],
                ['label' => 'Modèles de bulletin', 'icon' => 'document-text', 'route' => 'payroll.templates', 'resource' => 'payroll_settings', 'action' => 'read'],
                ['label' => 'Réglages des variables', 'icon' => 'cog', 'route' => 'payroll.variable-settings', 'resource' => 'payroll_settings', 'action' => 'read'],
            ],
        ],
        // Recrutement
        [
            'label' => 'Recrutement',
            'items' => [
                [
                    'label' => 'Banque de CV',
                    'icon' => 'document-text',
                    'route' => 'candidates.index',
                    'resource' => 'candidates',
                ],
                [
                    'label' => "Offres d'emploi",
                    'icon' => 'identification',
                    'route' => 'job-offers.index',
                    'resource' => 'job_offers',
                ],
                [
                    'label' => 'Entretiens',
                    'icon' => 'calendar',
                    'route' => 'interviews.index',
                    'resource' => 'interviews',
                ],
            ],
        ],

        // Administration
        [
            'label' => 'Administration',
            'items' => [
                [
                    'label' => 'Utilisateurs',
                    'icon' => 'user-circle',
                    'route' => 'users.index',
                    'resource' => 'users',
                ],
                [
                    'label' => "Droits d'accès",
                    'icon' => 'shield-check',
                    'route' => 'access.matrix',
                    'resource' => 'permissions',
                ],
                [
                    'label' => 'Mon abonnement',
                    'icon' => 'document-text',
                    'route' => 'subscription.mine',
                    'resource' => 'settings',
                    'action' => 'read'
                ],
            ],
        ],

        // Sorties et discipline
        [
            'label' => 'Sorties et discipline',
            'items' => [
                [
                    'label' => 'Ordres de mission',
                    'icon' => 'briefcase',
                    'route' => 'missions.index',
                    'resource' => 'mission_orders',
                    'action' => 'read',
                ],

                [
                    'label' => 'Paiements perdiems',
                    'icon' => 'scale', 'route' => 'missions.payments',
                    'resource' => 'mission_payments',
                    'action' => 'read'
                ],

                [
                    'label' => 'Demandes d’explications',
                    'icon' => 'document-text',
                    'route' => 'explanations.index',
                    'resource' => 'explanation_letters',
                    'action' => 'read',
                ],
            ],
        ],

        [
                'label' => 'Rapports',
                'items' =>
                [
                    [
                        'label' => 'Rapports RH',
                        'icon' => 'scale',
                        'route' => 'reports.hr',
                        'resource' => 'hr_reports',
                        'action' => 'read'
                    ],
                ],
        ],

        // Congés et permissions
        [
            'label' => 'Congés et Permissions',
            'items' => [
                [
                    'label' => 'Mes congés',
                    'icon' => 'user',
                    'route' => 'leaves.mine',
                    'resource' => null,
                ],
                [
                    'label' => 'Soldes',
                    'icon' => 'scale',
                    'route' => 'leaves.balances',
                    'resource' => 'leave_requests',
                    'action' => 'update',
                ],
                [
                    'label' => 'Jours travaillés',
                    'icon' => 'briefcase',
                    'route' => 'leaves.overtime',
                    'resource' => 'overtime',
                    'action' => 'read',
                ],
                [
                    'label' => 'Permissions et absences',
                    'icon' => 'document-text',
                    'route' => 'permissions.index',
                    'resource' => 'absence_permissions',
                    'action' => 'read',
                ],
                [
                    'label' => 'Paramètres congés',
                    'icon' => 'cog',
                    'route' => 'leaves.settings',
                    'resource' => 'leave_settings',
                    'action' => 'read',
                ],
            ],

        ],

        // Ajouter ici les futurs groupes (paie, etc.).
    ],
];
