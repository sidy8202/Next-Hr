<?php

return [

    /* Droits de l'équipe plateforme. Le propriétaire (platform_role = owner) a TOUS les droits, toujours. */
    'permissions' => [
        'dashboard.view'       => 'Voir le tableau de bord de la plateforme',
        'reports.view'         => 'Consulter les rapports et comparaisons',
        'companies.view'       => 'Voir les entreprises clientes',
        'companies.manage'     => 'Créer et modifier les entreprises',
        'companies.suspend'    => 'Suspendre et réactiver une entreprise',
        'subscriptions.view'   => 'Voir les abonnements',
        'subscriptions.manage' => 'Créer, changer et annuler les abonnements',
        'payments.record'      => 'Enregistrer les paiements d’abonnement',
        'plans.manage'         => 'Gérer les formules, les prix et la matrice des fonctionnalités',
        'staff.manage'         => 'Gérer les comptes de l’équipe plateforme',
        'audit.view'           => 'Consulter le journal d’audit de la plateforme',
    ],

    /* Modèles de rôles : ils pré-cochent les droits, qui restent ajustables compte par compte. */
    'roles' => [
        'owner'   => ['label' => 'Propriétaire (accès total)', 'permissions' => []],
        'manager' => ['label' => 'Responsable plateforme', 'permissions' => [
            'dashboard.view', 'reports.view', 'companies.view', 'companies.manage', 'companies.suspend',
            'subscriptions.view', 'subscriptions.manage', 'payments.record', 'audit.view',
        ]],
        'billing' => ['label' => 'Facturation', 'permissions' => [
            'dashboard.view', 'companies.view', 'subscriptions.view', 'subscriptions.manage', 'payments.record',
        ]],
        'support' => ['label' => 'Support', 'permissions' => [
            'dashboard.view', 'companies.view', 'companies.manage', 'subscriptions.view',
        ]],
        'analyst' => ['label' => 'Analyste (lecture seule)', 'permissions' => [
            'dashboard.view', 'reports.view', 'companies.view', 'subscriptions.view',
        ]],
    ],

    'periods' => [3 => 'Trimestriel', 6 => 'Semestriel', 12 => 'Annuel'],

    /* Remises suggérées par période (modifiables à chaque souscription). */
    'default_discounts' => [3 => 0, 6 => 5, 12 => 15],

    'payment_methods' => [
        'mobile_money' => 'Mobile money',
        'bank'         => 'Virement bancaire',
        'cash'         => 'Espèces',
        'cheque'       => 'Chèque',
    ],

    /* Jours de tolérance : abonnement impayé à l'échéance, puis première facture jamais payée. */
    'grace_days'   => 15,
    'pending_days' => 7,
    'max_tranches'        => 3,   // tranches maximum pour solder un abonnement
    'renewal_window_days' => 30,  // un abonnement soldé n'accepte un nouveau paiement (renouvellement) que 30 jours avant l'échéance
    'installment_days'    => 45,  // délai pour solder les tranches après la première avant expiration

    'sectors' => [
        'ngo'         => 'ONG et humanitaire',
        'commerce'    => 'Commerce et distribution',
        'mining'      => 'Mines et ressources',
        'finance'     => 'Banque, assurance et microfinance',
        'construction'=> 'BTP et immobilier',
        'telecom'     => 'Télécoms et numérique',
        'health'      => 'Santé',
        'education'   => 'Éducation et formation',
        'agri'        => 'Agriculture et agro-industrie',
        'transport'   => 'Transport et logistique',
        'industry'    => 'Industrie',
        'services'    => 'Services et conseil',
        'hospitality' => 'Hôtellerie et restauration',
        'public'      => 'Administration et secteur public',
        'other'       => 'Autre',
    ],

    'countries' => [
        'ML' => 'Mali', 'SN' => 'Sénégal', 'CI' => 'Côte d’Ivoire', 'BF' => 'Burkina Faso', 'NE' => 'Niger',
        'GN' => 'Guinée', 'TG' => 'Togo', 'BJ' => 'Bénin', 'MR' => 'Mauritanie', 'GH' => 'Ghana', 'CM' => 'Cameroun', 'FR' => 'France',
    ],
];
