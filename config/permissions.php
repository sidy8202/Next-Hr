<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Portées des permissions (Scopes)
    |--------------------------------------------------------------------------
    */
    'scopes' => [
        'own'  => 'Mes données',
        'team' => 'Mon équipe',
        'all'  => 'Tout',
    ],

    /*
    |--------------------------------------------------------------------------
    | Liste des ressources contrôlées
    |--------------------------------------------------------------------------
    */
    'resources' => [

        // Organisation
        'employees'   => ['label' => 'Employés',     'module' => 'Organisation'],
        'departments' => ['label' => 'Départements', 'module' => 'Organisation'],
        'positions'   => ['label' => 'Postes',       'module' => 'Organisation'],
        'delegations' => ['label' => 'Délégations',  'module' => 'Organisation'],

        // Contrats et documents
        'contracts'          => ['label' => 'Contrats',              'module' => 'Contrats et documents'],
        'internships'        => ['label' => 'Stages',                'module' => 'Contrats et documents'],
        'documents'          => ['label' => 'Documents employés',    'module' => 'Contrats et documents'],
        'document_templates' => ['label' => 'Modèles de documents',  'module' => 'Contrats et documents'],
        'documents_sensitive' => ['label' => 'Documents confidentiels', 'module' => 'Contrats et documents'],
        'document_requests'  => ['label' => 'Demandes de documents',   'module' => 'Contrats et documents'],

        // Congés
        'leave_types'         => ['label' => 'Types de congés',      'module' => 'Congés'],
        'leave_balances'      => ['label' => 'Soldes de congés',     'module' => 'Congés'],
        'absence_permissions' => ['label' => 'Autorisations d’absence', 'module' => 'Congés'],
        'holidays'            => ['label' => 'Jours fériés',         'module' => 'Congés'],
        'leave_requests' => ['label' => 'Demandes de congé',            'module' => 'Congés et absences'],
        'leave_settings' => ['label' => 'Paramètres des congés',        'module' => 'Congés et absences'],
        'absences'       => ['label' => 'Absences',                      'module' => 'Congés et absences'],
        'overtime'       => ['label' => 'Jours de repos travaillés',     'module' => 'Congés et absences'],


        // Paie
        'payroll_periods' => ['label' => 'Périodes de paie', 'module' => 'Paie'],
        'payslips'        => ['label' => 'Bulletins de paie', 'module' => 'Paie'],

        // Sorties et discipline
        'terminations'        => ['label' => 'Sorties d’effectif',    'module' => 'Sorties et discipline'],
        'termination_reasons' => ['label' => 'Motifs de sortie',      'module' => 'Sorties et discipline'],
        'explanation_letters' => ['label' => 'Demandes d’explication', 'module' => 'Sorties et discipline'],

        // Assurances
        'insurances'           => ['label' => 'Contrats d’assurance', 'module' => 'Assurances'],
        'insurance_enrollments' => ['label' => 'Affiliations',          'module' => 'Assurances'],

        // Recrutement
        'job_openings'        => ['label' => 'Offres d’emploi',      'module' => 'Recrutement'],
        'candidate_categories' => ['label' => 'Catégories de candidats', 'module' => 'Recrutement'],
        'job_applications'    => ['label' => 'Candidatures',         'module' => 'Recrutement'],
        'job_offers'   => ['label' => 'Offres d’emploi', 'module' => 'Recrutement'],
        'candidates'   => ['label' => 'Banque de CV',    'module' => 'Recrutement'],
        'applications' => ['label' => 'Candidatures',    'module' => 'Recrutement'],
        'interviews'   => ['label' => 'Entretiens',      'module' => 'Recrutement'],
        'page candidature' => ['label' => 'Page publique de candidature','module' => 'Recrutement'],
         

        // Missions
        'mission_orders' => ['label' => 'Ordres de mission', 'module' => 'Missions'],

        // Administration
        'users'       => ['label' => 'Utilisateurs',     'module' => 'Administration'],
        'permissions' => ['label' => 'Gestion des droits', 'module' => 'Administration'],
        'settings'    => ['label' => 'Paramètres',       'module' => 'Administration'],
        'audit_logs'  => ['label' => 'Journaux d’audit',  'module' => 'Administration'],

        // Tableaux de bord
        'dashboard_department' => ['label' => 'Dashboard Département', 'module' => 'Tableaux de bord'],
        'dashboard_hr'         => ['label' => 'Dashboard RH',          'module' => 'Tableaux de bord'],
        'dashboard_payroll'    => ['label' => 'Dashboard Paie',        'module' => 'Tableaux de bord'],
        

    ],
];
