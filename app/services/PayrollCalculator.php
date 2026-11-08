<?php

namespace App\Services\Payroll;

/**
 * Moteur de calcul d'une paie : fonction PURE. Elle ne lit ni la base ni la date : tout lui est fourni dans $in
 * (voir PayrollInputBuilder, qui le prépare à partir des données de l'application).
 *
 * Ordre de calcul (cadrage) :
 *  1. salaire de base et sursalaire de la période, au prorata de présence ;
 *  2. rubriques du modèle de traitement et variables du mois qui touchent le brut ;
 *  3. brut, puis assiettes distinctes : cotisable et imposable ;
 *  4. cotisations salariales, ITS (PayrollLegal) ;
 *  5. retenues sur le net : avantages en nature, retenues internes, retenues individuelles (prêts, avances) ;
 *  6. net à payer, puis cotisations patronales et coût employeur, calculés à part.
 *
 * $in :
 *   rules, rounding, factor (prorata 0..1), seniority_years,
 *   salary {base, supplement, mode: gross|net_target, target_net},
 *   labels {base_salary|supplement: {code, label, item_id, social, taxable}},
 *   items      : lignes de PayComposition (modèle de traitement + primes attribuées),
 *   variables  : lignes de PayVariables, enrichies de {subject_to_social, taxable, item_type},
 *   family {marital_status, children, infirm_adults}.
 */
final class PayrollCalculator
{
    /** @return array<string, mixed> */
    public static function compute(array $in): array
    {
        $salary = $in['salary'] ?? [];
        $target = (int) ($salary['target_net'] ?? 0);
        $isNetTarget = ($salary['mode'] ?? 'gross') === 'net_target' && $target > 0;
        $supplement = (int) ($salary['supplement'] ?? 0);
        $solved = null;

        if ($isNetTarget) {
            // Le net convenu se garantit sur le salaire contractuel d'un mois complet, hors variables du mois.
            $contract = $in;
            $contract['variables'] = [];
            $contract['factor'] = 1.0;

            $solved = NetTargetSolver::solve(fn (int $s): int => self::run($contract, $s)['totals']['net_contract'], $target);
            $supplement = $solved['supplement'];
        }

        $result = self::run($in, $supplement);
        $result['supplement'] = ['used' => $supplement, 'stored' => (int) ($salary['supplement'] ?? 0), 'recomputed' => $isNetTarget];

        if ($isNetTarget) {
            $full = $in;
            $full['variables'] = [];
            $full['factor'] = 1.0;
            $contractNet = self::run($full, $supplement)['totals']['net_contract'];

            $result['net_target'] = ['target' => $target, 'contract_net' => $contractNet, 'gap' => $contractNet - $target, 'reached' => $solved['reached']];

            if (! $solved['reached']) {
                $result['warnings'][] = 'Le net convenu (' . self::fmt($target) . ') n\'a pas pu être atteint.';
            } elseif ($contractNet > $target) {
                $result['warnings'][] = 'Net garanti : le net obtenu dépasse le net convenu de ' . self::fmt($contractNet - $target) . ' (arrondis, ou salaire de base déjà suffisant).';
            }
        }

        return $result;
    }

    /** Explication de l'ITS d'un mois, étape par étape (pour l'écran de simulation). @return array<string, float|int>|null */
    public static function explainIts(array $rules, int $monthlyBase, array $family): ?array
    {
        foreach ($rules as $r) {
            if (($r['kind'] ?? 'rate') !== 'its') {
                continue;
            }

            $params = $r['params'] ?? [];
            $married = IncomeTax::isMarried($family['marital_status'] ?? null);
            $children = (int) ($family['children'] ?? 0);
            $infirm = (int) ($family['infirm_adults'] ?? 0);

            $annual = $monthlyBase * 12;
            $gross = IncomeTax::grossAnnual((float) $annual, $params['brackets'] ?? []);
            $percent = min(100.0, IncomeTax::familyPercent($married, $children, $params['family'] ?? [], $infirm));
            $liquidated = $gross * (1 - $percent / 100);
            $rebate = (float) ($params['rebate_points'] ?? 2) / 100 * $annual;
            $its = max(0.0, $liquidated - $rebate);

            return [
                'monthly_base' => $monthlyBase, 'annual_base' => $annual, 'gross_tax' => round($gross, 2), 'family_percent' => $percent,
                'liquidated' => round($liquidated, 2), 'rebate_points' => (float) ($params['rebate_points'] ?? 2), 'rebate' => round($rebate, 2),
                'annual_its' => round($its, 2), 'monthly_its' => round($its / 12, 2),
            ];
        }

        return null;
    }

