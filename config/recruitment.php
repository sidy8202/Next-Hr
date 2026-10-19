<?php

return [
    // Niveaux d'études, du plus bas au plus haut ("rank" sert aux comparaisons).
    'education_levels' => [
        'none' => ['label' => 'Sans diplôme', 'rank' => 0],
        'def'  => ['label' => 'DEF / CEP', 'rank' => 1],
        'bac'  => ['label' => 'Baccalauréat / BT', 'rank' => 2],
        'bac2' => ['label' => 'Bac +2 (BTS, DUT, DEUG)', 'rank' => 3],
        'bac3' => ['label' => 'Bac +3 (Licence)', 'rank' => 4],
        'bac5' => ['label' => 'Bac +5 (Master, Ingénieur)', 'rank' => 5],
        'bac8' => ['label' => 'Bac +8 (Doctorat)', 'rank' => 6],
    ],

    // Ancienneté professionnelle = années d'EXPÉRIENCE (jamais l'âge). "min" inclus, "max" exclu.
    'seniority' => [
        'junior'    => ['label' => 'Junior',   'min' => 0,  'max' => 2],
        'confirmed' => ['label' => 'Confirmé', 'min' => 2,  'max' => 6],
        'senior'    => ['label' => 'Senior',   'min' => 6,  'max' => 10],
        'expert'    => ['label' => 'Expert',   'min' => 10, 'max' => null],
    ],

    'profile_categories' => [
        'Informatique & Télécoms',
        'Finance & Comptabilité',
        'Ressources humaines',
        'Commercial & Marketing',
        'Logistique & Achats',
        'Technique & Maintenance',
        'Santé & Social',
        'Juridique',
        'Administration & Secrétariat',
        'Éducation & Formation',
        'Autre',
    ],

    'sources' => [
        'spontaneous' => 'Candidature spontanée',
        'website'     => 'Site web / réseaux sociaux',
        'referral'    => 'Recommandation',
        'agency'      => 'Cabinet / agence',
        'school'      => 'École / université',
        'other'       => 'Autre',
    ],

    // À aligner sur les types de contrat de votre module Contrats si besoin.
    'contract_types' => [
        'CDI'        => 'CDI',
        'CDD'        => 'CDD',
        'STAGE'      => 'Stage',
        'CONSULTANT' => 'Consultance',
    ],

    'offer_statuses' => [
        'draft'  => 'Brouillon',
        'open'   => 'Ouverte',
        'closed' => 'Clôturée',
        'filled' => 'Pourvue',
    ],

    'candidate_statuses' => [
        'active'   => 'Actif',
        'archived' => 'Archivé',
        'hired'    => 'Embauché',
    ],

    // Étapes du pipeline (l'ordre est l'ordre d'affichage) et passages autorisés.
    'stages' => [
        'new'       => ['label' => 'Nouvelle',     'next' => ['screening', 'rejected']],
        'screening' => ['label' => 'Présélection', 'next' => ['interview', 'rejected']],
        'interview' => ['label' => 'Entretien',    'next' => ['test', 'offer', 'rejected']],
        'test'      => ['label' => 'Test',         'next' => ['interview', 'offer', 'rejected']],
        'offer'     => ['label' => 'Proposition',  'next' => ['hired', 'rejected']],
        'hired'     => ['label' => 'Embauché',     'next' => []],
        'rejected'  => ['label' => 'Refusée',      'next' => ['screening']],
    ],

    // Suggestions affichées lors d'un refus (le motif reste libre).
    'rejection_reasons' => [
        'Profil ne correspondant pas au poste',
        'Expérience insuffisante',
        'Prétentions salariales trop élevées',
        'Candidat indisponible ou désistement',
        'Poste pourvu par un autre candidat',
    ],

    // Durée de conservation d'un profil dans la banque de CV (consentement du candidat).
    'retention_months' => 24,

    'cv' => ['max_kb' => 5120, 'mimes' => ['pdf', 'doc', 'docx']],
];
