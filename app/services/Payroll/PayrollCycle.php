<?php

namespace App\Services\Payroll;

/**
 * Calcul PUR de la frise du cycle de paie d'un mois (aucune base de données).
 *
 * Cinq étapes, toujours dans le même ordre : variables → calcul → contrôle des nets → clôture → envoi des bulletins.
 * Une étape est « done » (faite), « current » (la première qui reste à faire) ou « waiting » (à faire plus tard).
 * Une étape peut redevenir à faire après coup (une nouvelle variable proposée après le calcul, par exemple) : la frise reflète toujours l'état réel.
 */
final class PayrollCycle
{
    public const DONE = 'done';
    public const CURRENT = 'current';
    public const WAITING = 'waiting';

    /**
     * @param array<string, mixed> $f
     *   period_status : none | draft | calculated | closed · pending_variables (variables « proposées ») · payslips (bulletins du mois)
     *   pending_checks (nets « à vérifier » non traités) · delivery : résultat de DeliveryState::summarize, ou null si la paie n'est pas clôturée
     *
     * @return array{steps: array<int, array<string, mixed>>, current: ?string, done: int, total: int, percent: int, finished: bool}
     */
    public static function steps(array $f): array
    {
        $status = (string) ($f['period_status'] ?? 'none');
        $closed = $status === 'closed';
        $calculated = in_array($status, ['calculated', 'closed'], true);
        $pendingVariables = max(0, (int) ($f['pending_variables'] ?? 0));
        $payslips = max(0, (int) ($f['payslips'] ?? 0));
        $pendingChecks = max(0, (int) ($f['pending_checks'] ?? 0));
        $delivery = is_array($f['delivery'] ?? null) ? $f['delivery'] : null;

        $toSend = $delivery === null ? 0 : (int) $delivery['failed'] + (int) $delivery['not_sent'] + (int) $delivery['partial'];
        $noAddress = $delivery === null ? 0 : (int) $delivery['no_address'];

        $steps = [
            [
                'key' => 'variables',
                'title' => 'Variables du mois',
                'complete' => $closed || $pendingVariables === 0,
                'detail' => $closed
                    ? 'Variables verrouillées par la clôture.'
                    : ($pendingVariables === 0
                        ? 'Aucune variable en attente de confirmation.'
                        : self::plural($pendingVariables, 'variable à confirmer ou à ignorer', 'variables à confirmer ou à ignorer') . ($calculated ? '. Recalculez ensuite la paie.' : '.')),
                'target' => ['route' => 'payroll.month', 'anchor' => 'variables', 'resource' => 'payroll_variables', 'label' => 'Confirmer les variables'],
            ],
            [
                'key' => 'calculation',
                'title' => 'Calcul de la paie',
                'complete' => $calculated,
                'detail' => $calculated
                    ? self::plural($payslips, 'bulletin calculé', 'bulletins calculés') . ($closed ? ', figés.' : '.')
                    : 'La paie de ce mois n\'est pas encore calculée.',
                'target' => ['route' => 'payroll.month', 'anchor' => 'month', 'resource' => 'payroll_periods', 'label' => 'Calculer la paie'],
            ],
            [
                'key' => 'checks',
                'title' => 'Contrôle des nets',
                'complete' => $closed || ($calculated && $pendingChecks === 0),
                'detail' => ! $calculated
                    ? 'À faire après le calcul.'
                    : ($pendingChecks === 0
                        ? 'Aucun écart de net en attente de traitement.'
                        : self::plural($pendingChecks, 'bulletin au net différent du net connu, à traiter', 'bulletins au net différent du net connu, à traiter') . '.'),
                'target' => ['route' => 'payroll.month', 'anchor' => 'month', 'resource' => 'payroll_periods', 'label' => 'Traiter les écarts'],
            ],
            [
                'key' => 'closing',
                'title' => 'Clôture',
                'complete' => $closed,
                'detail' => $closed
                    ? 'Paie clôturée : les bulletins sont définitifs.'
                    : ($calculated
                        ? ($pendingChecks === 0 ? 'Prête à être clôturée.' : 'Traitez d\'abord les écarts de net.')
                        : 'À faire après le calcul et le contrôle.'),
                'target' => ['route' => 'payroll.month', 'anchor' => 'month', 'resource' => 'payroll_periods', 'label' => 'Clôturer la paie'],
            ],
            [
                'key' => 'sending',
                'title' => 'Envoi des bulletins',
                'complete' => $closed && $delivery !== null && $toSend === 0,
                'detail' => ! $closed
                    ? 'À faire après la clôture.'
                    : ($delivery === null
                        ? 'Aucun bulletin à envoyer.'
                        : ($toSend === 0
                            ? 'Tous les bulletins envoyables sont envoyés' . ($noAddress > 0 ? ' (' . self::plural($noAddress, 'employé sans adresse e-mail', 'employés sans adresse e-mail') . ').' : '.')
                            : self::plural($toSend, 'bulletin à envoyer ou à relancer', 'bulletins à envoyer ou à relancer') . ((int) $delivery['failed'] > 0 ? ' dont ' . (int) $delivery['failed'] . ' en échec.' : '.'))),
                'target' => ['route' => 'payroll.month', 'anchor' => 'month', 'resource' => 'payroll_periods', 'label' => 'Envoyer les bulletins'],
            ],
        ];

        $current = null;
        $done = 0;

        foreach ($steps as $i => $s) {
            if ($s['complete']) {
                $steps[$i]['status'] = self::DONE;
                $done++;
            } elseif ($current === null) {
                $steps[$i]['status'] = self::CURRENT;
                $current = $s['key'];
            } else {
                $steps[$i]['status'] = self::WAITING;
            }

            unset($steps[$i]['complete']);
        }

        $total = count($steps);

        return [
            'steps' => $steps,
            'current' => $current,
            'done' => $done,
            'total' => $total,
            'percent' => intdiv($done * 200 + $total, 2 * $total),
            'finished' => $done === $total,
        ];
    }

    protected static function plural(int $n, string $one, string $many): string
    {
        return $n . ' ' . ($n > 1 ? $many : $one);
    }
}
