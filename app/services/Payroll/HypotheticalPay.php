<?php

namespace App\Services\Payroll;

use App\Models\PayItem;
use App\Models\PayProfileItem;
use App\Models\PayrollRule;
use App\Models\PayrollSetting;
use DateTimeImmutable;
use RuntimeException;

/**
 * Paie d'une personne qui n'existe pas encore (aperçu à la création d'un employé ; base du futur simulateur de personne hypothétique).
 * Rien n'est enregistré : on assemble l'entrée du moteur (PayrollCalculator) à partir des faits fournis.
 *
 * Aperçu AU BARÈME : salaire de base + rubriques du modèle de traitement choisi, mois complet, sans primes propres à l'employé, sans variables,
 * sans sursalaire (sauf net garanti : le moteur le calcule pour atteindre le net).
 *
 * Trois façons de partir : du salaire de base (« base »), du brut voulu (« gross ») ou du net voulu (« net »). Pour les deux dernières,
 * ReverseSolver cherche le plus petit salaire de base qui atteint la cible ; le brut et le net qui en résultent sont ceux du moteur.
 *
 * assemble(), summarize(), seniorityYears() et period() sont purs ; load() et preview() lisent la base.
 */
final class HypotheticalPay
{
    /** Mois utilisé pour les règles légales : le plus tardif entre le mois courant et le mois d'embauche. */
    public static function period(?string $hireDate, string $today): string
    {
        $current = substr($today, 0, 7);

        if ($hireDate !== null && preg_match('/^\d{4}-\d{2}-\d{2}$/', $hireDate)) {
            return max($current, substr($hireDate, 0, 7));
        }

        return $current;
    }

    /**
     * Mois de l'aperçu : celui qu'on demande ($p['period'], AAAA-MM valide), sinon celui que choisit period() d'après la date d'embauche.
     *
     * @param array<string, mixed> $p
     */
    public static function resolvePeriod(array $p, string $today): string
    {
        $asked = (string) ($p['period'] ?? '');

        return preg_match('/^\d{4}-(0[1-9]|1[0-2])$/', $asked) ? $asked : self::period($p['hire_date'] ?? null, $today);
    }

    /** Années complètes entre l'embauche et la fin du mois ; 0 si l'embauche est absente, invalide ou postérieure. */
    public static function seniorityYears(?string $hire, string $end): int
    {
        if ($hire === null || $hire === '') {
            return 0;
        }

        try {
            $from = new DateTimeImmutable($hire);
            $to = new DateTimeImmutable($end);
        } catch (\Exception) {
            return 0;
        }

        return $from > $to ? 0 : (int) $from->diff($to)->y;
    }

    /**
     * Entrée du moteur. $f : rules, rounding, items, labels, base, guaranteed_net (?int), marital_status, children, infirm_adults, seniority_years.
     *
     * @param array<string, mixed> $f
     *
     * @return array<string, mixed>
     */
    public static function assemble(array $f): array
    {
        $net = (int) ($f['guaranteed_net'] ?? 0);

        return [
            'rules' => $f['rules'] ?? [],
            'rounding' => (string) ($f['rounding'] ?? 'nearest'),
            'factor' => 1.0,
            'seniority_years' => max(0, (int) ($f['seniority_years'] ?? 0)),
            'salary' => [
                'base' => max(0, (int) ($f['base'] ?? 0)),
                'supplement' => 0,
                'mode' => $net > 0 ? 'net_target' : 'gross',
                'target_net' => $net > 0 ? $net : null,
            ],
            'labels' => $f['labels'] ?? [],
            'items' => $f['items'] ?? [],
            'variables' => [],
            'family' => [
                'marital_status' => $f['marital_status'] ?? null,
                'children' => max(0, (int) ($f['children'] ?? 0)),
                'infirm_adults' => max(0, (int) ($f['infirm_adults'] ?? 0)),
            ],
        ];
    }

