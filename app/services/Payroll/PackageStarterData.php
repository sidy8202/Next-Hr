<?php

namespace App\Services\Payroll;

/**
 * Paquets de départ livrés avec l'application : uniquement de la STRUCTURE, jamais de valeur légale ou conventionnelle de mémoire.
 * - Commerce et Transit reprennent ce que montrent les deux bulletins de février 2026 fournis par un comptable (aucun montant).
 * - Les autres secteurs sont des coquilles vides : la plateforme les remplit à partir de la convention, puis un comptable les valide.
 * Tous sont créés « à valider ».
 */
class PackageStarterData
{
    /** @return array<int, array{code: string, name: string, sector: string, description: string, source: ?string, payload: array<string, mixed>}> */
    public static function all(): array
    {
        $toConfirm = 'Qualification de départ : à confirmer par le comptable.';

        $seniority = [
            'code' => '100300', 'label' => "Prime d'ancienneté", 'type' => 'gain', 'calc_type' => 'scale',
            'subject_to_social' => true, 'taxable' => true, 'is_compulsory' => true, 'sort' => 110, 'scale' => [],
            'notes' => 'Barème à saisir selon la convention applicable. ' . $toConfirm,
        ];

        $template = ['name' => 'Standard', 'show_employer_part' => false, 'show_item_codes' => true, 'group_lines' => true];

        $commerce = [
            'items' => [
                [
                    'code' => '100200', 'label' => 'Indemnité spéciale', 'type' => 'gain', 'calc_type' => 'fixed',
                    'subject_to_social' => true, 'taxable' => false, 'is_compulsory' => true, 'sort' => 100,
                    'notes' => "Soumise aux cotisations et non imposable d'après le bulletin de février 2026 ; montant à renseigner. " . $toConfirm,
                ],
                $seniority,
            ],
            'profiles' => [[
                'name' => 'Personnel commerce', 'convention' => 'Convention Collective Fédérale du Commerce',
                'description' => 'Modèle de départ : complétez-le avec les rubriques de votre convention et de votre politique interne.',
                'items' => [['code' => '100200'], ['code' => '100300']],
            ]],
            'template' => $template,
            'settings' => ['convention_name' => 'Convention Collective Fédérale du Commerce'],
        ];

        $transit = [
            'items' => [
                [
                    'code' => '100600', 'label' => 'Téléphone', 'type' => 'benefit_in_kind', 'calc_type' => 'fixed',
                    'subject_to_social' => true, 'taxable' => true, 'is_compulsory' => false, 'sort' => 140,
                    'notes' => 'Ajouté au brut puis retiré du net, comme sur le bulletin de transit de février 2026 ; montant à renseigner. ' . $toConfirm,
                ],
                $seniority,
            ],
            'profiles' => [[
                'name' => 'Personnel transit',
                'description' => 'Modèle de départ : complétez-le avec les rubriques de votre convention et de votre politique interne.',
                'items' => [['code' => '100600'], ['code' => '100300']],
            ]],
            'template' => $template,
        ];

        $source = 'Bulletin de paie de février 2026 (exemple fourni par un comptable)';

        $packages = [
            ['code' => 'commerce', 'name' => 'Commerce : squelette de départ', 'sector' => 'Commerce', 'description' => 'Structure tirée du bulletin commerce de février 2026 : aucun montant, qualification à valider.', 'source' => $source, 'payload' => $commerce],
            ['code' => 'transit', 'name' => 'Transit : squelette de départ', 'sector' => 'Transit', 'description' => 'Structure tirée du bulletin transit de février 2026 : aucun montant, qualification à valider.', 'source' => $source, 'payload' => $transit],
        ];

        foreach ([
            'banques' => ['Banques et établissements financiers', 'Banques'],
            'btp' => ['Bâtiment et travaux publics', 'BTP'],
            'hotellerie' => ['Hôtellerie et restauration', 'Hôtellerie'],
            'mines' => ['Mines et carrières', 'Mines'],
            'enseignement_prive' => ['Enseignement privé', 'Enseignement privé'],
            'sante_communautaire' => ['Centres de santé communautaires', 'Santé communautaire'],
        ] as $code => [$name, $sector]) {
            $packages[] = [
                'code' => $code, 'name' => $name . ' : à remplir', 'sector' => $sector,
                'description' => 'Coquille vide : à remplir par la plateforme à partir de la convention applicable, puis à faire valider par un comptable.',
                'source' => null, 'payload' => [],
            ];
        }

        return $packages;
    }
}
