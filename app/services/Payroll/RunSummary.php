<?php

namespace App\Services\Payroll;

/** Totaux et alertes d'une paie, par département : fonction pure. */
final class RunSummary
{
    public const NO_DEPARTMENT = 'Sans département';

    /**
     * @param  array<int, array{department: ?string, net_known: ?int, net_to_pay: int, status: string}>  $rows
     * @return array<string, mixed>
     */
    public static function summarize(array $rows): array
    {
        $statuses = [NetCheck::OK => 0, NetCheck::VARIABLES => 0, NetCheck::TO_CHECK => 0, NetCheck::NO_REFERENCE => 0];
        $compared = ['count' => 0, 'net_known' => 0, 'net_calculated' => 0];
        $departments = [];
        $netTotal = 0;

        foreach ($rows as $r) {
            $name = ($r['department'] ?? null) ?: self::NO_DEPARTMENT;
            $departments[$name] ??= ['count' => 0, 'ok' => 0, 'variables' => 0, 'to_check' => 0, 'no_reference' => 0, 'net_known' => 0, 'net_calculated' => 0];

            $departments[$name]['count']++;
            $departments[$name][$r['status']]++;
            $statuses[$r['status']]++;
            $netTotal += $r['net_to_pay'];

            if ($r['net_known'] !== null) {
                $compared['count']++;
                $compared['net_known'] += $r['net_known'];
                $compared['net_calculated'] += $r['net_to_pay'];
                $departments[$name]['net_known'] += $r['net_known'];
                $departments[$name]['net_calculated'] += $r['net_to_pay'];
            }
        }

        $compared['gap'] = $compared['net_calculated'] - $compared['net_known'];

        ksort($departments);

        if (isset($departments[self::NO_DEPARTMENT])) {
            $none = $departments[self::NO_DEPARTMENT];
            unset($departments[self::NO_DEPARTMENT]);
            $departments[self::NO_DEPARTMENT] = $none;
        }

        return ['count' => count($rows), 'net_total' => $netTotal, 'compared' => $compared, 'statuses' => $statuses, 'departments' => $departments];
    }
}
