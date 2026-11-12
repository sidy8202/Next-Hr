<?php

namespace App\Services\Payroll;

/** État d'envoi d'un bulletin, canal par canal puis globalement : fonction pure. */
final class DeliveryState
{
    public const NO_ADDRESS = 'no_address';
    public const NOT_SENT = 'not_sent';
    public const SENT = 'sent';
    public const FAILED = 'failed';

    /**
     * État d'un canal d'e-mail.
     *
     * @param  array{status?: string, sent_at?: ?string, error?: ?string, opened_at?: ?string}|null  $row  ligne de suivi, s'il y en a une
     * @return array{state: string, label: string, address: ?string, at: ?string, opened_at: ?string, error: ?string}
     */
    public static function channel(?array $row, ?string $address): array
    {
        $address = $address !== null ? trim($address) : null;

        if ($address === null || $address === '' || ! filter_var($address, FILTER_VALIDATE_EMAIL)) {
            return ['state' => self::NO_ADDRESS, 'label' => 'Aucune adresse', 'address' => null, 'at' => null, 'opened_at' => null, 'error' => null];
        }

        $base = ['address' => $address, 'at' => null, 'opened_at' => null, 'error' => null];

        return match ($row['status'] ?? null) {
            'sent' => ['state' => self::SENT, 'label' => 'Envoyé'] + array_merge($base, ['at' => $row['sent_at'] ?? null, 'opened_at' => $row['opened_at'] ?? null]),
            'failed' => ['state' => self::FAILED, 'label' => 'Échec'] + array_merge($base, ['error' => $row['error'] ?? null]),
            default => ['state' => self::NOT_SENT, 'label' => 'Non envoyé'] + $base,
        };
    }

    /**
     * État global d'un bulletin d'après ses canaux d'e-mail.
     *
     * @param  array<int, array{state: string}>  $channels
     * @return string sent | partial | failed | not_sent | no_address
     */
    public static function overall(array $channels): string
    {
        $deliverable = array_values(array_filter(array_column($channels, 'state'), fn ($s) => $s !== self::NO_ADDRESS));

        if ($deliverable === []) {
            return self::NO_ADDRESS;
        }

        $sent = count(array_filter($deliverable, fn ($s) => $s === self::SENT));

        if ($sent === count($deliverable)) {
            return self::SENT;
        }

        if ($sent > 0) {
            return 'partial';
        }

        return in_array(self::FAILED, $deliverable, true) ? self::FAILED : self::NOT_SENT;
    }

    /**
     * Totaux pour le résumé « N envoyés, M en échec, K jamais vus ».
     *
     * @param  array<int, array{overall: string, seen_app: bool, opened_email: bool}>  $items
     * @return array{total: int, sent: int, partial: int, failed: int, not_sent: int, no_address: int, seen_app: int, opened_email: int}
     */
    public static function summarize(array $items): array
    {
        $sum = ['total' => count($items), 'sent' => 0, 'partial' => 0, 'failed' => 0, 'not_sent' => 0, 'no_address' => 0, 'seen_app' => 0, 'opened_email' => 0];

        foreach ($items as $i) {
            $sum[$i['overall']]++;
            $sum['seen_app'] += $i['seen_app'] ? 1 : 0;
            $sum['opened_email'] += $i['opened_email'] ? 1 : 0;
        }

        return $sum;
    }
}
