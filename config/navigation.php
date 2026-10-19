<?php

/*
| Menu latéral. Un lien n'apparaît que si sa route existe ET si l'utilisateur a le droit « read »
| sur sa ressource (resource = null : toujours visible, socle libre-service).
*/
return [
    'groups' => [
        [
            'label' => null,
            'items' => [
                ['label' => 'Tableau de bord', 'icon' => 'home', 'route' => 'dashboard', 'resource' => null],
                ['label' => 'Notifications', 'icon' => 'bell', 'route' => 'notifications.index', 'resource' => null],
            ],
        ],
        [
            'label' => 'Organisation',
            'items' => [
                ['label' => 'Départements', 'icon' => 'building-office', 'route' => 'departments.index', 'resource' => 'departments'],
                ['label' => 'Postes', 'icon' => 'briefcase', 'route' => 'positions.index', 'resource' => 'positions'],
                ['label' => 'Employés', 'icon' => 'users', 'route' => 'employees.index', 'resource' => 'employees'],
            ],
        ],
        [
            'label' => 'Recrutement',
            'items' => [
                ['label' => 'Banque de CV', 'icon' => 'document-text', 'route' => 'candidates.index', 'resource' => 'candidates'],
                ['label' => 'Offres d\'emploi', 'icon' => 'identification', 'route' => 'job-offers.index', 'resource' => 'job_offers'],
                ['label' => 'Entretiens', 'icon' => 'calendar', 'route' => 'interviews.index', 'resource' => 'interviews']
            ],
        ],
        [
            'label' => 'Administration',
            'items' => [
                ['label' => 'Utilisateurs', 'icon' => 'user-circle', 'route' => 'users.index', 'resource' => 'users'],
                ['label' => 'Droits d\'accès', 'icon' => 'shield-check', 'route' => 'access.matrix', 'resource' => 'permissions'],
            ],
        ],
        // Les groupes Congés, Paie… seront ajoutés avec leurs pages.
    ],
];