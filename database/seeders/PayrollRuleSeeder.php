<?php

namespace Database\Seeders;

use App\Models\PayrollRule;
use Illuminate\Database\Seeder;

/**
 * Règles légales du Mali, saisies d'après le Code général des impôts (articles 9 à 12-B, 159-163) et les bulletins de février 2026.
 * TOUTES sont créées « à valider » : un comptable doit les confirmer (page plateforme « Règles de paie ») avant usage en production.
 * Relançable sans risque : une règle existante (même code, même date d'effet) n'est jamais écrasée.
 */
class PayrollRuleSeeder extends Seeder
{
    public function run(): void
    {
        $bulletins = 'Bulletins de paie de février 2026 (2 entreprises)';

        $rules = [
            ['code' => 'INPS_SAL', 'label' => 'INPS retraite (part salariale)', 'party' => 'employee', 'rate' => 3.6, 'base' => 'cotisable', 'deductible_from_its' => true,
                'effective_from' => '2026-01-01', 'source' => $bulletins, 'notes' => 'Taux relevé sur les deux bulletins. Déductible de la base de l’ITS. Plafond éventuel à confirmer. Date d’effet à confirmer.', 'sort' => 10],
            ['code' => 'AMO_SAL', 'label' => 'AMO (part salariale)', 'party' => 'employee', 'rate' => 3.06, 'base' => 'cotisable', 'deductible_from_its' => false,
                'effective_from' => '2026-01-01', 'source' => $bulletins, 'notes' => 'Taux relevé sur les deux bulletins. Non déduit de la base de l’ITS sur le bulletin analysé. Date d’effet à confirmer.', 'sort' => 20],
            ['code' => 'ITS', 'label' => 'Impôt sur les traitements et salaires (ITS)', 'party' => 'employee', 'kind' => 'its', 'base' => 'imposable',
                'effective_from' => '2015-07-01', 'source' => 'Code général des impôts, art. 9 à 12-B (loi 2016-10 et loi 2008-002)',
                'notes' => 'Barème par tranches, puis réduction pour charges de famille (situation au 1er janvier), puis réduction de 2 points sur le taux réel. Écart de quelques dizaines de FCFA constaté avec les bulletins : règle d’arrondi à confirmer avec le comptable.',
                'params' => [
                    'brackets' => [[0, 330000, 0], [330000, 578400, 5], [578400, 1176400, 12], [1176400, 1789733, 18], [1789733, 2384195, 26], [2384195, 3494130, 31], [3494130, null, 37]],
                    'family' => ['married' => 10, 'per_child' => 2.5, 'max_children' => 10, 'infirm_adult' => 10],
                    'rebate_points' => 2,
                ], 'sort' => 30],
            ['code' => 'INPS_EMP', 'label' => 'INPS employeur', 'party' => 'employer', 'rate' => 16.4, 'base' => 'cotisable',
                'effective_from' => '2026-01-01', 'source' => 'Bulletin de février 2026 (entreprise de transit)', 'notes' => 'Regroupe vraisemblablement retraite, prestations familiales et accidents du travail : détail et plafonds à confirmer.', 'sort' => 40],
            ['code' => 'AMO_EMP', 'label' => 'AMO (part patronale)', 'party' => 'employer', 'rate' => 3.5, 'base' => 'cotisable',
                'effective_from' => '2026-01-01', 'source' => 'Bulletin de février 2026 (entreprise de transit)', 'notes' => 'À confirmer.', 'sort' => 50],
            ['code' => 'CFE', 'label' => 'Contribution forfaitaire à la charge des employeurs (CFE)', 'party' => 'employer', 'rate' => 3.5, 'base' => 'brut',
                'effective_from' => '2026-01-01', 'source' => 'Code général des impôts, art. 159 à 163 ; bulletin de février 2026',
                'notes' => 'Le CGI arrondit la base aux mille francs inférieurs ; le bulletin observé ne l’applique pas (10 042 = 3,5 % de 286 919). À confirmer.', 'sort' => 60],
            ['code' => 'TL', 'label' => 'TL (taxe employeur)', 'party' => 'employer', 'rate' => 1.0, 'base' => 'brut',
                'effective_from' => '2026-01-01', 'source' => 'Bulletin de février 2026 (entreprise de transit)', 'notes' => 'Nature exacte du libellé « TL » à identifier avec le comptable.', 'sort' => 70],
        ];

        foreach ($rules as $r) {
            PayrollRule::firstOrCreate(
                ['country' => 'ML', 'code' => $r['code'], 'effective_from' => $r['effective_from']],
                $r + ['kind' => $r['kind'] ?? 'rate', 'status' => 'to_validate'],
            );
        }
    }
}