    /**
     * Chiffres à afficher, tirés du résultat du moteur.
     * « employee_total » est le total salarié du moteur (cotisations ET ITS) : les cotisations seules s'obtiennent par soustraction.
     *
     * @param array<string, mixed> $result résultat de PayrollCalculator::compute()
     * @param ?int $base salaire de base utilisé pour ce résultat
     *
     * @return array<string, mixed>
     */
    public static function summarize(array $result, ?int $base = null): array
    {
        $t = $result['totals'];
        $its = (int) $t['its'];
        $employee = (int) $t['employee_contributions'];

        return [
            'base' => (int) ($base ?? 0),
            'gross' => (int) $t['gross'],
            'social' => max(0, $employee - $its),
            'its' => $its,
            'employee_total' => $employee,
            'net' => (int) $t['net_contract'],
            'employer_total' => (int) $t['employer_total'],
            'employer_cost' => (int) $t['employer_cost'],
            'annual_cost' => (int) $t['employer_cost'] * 12,
            'supplement' => (int) ($result['supplement']['used'] ?? 0),
            'net_target' => $result['net_target'] ?? null,
            'warnings' => array_values($result['warnings'] ?? []),
        ];
    }

    /**
     * Lit ce que le moteur attend et qui ne dépend pas d'un employé : règles légales, arrondi, libellés, rubriques d'un modèle.
     *
     * @return array{rules: array, rounding: string, labels: array, items: array, end: string}
     *
     * @throws RuntimeException si aucune règle légale n'est en vigueur
     */
    public static function load(int $companyId, string $period, ?int $profileId): array
    {
        [, $end] = PayrollDays::monthBounds($period);
        $rules = PayrollRule::activeOn($end);

        if ($rules === []) {
            throw new RuntimeException('Aucune règle légale en vigueur à cette date : la plateforme doit charger les règles de paie.');
        }

        $labels = [];

        foreach (PayItem::whereIn('system_key', ['base_salary', 'supplement'])->get() as $item) {
            $labels[$item->system_key] = [
                'code' => $item->code, 'label' => $item->label, 'item_id' => $item->id,
                'social' => (bool) $item->subject_to_social, 'taxable' => (bool) $item->taxable,
            ];
        }

        $lines = [];

        if ($profileId) {
            foreach (PayProfileItem::where('pay_profile_id', $profileId)->with('item.scaleRows')->get() as $row) {
                if ($row->item && $row->item->is_active) {
                    $lines[] = PayComposition::fromItem($row->item, $row->amount, $row->rate);
                }
            }
        }

        return [
            'rules' => $rules,
            'rounding' => (string) PayrollSetting::forCompany($companyId)->rounding,
            'labels' => $labels,
            'items' => PayComposition::merge($lines, []),
            'end' => $end,
        ];
    }

    /**
     * Aperçu complet. $p : marital_status, children, infirm_adults, hire_date, pay_profile_id, et en option period (AAAA-MM)
     * et seniority_years (prioritaire sur le calcul à partir de hire_date).
     *
     * Point de départ, selon $p['from'] :
     *  - « base » (défaut) : $p['base'] est le salaire de base ; $p['guaranteed_net'] garantit un net par un sursalaire ;
     *  - « gross » ou « net » : $p['target'] est le brut ou le net voulu, et le salaire de base est CHERCHÉ (le sursalaire reste nul).
     * Le résultat porte alors « solve » : type de cible, cible, atteinte ou non, écart (obtenu moins cible).
     *
     * @param array<string, mixed> $p
     *
     * @return array<string, mixed>
     *
     * @throws RuntimeException
     */
    public static function preview(int $companyId, array $p): array
    {
        $loaded = self::load($companyId, self::resolvePeriod($p, now()->toDateString()), ! empty($p['pay_profile_id']) ? (int) $p['pay_profile_id'] : null);

        $common = $loaded + $p + [
            'seniority_years' => self::seniorityYears($p['hire_date'] ?? null, $loaded['end']),
        ];

        $from = (string) ($p['from'] ?? 'base');

        if ($from !== ReverseSolver::GROSS && $from !== ReverseSolver::NET) {
            $base = max(0, (int) ($p['base'] ?? 0));

            return self::summarize(PayrollCalculator::compute(self::assemble($common)), $base);
        }

        // Départ du brut ou du net : le salaire de base est cherché, sans sursalaire ni net garanti.
        $inputFor = fn (int $base): array => self::assemble(['base' => $base, 'guaranteed_net' => null] + $common);

        $solved = ReverseSolver::solve($from, (int) ($p['target'] ?? 0), function (int $base) use ($inputFor): array {
            $t = PayrollCalculator::compute($inputFor($base))['totals'];

            return ['gross' => (int) $t['gross'], 'net' => (int) $t['net_contract']];
        });

        return self::summarize(PayrollCalculator::compute($inputFor($solved['base'])), $solved['base']) + [
            'solve' => ['kind' => $solved['kind'], 'target' => $solved['target'], 'reached' => $solved['reached'], 'gap' => $solved['gap']],
        ];
    }
}
