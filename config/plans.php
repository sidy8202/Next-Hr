<?php

/*
| Catalogue des fonctionnalités et réglages par défaut des trois formules.
| 'resources' : les ressources de config/permissions.php pilotées par la fonctionnalité
|               (une ressource absente de cette liste est considérée comme « socle » : toujours disponible).
| 'default'   : valeur de départ de la matrice (standard, business, premium). La matrice est ensuite modifiable
|               dans l'espace plateforme (Formules) : ce fichier ne sert qu'à l'initialisation (PlanSeeder).
*/

$all = ['standard' => true, 'business' => true, 'premium' => true];
$bp = ['standard' => false, 'business' => true, 'premium' => true];
$p = ['standard' => false, 'business' => false, 'premium' => true];

return [

    'features' => [

        // ── Socle RH et paie : dans TOUTES les formules ──
        'organization'  => ['label' => 'Employés, départements et postes', 'module' => 'Organisation', 'resources' => ['employees', 'departments', 'positions'], 'default' => $all],
        'contracts_docs' => ['label' => 'Contrats, documents employés, demandes de documents', 'module' => 'Contrats et documents', 'resources' => ['contracts', 'documents', 'document_requests'], 'default' => $all],
        'leave_absence' => ['label' => 'Congés, soldes, absences, autorisations d’absence, jours fériés', 'module' => 'Congés et absences', 'resources' => ['leave_types', 'leave_balances', 'leave_requests', 'leave_settings', 'absences', 'absence_permissions', 'holidays'], 'default' => $all],
        'overtime'      => ['label' => 'Heures supplémentaires et jours de repos travaillés', 'module' => 'Congés et absences', 'resources' => ['overtime'], 'default' => $all],
        'payroll'       => ['label' => 'Paie : périodes, bulletins de paie, dashboard paie', 'module' => 'Paie', 'resources' => ['payroll_periods', 'payslips', 'dashboard_payroll'], 'default' => $all],
        'terminations'  => ['label' => 'Sorties d’effectif et solde de tout compte', 'module' => 'Sorties', 'resources' => ['terminations', 'termination_reasons'], 'default' => $all],
        'custom_rights' => ['label' => 'Droits personnalisés sur chaque ressource', 'module' => 'Administration', 'resources' => ['permissions'], 'default' => $all],
        'admin_core'    => ['label' => 'Utilisateurs, paramètres, journal d’audit', 'module' => 'Administration', 'resources' => ['users', 'settings', 'audit_logs'], 'default' => $all],
        'dashboards'    => ['label' => 'Dashboards département et RH', 'module' => 'Tableaux de bord', 'resources' => ['dashboard_department', 'dashboard_hr'], 'default' => $all],

        // ── Extension RH : Business et Premium ──
        'recruitment'   => ['label' => 'Recrutement complet (CV, offres, entretiens, page carrières)', 'module' => 'Recrutement', 'resources' => ['job_openings', 'candidate_categories', 'job_applications', 'job_offers', 'candidates', 'applications', 'interviews', 'page candidature'], 'default' => $bp],
        'discipline'    => ['label' => 'Demandes d’explication et papier à en-tête', 'module' => 'Discipline', 'resources' => ['explanation_letters', 'explanations'], 'default' => $bp],
        'assignments'   => ['label' => 'Affectations, intérims et délégations', 'module' => 'Organisation', 'resources' => ['assignments', 'delegations'], 'default' => $bp],
        'internships'   => ['label' => 'Gestion des stages', 'module' => 'Contrats et documents', 'resources' => ['internships'], 'default' => $bp],
        'doc_templates' => ['label' => 'Modèles de documents personnalisés', 'module' => 'Contrats et documents', 'resources' => ['document_templates'], 'default' => $bp],
        'insurance'     => ['label' => 'Assurances et affiliations', 'module' => 'Assurances', 'resources' => ['insurances', 'insurance_enrollments'], 'default' => $bp],
        'missions'      => ['label' => 'Ordres de mission (workflow, ordres signés)', 'module' => 'Missions', 'resources' => ['mission_orders', 'mission_orders_hr', 'mission_orders_dg'], 'default' => $bp],
        'payroll_advanced' => ['label' => 'Paie avancée : profils multiples, rubriques personnalisées, états sociaux', 'module' => 'Paie', 'resources' => [], 'default' => $bp],
        'exports'       => ['label' => 'Exports Excel et rapports standard', 'module' => 'Rapports', 'resources' => [], 'default' => $bp],

        // ── Pilotage : Premium ──
        'mission_payments' => ['label' => 'Paiement des perdiems et fiches de perdiem', 'module' => 'Missions', 'resources' => ['mission_payments'], 'default' => $p],
        'sensitive_docs' => ['label' => 'Documents confidentiels', 'module' => 'Contrats et documents', 'resources' => ['documents_sensitive'], 'default' => $p],
        'payroll_expert' => ['label' => 'Paie experte : conventions personnalisées, simulations, multi-établissements', 'module' => 'Paie', 'resources' => [], 'default' => $p],
        'advanced_reports' => ['label' => 'Rapports avancés et comparatifs, historique des consultations', 'module' => 'Rapports', 'resources' => [], 'default' => $p],
    ],

    'plans' => [
        'standard' => ['name' => 'Standard', 'color' => '#6366f1', 'sort' => 1, 'max_employees' => 55,   'storage_gb' => 2,  'audit_retention_months' => 3,  'description' => 'Le socle RH et la paie, pour les petites structures.'],
        'business' => ['name' => 'Business', 'color' => '#0ea5e9', 'sort' => 2, 'max_employees' => 250,  'storage_gb' => 10, 'audit_retention_months' => 12, 'description' => 'Toute la gestion RH : recrutement, discipline, missions, assurances.'],
        'premium'  => ['name' => 'Premium',  'color' => '#8b5cf6', 'sort' => 3, 'max_employees' => 1000, 'storage_gb' => 50, 'audit_retention_months' => 60, 'description' => 'Pilotage complet, conformité et paie experte.'],
    ],
];
