<?php

return [
    'groups' => [
        [
            'label' => null,
            'items' => [
                ['label' => 'Tableau de bord', 'icon' => 'home', 'route' => 'dashboard', 'resource' => null],
            ],
        ],
        [
            'label' => 'Organisation',
            'items' => [
                ['label' => 'Départements', 'icon' => 'building-office', 'route' => 'departments.index', 'resource' => 'departments'],
                ['label' => 'Postes',       'icon' => 'briefcase',       'route' => 'positions.index',   'resource' => 'positions'],
                ['label' => 'Employés',     'icon' => 'users',           'route' => 'employees.index',   'resource' => 'employees'],
            ],
        ],
        // On ajoutera les autres groupes (Congés, Paie, Recrutement...) au fil des étapes
    ],
];
