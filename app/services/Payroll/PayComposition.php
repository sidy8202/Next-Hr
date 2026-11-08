<?php

namespace App\Services\Payroll;

use App\Models\EmployeePayItem;
use App\Models\EmployeePayProfile;
use App\Models\PayItem;
use App\Models\PayProfileItem;

/**
 * Compose la liste réelle des rubriques d'un employé à une date :
 *   rubriques du modèle de traitement en vigueur + primes attribuées à l'employé,
 *   la valeur attribuée à l'employé l'emportant sur celle du modèle pour une même rubrique.
 *
 * Aucun calcul de paie ici (c'est le rôle du moteur, lot 13d) : on ne fait que dire QUELLES rubriques
 * s'appliquent et avec QUELLE valeur de départ. merge(), makeLine() et isActiveOn() sont des fonctions pures.
 */
class PayComposition
{
    /** @return array<int, array<string, mixed>> lignes triées par ordre puis par code */
    public static function forEmployee(int $employeeId, string $date): array
    {
        $profileLines = [];

        $assignment = EmployeePayProfile::where('employee_id', $employeeId)->inForceOn($date)->orderByDesc('effective_from')->first();

        if ($assignment) {
            $rows = PayProfileItem::where('pay_profile_id', $assignment->pay_profile_id)->with('item.scaleRows')->get();

            foreach ($rows as $row) {
                if ($row->item && $row->item->is_active) {
                    $profileLines[] = self::fromItem($row->item, $row->amount, $row->rate);
                }
            }
        }

        $employeeLines = [];

        $attributed = EmployeePayItem::where('employee_id', $employeeId)->inForceOn($date)->with('item.scaleRows')->get();

        foreach ($attributed as $row) {
            if ($row->item && $row->item->is_active) {
                $employeeLines[] = self::fromItem($row->item, $row->amount, $row->rate);
            }
        }

        return self::merge($profileLines, $employeeLines);
    }

    public static function fromItem(PayItem $item, $amount = null, $rate = null): array
    {
        $data = $item->only([
            'id', 'code', 'label', 'type', 'calc_type', 'subject_to_social', 'taxable', 'is_compulsory', 'default_amount', 'default_rate',
            'ref_item_id', 'floor_amount', 'cap_amount', 'show_on_payslip', 'sort', 'system_key',
        ]);
        $data['scale'] = $item->scaleRows->map(fn ($r) => [(int) $r->from_years, (float) $r->rate])->all();

        return self::makeLine($data, $amount !== null ? (int) $amount : null, $rate !== null ? (float) $rate : null);
    }

    /** Fonction pure : construit une ligne à partir des données d'une rubrique et d'une éventuelle valeur imposée. */
    public static function makeLine(array $item, ?int $amount = null, ?float $rate = null): array
    {
        return [
            'item_id' => (int) $item['id'],
            'code' => (string) $item['code'],
            'label' => (string) $item['label'],
            'type' => (string) $item['type'],
            'calc_type' => (string) $item['calc_type'],
            'system_key' => $item['system_key'] ?? null,
            'subject_to_social' => (bool) $item['subject_to_social'],
            'taxable' => (bool) $item['taxable'],
            'amount' => $amount ?? (isset($item['default_amount']) ? (int) $item['default_amount'] : null),
            'rate' => $rate ?? (isset($item['default_rate']) ? (float) $item['default_rate'] : null),
            'ref_item_id' => $item['ref_item_id'] ?? null,
            'floor' => isset($item['floor_amount']) ? (int) $item['floor_amount'] : null,
            'cap' => isset($item['cap_amount']) ? (int) $item['cap_amount'] : null,
            'scale' => $item['scale'] ?? [],
            'show_on_payslip' => (bool) ($item['show_on_payslip'] ?? true),
            'sort' => (int) ($item['sort'] ?? 0),
            'source' => 'profile',
        ];
    }

    /**
     * Fonction pure : fusionne les lignes du modèle et celles de l'employé.
     * Source « profile » (modèle seul), « employee » (prime propre à l'employé) ou « employee_override » (l'employé remplace le modèle).
     */
    public static function merge(array $profileLines, array $employeeLines): array
    {
        $byItem = [];

        foreach ($profileLines as $line) {
            $line['source'] = 'profile';
            $byItem[$line['item_id']] = $line;
        }

        foreach ($employeeLines as $line) {
            $line['source'] = isset($byItem[$line['item_id']]) ? 'employee_override' : 'employee';
            $byItem[$line['item_id']] = $line;
        }

        $lines = array_values($byItem);
        usort($lines, fn ($a, $b) => [$a['sort'], $a['code']] <=> [$b['sort'], $b['code']]);

        return $lines;
    }

    /** Fonction pure : la période [début, fin ou null] couvre-t-elle la date ? (dates au format AAAA-MM-JJ) */
    public static function isActiveOn(string $from, ?string $until, string $date): bool
    {
        return $from <= $date && ($until === null || $until >= $date);
    }

    /** Fonction pure : deux périodes se chevauchent-elles ? (fin null = sans fin) */
    public static function overlaps(string $fromA, ?string $untilA, string $fromB, ?string $untilB): bool
    {
        return $fromA <= ($untilB ?? '9999-12-31') && $fromB <= ($untilA ?? '9999-12-31');
    }
}
