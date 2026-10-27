<?php

return [
    // Délai de réponse par défaut (en jours OUVRÉS du planning de l'employé). Modifiable dans « En-tête des lettres ».
    // À valider selon votre règlement intérieur ou votre convention collective.
    'default_response_days' => 3,

    // Modèles de départ du corps d'une demande d'explication (le texte reste entièrement modifiable).
    'request_templates' => [
        'absence' => ['label' => 'Absence non justifiée', 'subject' => 'Demande d’explication — absence non justifiée',
            'body' => "Nous avons constaté votre absence le [date] sans que nous ayons été informés ni reçu de justificatif.\n\nNous vous prions de bien vouloir nous communiquer par écrit les raisons de cette absence, accompagnées de tout document utile."],
        'lateness' => ['label' => 'Retards répétés', 'subject' => 'Demande d’explication — retards répétés',
            'body' => "Il a été relevé que vous êtes arrivé(e) en retard à plusieurs reprises, notamment les [dates].\n\nNous vous demandons de nous expliquer par écrit les raisons de ces retards."],
        'conduct' => ['label' => 'Manquement aux consignes', 'subject' => 'Demande d’explication — manquement aux consignes',
            'body' => "Le [date], à [lieu], il a été constaté que [faits constatés].\n\nCes faits paraissent contraires à [consigne, règlement ou procédure concernée]. Nous vous invitons à nous fournir vos explications par écrit."],
        'other' => ['label' => 'Autre motif', 'subject' => 'Demande d’explication', 'body' => ''],
    ],

    // Suites possibles après la réponse de l'employé. Les textes sont des points de départ : à adapter à votre règlement intérieur.
    'decisions' => [
        'no_action' => ['label' => 'Classement sans suite', 'subject' => 'Suite donnée à votre demande d’explication',
            'body' => "Nous avons pris connaissance de vos explications du [date] relatives à la demande d’explication citée en référence.\n\nAprès examen, nous avons décidé de ne pas donner de suite disciplinaire à cette affaire."],
        'caution' => ['label' => 'Mise en garde', 'subject' => 'Lettre de mise en garde',
            'body' => "Suite à la demande d’explication citée en référence et à votre réponse du [date], nous vous adressons la présente mise en garde concernant [faits].\n\nNous vous demandons de veiller à ne plus renouveler ce comportement."],
        'warning' => ['label' => 'Avertissement', 'subject' => 'Notification d’un avertissement',
            'body' => "Après examen de la demande d’explication citée en référence et de votre réponse du [date], nous constatons que les faits reprochés sont établis : [faits].\n\nNous vous notifions par la présente un avertissement. Toute récidive pourra entraîner une sanction plus sévère."],
        'reprimand' => ['label' => 'Blâme', 'subject' => 'Notification d’un blâme',
            'body' => "Après examen de la demande d’explication citée en référence et de votre réponse du [date], nous vous notifions un blâme pour les faits suivants : [faits].\n\nCette sanction sera versée à votre dossier."],
        'suspension' => ['label' => 'Mise à pied', 'subject' => 'Notification d’une mise à pied',
            'body' => "Après examen de la demande d’explication citée en référence et de votre réponse du [date], nous vous notifions une mise à pied pour les faits suivants : [faits].\n\nLes dates et les conditions de rémunération de cette mise à pied sont précisées ci-dessous."],
        'dismissal' => ['label' => 'Licenciement', 'subject' => 'Notification de licenciement',
            'body' => "Après examen de la demande d’explication citée en référence et de votre réponse du [date], nous sommes au regret de vous notifier votre licenciement pour le motif suivant : [motif].\n\nLes modalités (préavis, indemnités, solde de tout compte) vous seront communiquées séparément."],
    ],
];
