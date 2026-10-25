<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Types de documents et gestion de la sensibilité
    |--------------------------------------------------------------------------
    |
    | 'kind' : 'generated' (à partir d'un modèle PDF) ou 'received' (téléversé).
    | 'sensitive' : Si true, l'accès requiert la permission 'documents_sensitive'.
    |
    */
    'types' => [

        // Documents générés (Templates PDF)
        'work_contract' => [
            'label'     => 'Contrat de travail',
            'kind'      => 'generated',
            'sensitive' => true,
        ],
        'contract_amendment' => [
            'label'     => 'Avenant au contrat',
            'kind'      => 'generated',
            'sensitive' => true,
        ],
        'work_attestation' => [
            'label'     => 'Attestation de travail',
            'kind'      => 'generated',
            'sensitive' => false,
        ],
        'salary_attestation' => [
            'label'     => 'Attestation de salaire',
            'kind'      => 'generated',
            'sensitive' => true,
        ],
        'work_certificate' => [
            'label'     => 'Certificat de travail',
            'kind'      => 'generated',
            'sensitive' => false,
        ],
        'warning_letter' => [
            'label'     => "Lettre d'avertissement",
            'kind'      => 'generated',
            'sensitive' => true,
        ],
        'dismissal_letter' => [
            'label'     => 'Lettre de licenciement',
            'kind'      => 'generated',
            'sensitive' => true,
        ],
        'final_settlement' => [
            'label'     => 'Solde de tout compte',
            'kind'      => 'generated',
            'sensitive' => true,
        ],

        // Documents reçus (Téléversements)
        'resignation_letter' => [
            'label'     => 'Lettre de démission',
            'kind'      => 'received',
            'sensitive' => true,
        ],
        'diploma' => [
            'label'     => 'Diplôme',
            'kind'      => 'received',
            'sensitive' => false,
        ],
        'id_document' => [
            'label'     => "Pièce d'identité",
            'kind'      => 'received',
            'sensitive' => true,
        ],
        'medical_certificate' => [
            'label'     => 'Certificat médical',
            'kind'      => 'received',
            'sensitive' => true,
        ],
        'cv' => [
            'label'     => 'CV',
            'kind'      => 'received',
            'sensitive' => false,
        ],
        'leave_sheet' => [
            'label' => 'Fiche de congé',
            'kind' => 'issued',
            'sensitive' => false,
        ],
        'other' => [
            'label'     => 'Autre document',
            'kind'      => 'received',
            'sensitive' => false,
        ],

    ],

];
