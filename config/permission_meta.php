<?php

/*
|--------------------------------------------------------------------------
| Métadonnées des droits d'accès
|--------------------------------------------------------------------------
| employee_scoped : ressources dont chaque enregistrement concerne UN employé (champ employee_id).
|   Seules celles-ci acceptent les portées « own » et « team ». Les autres sont forcées à « all ».
|   (mission_orders n'y figure pas : elle n'a pas de employee_id, on y reviendra avec le module Missions.)
|
| templates : modèles qui PRÉ-COCHENT la matrice (ce ne sont pas des rôles figés).
|   Format : 'ressource' => ['lettres', 'portée']  avec lettres parmi c, r, u, d.
|   Les ressources inconnues de config/permissions.php sont ignorées.
*/

return [

    'employee_scoped' => [
        'employees', 'contracts', 'internships', 'documents', 'documents_sensitive', 'document_requests',
        'leave_balances', 'leave_requests', 'absence_permissions', 'payslips',
        'terminations', 'explanation_letters', 'insurance_enrollments',
        'leave_requests', 'overtime', // (portées own / team / all)
    ],

    'templates' => [

        'employee' => [
            'label' => 'Employé (libre-service)',
            'permissions' => [
                'leave_requests' => ['cr', 'own'], 'leave_balances' => ['r', 'own'], 'absence_permissions' => ['cr', 'own'],
                'payslips' => ['r', 'own'], 'documents' => ['r', 'own'], 'document_requests' => ['cr', 'own'],
                'explanation_letters' => ['ru', 'own'], 'dashboard_department' => ['r', 'all'],
            ],
        ],

         'employee_leaves' => [
            'label' => 'Employé (congés)',
            'permissions' => [
                'leave_requests' => ['cr', 'own'],
                'overtime'       => ['cr', 'own'],
            ],
        ],

        'department_head' => [
            'label' => 'Chef de département',
            'permissions' => [
                'employees' => ['r', 'team'], 'leave_requests' => ['cru', 'team'], 'leave_balances' => ['r', 'team'],
                'absence_permissions' => ['cru', 'team'], 'documents' => ['r', 'team'], 'document_requests' => ['cr', 'team'],
                'explanation_letters' => ['cr', 'team'], 'departments' => ['r', 'all'], 'positions' => ['r', 'all'],
                'overtime' => ['cr', 'team'],
                'dashboard_department' => ['r', 'all'],
            ],
        ],

        'hr_agent' => [
            'label' => 'Agent RH',
            'permissions' => [
                'employees' => ['cru', 'all'], 'departments' => ['cru', 'all'], 'positions' => ['cru', 'all'],
                'contracts' => ['cru', 'all'], 'internships' => ['cru', 'all'], 'documents' => ['cru', 'all'],
                'document_templates' => ['r', 'all'], 'document_requests' => ['ru', 'all'],
                'leave_types' => ['r', 'all'], 'leave_balances' => ['cru', 'all'], 'leave_requests' => ['ru', 'all'],
                'absence_permissions' => ['ru', 'all'], 'holidays' => ['cru', 'all'], 'explanation_letters' => ['cru', 'all'],
                'insurances' => ['cru', 'all'], 'insurance_enrollments' => ['cru', 'all'],
                'job_openings' => ['cru', 'all'], 'candidates' => ['cru', 'all'], 'candidate_categories' => ['cru', 'all'],
                'job_applications' => ['cru', 'all'], 'dashboard_hr' => ['r', 'all'],
                'leave_requests' => ['crud', 'all'], 'leave_settings' => ['ru', 'all'], 'absences' => ['crud', 'all'], 'overtime' => ['cru', 'all'],


            ],
        ],

        'recruiter' => [
            'label' => 'Recruteur',
            'permissions' => [
                'job_offers'   => ['crud', 'all'],
                'candidates'   => ['crud', 'all'],
                'applications' => ['cru',  'all'],
                'interviews' => ['crud', 'all' ],
                'departments'  => ['r',    'all'],
                'positions'    => ['r',    'all'],
            ],
        ],


        'payroll' => [
            'label' => 'Gestionnaire de paie',
            'permissions' => [
                'employees' => ['r', 'all'], 'contracts' => ['r', 'all'], 'payroll_periods' => ['cru', 'all'],
                'payslips' => ['cru', 'all'], 'documents' => ['cr', 'all'], 'dashboard_payroll' => ['r', 'all'],
            ],
        ],

        'admin_manager' => [
            'label' => 'Responsable administratif',
            'permissions' => [
                'employees' => ['r', 'all'], 'insurances' => ['cru', 'all'], 'insurance_enrollments' => ['cru', 'all'],
                'documents' => ['cru', 'all'], 'document_requests' => ['ru', 'all'],
            ],
        ],

        'dg' => [
            'label' => 'Direction générale (DG / PDG)',
            'permissions' => [
                'employees' => ['r', 'all'], 'departments' => ['r', 'all'], 'positions' => ['r', 'all'], 'contracts' => ['r', 'all'],
                'leave_requests' => ['ru', 'team'], 'absence_permissions' => ['ru', 'team'], 'terminations' => ['r', 'all'],
                'job_openings' => ['r', 'all'], 'candidates' => ['r', 'all'],
                'dashboard_hr' => ['r', 'all'], 'dashboard_payroll' => ['r', 'all'],
            ],
        ],
    ],
];
