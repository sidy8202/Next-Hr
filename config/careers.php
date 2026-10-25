<?php

return [
    // Score de correspondance (0-100) : seuils d'affichage.
    'shortlist_threshold' => 70,   // « Correspond » : peut être présélectionné
    'review_threshold' => 40,      // en dessous : « Correspond peu » ; entre les deux : « À examiner »

    // Poids de chaque critère de l'offre. Un critère absent de l'offre est ignoré (les autres sont rééquilibrés).
    'weights' => ['domain' => 25, 'education' => 25, 'experience' => 30, 'skills' => 20],

    // Conservation d'un profil dont le candidat n'a PAS accepté la banque de CV : durée du processus.
    'process_retention_months' => 6,

    // Anti-spam du formulaire public : candidatures par adresse IP et par offre, par heure.
    'apply_attempts_per_hour' => 5,
];