    /** @return array<string, mixed> */
    protected static function run(array $in, int $supplement): array
    {
        $rounding = (string) ($in['rounding'] ?? 'nearest');
        $factor = (float) ($in['factor'] ?? 1.0);
        $years = (int) ($in['seniority_years'] ?? 0);
        $labels = $in['labels'] ?? [];
        $warnings = [];

        $earnings = [];
        $grossDeductions = [];
        $netDeductions = [];
        $byItem = [];

        $baseSalary = IncomeTax::round((float) ($in['salary']['base'] ?? 0) * $factor, $rounding);
        $supplementPaid = IncomeTax::round($supplement * $factor, $rounding);
        $prorata = $factor < 1.0 ? 'Prorata de présence : ' . rtrim(rtrim(number_format($factor * 30, 2, ',', ''), '0'), ',') . ' jours sur 30' : null;

        foreach ([['base_salary', $baseSalary, '100001', 'Salaire de base'], ['supplement', $supplementPaid, '100002', 'Sursalaire']] as [$key, $amount, $code, $label]) {
            if ($key === 'supplement' && $amount <= 0) {
                continue;
            }

            $meta = $labels[$key] ?? [];
            $line = self::line($meta['item_id'] ?? null, $meta['code'] ?? $code, $meta['label'] ?? $label, $amount, (bool) ($meta['social'] ?? true), (bool) ($meta['taxable'] ?? true), $prorata);
            $earnings[] = $line;

            if (! empty($meta['item_id'])) {
                $byItem[$meta['item_id']] = $amount;
            }
        }

        // Valeur d'une rubrique du catalogue, selon son type de calcul.
        $evaluate = function (array $it, float $grossSoFar) use ($factor, $years, $baseSalary, &$byItem, &$warnings, $rounding): array {
            $label = $it['label'];
            $rate = isset($it['rate']) ? (float) $it['rate'] : null;
            $calc = $it['calc_type'];
            $amount = 0;
            $base = null;
            $note = null;
            $ok = true;

            switch ($calc) {
                case 'fixed':
                    if (($it['amount'] ?? null) === null) {
                        $warnings[] = "« $label » : aucun montant renseigné (rubrique comptée à 0).";
                        $ok = false;
                    } else {
                        $amount = IncomeTax::round((float) $it['amount'] * $factor, $rounding);
                    }
                    break;

                case 'percent_base':
                case 'percent_gross':
                case 'percent_item':
                    if ($rate === null) {
                        $warnings[] = "« $label » : aucun taux renseigné (rubrique comptée à 0).";
                        $ok = false;
                        break;
                    }

                    if ($calc === 'percent_base') {
                        $base = (float) $baseSalary;
                    } elseif ($calc === 'percent_gross') {
                        $base = $grossSoFar;
                    } else {
                        $base = isset($byItem[$it['ref_item_id'] ?? 0]) ? (float) $byItem[$it['ref_item_id']] : null;

                        if ($base === null) {
                            $warnings[] = "« $label » : la rubrique de référence est absente de ce bulletin (rubrique comptée à 0).";
                            $ok = false;
                            break;
                        }
                    }

                    $amount = IncomeTax::round($base * $rate / 100, $rounding);
                    break;

                case 'scale':
                    $scaleRate = null;

                    foreach ($it['scale'] ?? [] as [$from, $r]) {
                        if ($years >= $from) {
                            $scaleRate = (float) $r;
                        }
                    }

                    if (empty($it['scale'])) {
                        $warnings[] = "« $label » : le barème n'a aucun palier (rubrique comptée à 0).";
                        $ok = false;
                    } elseif ($scaleRate === null) {
                        $note = "Ancienneté de $years an(s) : aucun palier atteint";
                        $rate = 0.0;
                    } else {
                        $rate = $scaleRate;
                        $base = (float) $baseSalary;
                        $amount = IncomeTax::round($base * $scaleRate / 100, $rounding);
                        $note = "Ancienneté : $years an(s)";
                    }
                    break;

                case 'qty_rate':
                    $warnings[] = "« $label » : calcul quantité × taux non géré à ce stade (rubrique comptée à 0).";
                    $ok = false;
                    break;

                default:
                    $ok = false;
            }

            if ($ok) {
                if (($it['floor'] ?? null) !== null && $amount < $it['floor']) {
                    $amount = (int) $it['floor'];
                    $note = trim(($note ?? '') . ' Plancher appliqué.');
                }

                if (($it['cap'] ?? null) !== null && $amount > $it['cap']) {
                    $amount = (int) $it['cap'];
                    $note = trim(($note ?? '') . ' Plafond appliqué.');
                }
            }

            $line = self::line($it['item_id'] ?? null, $it['code'] ?? null, $label, $amount, (bool) ($it['subject_to_social'] ?? true), (bool) ($it['taxable'] ?? true), $note);
            $line['base'] = $base !== null ? (int) round($base) : null;
            $line['rate'] = $rate;
            $byItem[$it['item_id'] ?? 0] = $amount;

            return $line;
        };

        $place = function (array $line, string $type) use (&$earnings, &$grossDeductions, &$netDeductions): void {
            switch ($type) {
                case 'gross_deduction':
                    $grossDeductions[] = $line;
                    break;
                case 'deduction_internal':
                    $netDeductions[] = ['category' => 'internal'] + $line;
                    break;
                case 'deduction_individual':
                    $netDeductions[] = ['category' => 'individual'] + $line;
                    break;
                case 'benefit_in_kind':
                    $earnings[] = $line;
                    // Un avantage en nature entre dans le brut, puis se retire du net.
                    $netDeductions[] = ['category' => 'benefit', 'label' => 'Avantage en nature : ' . $line['label']] + $line;
                    break;
                default:
                    $earnings[] = $line;
            }
        };

        // Rubriques du catalogue : d'abord celles qui ne dépendent de rien, puis « % d'une rubrique », puis « % du brut ».
        $deferredItem = [];
        $deferredGross = [];

        foreach ($in['items'] ?? [] as $it) {
            match ($it['calc_type']) {
                'system' => null,
                'percent_item' => $deferredItem[] = $it,
                'percent_gross' => $deferredGross[] = $it,
                default => $place($evaluate($it, 0.0), ($it['type'] ?? 'gain')),
            };
        }

        for ($pass = 0; $pass < 3 && $deferredItem; $pass++) {
            foreach ($deferredItem as $k => $it) {
                if (isset($byItem[$it['ref_item_id'] ?? 0]) || $pass === 2) {
                    $place($evaluate($it, 0.0), ($it['type'] ?? 'gain'));
                    unset($deferredItem[$k]);
                }
            }
        }

        // Variables du mois confirmées et échéances de prêts.
        foreach ($in['variables'] ?? [] as $v) {
            $amount = $v['amount'];

            if ($amount === null) {
                $warnings[] = '« ' . $v['label'] . ' » : montant non calculable (compté à 0).';
                $amount = 0;
            }

            $note = $v['note'] ?? null;

            if (! empty($v['quantity'])) {
                $note = trim(rtrim(rtrim(number_format((float) $v['quantity'], 2, ',', ''), '0'), ',') . ' ' . (($v['unit'] ?? '') === 'hours' ? 'h' : 'j') . ($note ? ' · ' . $note : ''));
            }

            $line = self::line($v['item_id'] ?? null, $v['code'] ?? null, $v['label'], (int) $amount, (bool) ($v['subject_to_social'] ?? true), (bool) ($v['taxable'] ?? true), $note);
            $line['source'] = $v['source'] ?? 'variable';
            $itemType = $v['item_type'] ?? null;

            if ($v['effect'] === 'gross_deduct') {
                $grossDeductions[] = $line;
            } elseif ($v['effect'] === 'net_deduct') {
                $netDeductions[] = ['category' => $itemType === 'deduction_internal' ? 'internal' : 'individual'] + $line;
            } else {
                $earnings[] = $line;

                if ($itemType === 'benefit_in_kind') {
                    $netDeductions[] = ['category' => 'benefit', 'label' => 'Avantage en nature : ' . $line['label']] + $line;
                }
            }
        }

        [$grossSoFar] = self::totals($earnings, $grossDeductions);

        foreach ($deferredGross as $it) {
            $place($evaluate($it, (float) $grossSoFar), ($it['type'] ?? 'gain'));
        }

        foreach ($deferredItem as $it) {
            $place($evaluate($it, 0.0), ($it['type'] ?? 'gain'));
        }

        // Brut et assiettes.
        [$gross, $cotisable, $taxableGross] = self::totals($earnings, $grossDeductions);

        $family = $in['family'] ?? [];
        $legal = PayrollLegal::compute($in['rules'] ?? [], [
            'gross' => $gross, 'cotisable' => $cotisable, 'taxable_gross' => $taxableGross,
            'marital_status' => $family['marital_status'] ?? null, 'children' => (int) ($family['children'] ?? 0), 'infirm_adults' => (int) ($family['infirm_adults'] ?? 0),
        ], $rounding);

        // Retenues sur le net.
        $sum = fn (string $category): int => (int) array_sum(array_map(fn ($l) => $l['category'] === $category ? $l['amount'] : 0, $netDeductions));
        $benefits = $sum('benefit');
        $internal = $sum('internal');
        $individual = $sum('individual');

        $netBeforeOther = $gross - $legal['employee_total'];
        $netContract = $netBeforeOther - $benefits - $internal;
        $netToPay = $netContract - $individual;

        if ($netToPay < 0) {
            $warnings[] = 'Le net à payer est négatif (' . self::fmt($netToPay) . ') : les retenues dépassent le salaire.';
        }

        return [
            'factor' => $factor,
            'earnings' => $earnings,
            'gross_deductions' => $grossDeductions,
            'legal' => $legal['lines'],
            'net_deductions' => $netDeductions,
            'totals' => [
                'gross' => $gross, 'cotisable' => $cotisable, 'taxable_gross' => $taxableGross, 'its_base' => $legal['its_base'], 'its' => $legal['its'],
                'employee_contributions' => $legal['employee_total'], 'net_before_other' => $netBeforeOther,
                'benefits' => $benefits, 'internal' => $internal, 'individual' => $individual,
                'net_contract' => $netContract, 'net_to_pay' => $netToPay,
                'employer_total' => $legal['employer_total'], 'employer_cost' => $legal['employer_cost'],
            ],
            'warnings' => $warnings,
        ];
    }

