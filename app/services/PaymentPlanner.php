<?php

namespace App\Services;

use Carbon\CarbonInterface;
use InvalidArgumentException;

/** Règles de calcul des échéanciers de perdiem : pures (sans base de données), donc testables seules. */
final class PaymentPlanner
{
    /**
     * Échéancier proposé selon la modalité choisie à la saisie de la mission.
     * before : tout la veille du départ ; after : tout au retour ; tranches : moitié avant, solde après ;
     * custom : rien (la RH le définit à la main).
     *
     * @return array<int, array{label: string, amount: int, date: string}>
     */
    public static function defaultPlan(string $paymentMode, int $total, CarbonInterface $start, CarbonInterface $end): array
    {
        if ($total < 1) {
            return [];
        }

        $before = $start->copy()->startOfDay()->subDay()->toDateString();
        $after = $end->copy()->startOfDay()->toDateString();

        return match (true) {
            $paymentMode === 'before' => [['label' => 'Paiement avant le départ', 'amount' => $total, 'date' => $before]],
            $paymentMode === 'after' || ($paymentMode === 'tranches' && $total < 2) => [['label' => 'Paiement après la mission', 'amount' => $total, 'date' => $after]],
            $paymentMode === 'tranches' => [
                ['label' => 'Avance avant le départ', 'amount' => intdiv($total, 2), 'date' => $before],
                ['label' => 'Solde après la mission', 'amount' => $total - intdiv($total, 2), 'date' => $after],
            ],
            default => [],
        };
    }

    /**
     * Valide et normalise un échéancier saisi à la main : montants entiers > 0, dates valides, somme = total.
     *
     * @return array<int, array{label: string, amount: int, date: ?string}>
     */
    public static function normalize(array $rows, int $total): array
    {
        $out = [];
        $sum = 0;

        foreach ($rows as $r) {
            $amount = (int) ($r['amount'] ?? 0);

            if ($amount < 1) {
                throw new InvalidArgumentException('Chaque tranche doit avoir un montant supérieur à 0.');
            }

            $date = trim((string) ($r['date'] ?? ''));

            if ($date !== '' && strtotime($date) === false) {
                throw new InvalidArgumentException('Une date de tranche est invalide.');
            }

            $label = trim((string) ($r['label'] ?? '')) ?: 'Tranche';
            $out[] = ['label' => mb_substr($label, 0, 80), 'amount' => $amount, 'date' => $date !== '' ? $date : null];
            $sum += $amount;
        }

        if (! $out) {
            throw new InvalidArgumentException('Ajoutez au moins une tranche.');
        }

        if ($sum !== $total) {
            throw new InvalidArgumentException('Le total des tranches ('.self::fcfa($sum).') doit être égal au perdiem total ('.self::fcfa($total).').');
        }

        return $out;
    }

    /**
     * État financier d'un participant.
     * unplanned : aucun échéancier ; unpaid : rien payé ; partial : payé en partie ; settled : soldé.
     *
     * @return array{total: int, paid: int, remaining: int, state: string}
     */
    public static function summarize(int $total, int $paid, int $openRows): array
    {
        $state = match (true) {
            $total > 0 && $paid >= $total => 'settled',
            $paid > 0 => 'partial',
            $openRows === 0 => 'unplanned',
            default => 'unpaid',
        };

        return ['total' => $total, 'paid' => $paid, 'remaining' => max(0, $total - $paid), 'state' => $state];
    }

    public static function fcfa(int $amount): string
    {
        return number_format($amount, 0, ',', ' ').' FCFA';
    }
}
