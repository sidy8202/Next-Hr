<?php

return [
    // Heures de travail d'une journée : sert à convertir des heures de permission en jours.
    'hours_per_day' => 8,

    // Types de permission créés pour chaque entreprise (modifiables ensuite dans « Types de permission »).
    // Durées des événements familiaux : d'après le guide du Code du travail consulté, À CONFIRMER avec votre juriste / convention.
    // excess_policy : deduct_leave = l'excédent est retiré du congé annuel ; refuse = refusé ; unpaid = jours non payés.
    'default_types' => [
        ['code' => 'personal', 'name' => 'Permission personnelle',               'kind' => 'days',  'free_days' => 2, 'excess_policy' => 'deduct_leave'],
        ['code' => 'wedding',  'name' => 'Mariage du salarié',                   'kind' => 'days',  'free_days' => 3, 'excess_policy' => 'deduct_leave'],
        ['code' => 'birth',    'name' => 'Consulation Médicale',                'kind' => 'days',  'free_days' => 1, 'excess_policy' => 'deduct_leave'],
        ['code' => 'death_close', 'name' => 'Décès du conjoint ou d’un enfant',  'kind' => 'days',  'free_days' => 3, 'excess_policy' => 'deduct_leave', 'requires_document' => true],
        ['code' => 'death_parent', 'name' => 'Décès d’un parent',                'kind' => 'days',  'free_days' => 2, 'excess_policy' => 'deduct_leave', 'requires_document' => true],
        ['code' => 'child_wedding', 'name' => 'Mariage d’un enfant',             'kind' => 'days',  'free_days' => 2, 'excess_policy' => 'deduct_leave'],
        ['code' => 'hours',    'name' => 'Permission de quelques heures',        'kind' => 'hours', 'free_days' => 0, 'excess_policy' => 'deduct_leave', 'hours_free_per_month' => 8],
    ],

    // Message de la fiche de permission (chaque type peut avoir le sien).
    'sheet_message' => 'Votre demande de permission a été accordée. Nous vous souhaitons de bien profiter de ce moment et vous attendons à la reprise.',
];