    /**
     * Brut, assiette des cotisations et brut imposable : les retenues sur le brut (absences, mises à pied) réduisent
     * chaque assiette dont elles font partie.
     *
     * @return array{0: int, 1: int, 2: int}
     */
    protected static function totals(array $earnings, array $grossDeductions): array
    {
        $gross = array_sum(array_column($earnings, 'amount')) - array_sum(array_column($grossDeductions, 'amount'));
        $cotisable = 0;
        $taxable = 0;

        foreach ($earnings as $l) {
            $cotisable += $l['social'] ? $l['amount'] : 0;
            $taxable += $l['taxable'] ? $l['amount'] : 0;
        }

        foreach ($grossDeductions as $l) {
            $cotisable -= $l['social'] ? $l['amount'] : 0;
            $taxable -= $l['taxable'] ? $l['amount'] : 0;
        }

        return [max(0, (int) $gross), max(0, (int) $cotisable), max(0, (int) $taxable)];
    }

    /** @return array<string, mixed> */
    protected static function line(?int $itemId, ?string $code, string $label, int $amount, bool $social, bool $taxable, ?string $note = null): array
    {
        return ['item_id' => $itemId, 'code' => $code, 'label' => $label, 'base' => null, 'rate' => null, 'amount' => $amount, 'social' => $social, 'taxable' => $taxable, 'note' => $note, 'source' => 'catalogue'];
    }

    protected static function fmt(int $n): string
    {
        return number_format($n, 0, ',', ' ');
    }
}
