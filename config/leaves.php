<?php

return [
    // Valeurs par défaut d'une entreprise (modifiables ensuite dans « Paramètres des congés »).
    'default_monthly_days' => 2.5,      // jours de congé acquis par mois de présence
    'negative_tolerance' => 5,          // solde négatif toléré, par exception (en jours)
    'workdays' => [1, 2, 3, 4, 5],      // 1 = lundi … 7 = dimanche
    'deduct_unjustified' => true,       // une absence non justifiée est retirée du solde de congé
    'recovery_validity_days' => 90,     // un jour de récupération doit être pris dans ce délai

    // Types de congé créés pour chaque entreprise (aucune durée légale n'est imposée : à ajuster par l'entreprise).
    // pool : 'annual' = retire du solde de congé annuel ; 'recovery' = retire des jours de récupération ; null = ne retire rien.
    'default_types' => [
        ['code' => 'annual',   'name' => 'Congé annuel',                     'pool' => 'annual',   'requires_document' => false, 'paid' => true],
        ['code' => 'recovery', 'name' => 'Récupération (jours travaillés)',  'pool' => 'recovery', 'requires_document' => false, 'paid' => true],
        ['code' => 'sick',     'name' => 'Congé maladie',                    'pool' => null,       'requires_document' => true,  'paid' => true],
        ['code' => 'family',   'name' => 'Permission exceptionnelle (événement familial)', 'pool' => null, 'requires_document' => false, 'paid' => true],
        ['code' => 'maternity', 'name' => 'Congé de maternité / paternité',  'pool' => null,       'requires_document' => true,  'paid' => true],
        ['code' => 'unpaid',   'name' => 'Congé sans solde',                 'pool' => null,       'requires_document' => false, 'paid' => false],
    ],

    // Majoration d'ancienneté : [années de service, jours en plus par an]. Valeurs du Code du travail du Mali (art. L.151-L.154,
    // d'après le guide consulté) : à confirmer avec votre juriste ou votre convention collective. [] = aucune majoration.
    'seniority_bonuses' => [[15, 2], [20, 4], [25, 6]],

    // Justificatifs (formats affichables dans le visualiseur).
    'attachment' => ['max_kb' => 5120, 'mimes' => ['pdf', 'jpg', 'jpeg', 'png']],
];
