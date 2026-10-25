<?php

return [
    // Chaque type de demande branche son propre « handler » sur le moteur de validation.
    'handlers' => [
        'leave' => \App\Approvals\LeaveHandler::class,
        'overtime' => \App\Approvals\OvertimeHandler::class,
        // lot 11 : 'permission', 'mission', …
    ],

    // La RH peut valider à la place du chef de département (absent, sans compte, sans intérimaire). L'étape est alors marquée « RH à la place du chef ».
    'hr_can_override_manager' => true,

    // Intérim par votre module « Délégations » (table delegations : from_employee_id délègue à to_employee_id entre start_date et end_date).
    'delegation' => [
        'enabled' => true,
        'model' => \App\Models\Delegation::class,
        'company' => 'company_id',
        'delegator' => 'from_employee_id',   // le chef qui délègue
        'delegate' => 'to_employee_id',      // celui qui le remplace
        'from' => 'start_date',
        'to' => 'end_date',
    ],
];
