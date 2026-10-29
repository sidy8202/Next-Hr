<?php

return [
    'reference_prefix' => 'OM',

    // Diviseur par défaut du salaire journalier (22, 26 ou 30), modifiable par la RH à chaque validation.
    'salary_divisor' => 30,

    // Scan de l'ordre signé déposé par la RH.
    'signed' => ['mimes' => 'pdf,jpg,jpeg,png', 'max_kb' => 10240],

    'payment_modes' => [
        'before'   => 'Intégral avant le départ',
        'tranches' => 'Par tranches',
        'after'    => 'Après la mission',
        'custom'   => 'Échéancier personnalisé',
    ],
];
