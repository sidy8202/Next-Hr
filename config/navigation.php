
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
