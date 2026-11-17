<?php

namespace App\Services\Payroll;

/**
 * Un scénario du simulateur de personne hypothétique : valeurs saisies à l'écran, nettoyées avant d'aller au moteur.
 * Fonctions pures : tout ce qui vient du navigateur est traité comme du texte non fiable (chiffres seulement, bornes fixes).
 */
final class ScenarioInput
{
    public const MARITAL = ['single', 'married', 'divorced', 'widowed'];

    public const MAX_SCENARIOS = 3;

    /** Point de départ : le salaire de base, le brut voulu ou le net voulu. */
    public const FROM = ['base' => 'Salaire de base', 'gross' => 'Brut', 'net' => 'Net'];

    /** Plafond des montants saisis : évite tout dépassement de capacité dans le calcul. */
    private const MAX_AMOUNT = 100000000000;

    /** Scénario vide, tel qu'affiché au départ. @return array<string, string> */
    public static function blank(string $label): array
    {
        return ['label' => $label, 'from' => 'base', 'step_id' => '', 'amount' => '', 'marital_status' => 'single', 'children' => '0', 'infirm_adults' => '0', 'seniority' => '0', 'net' => '', 'pay_profile_id' => ''];
    }

    /** « Scénario A », « Scénario B », « Scénario C ». */
    public static function defaultLabel(int $index): string
    {
        return 'Scénario ' . chr(65 + max(0, min(25, $index)));
    }

    /**
     * @param array<string, mixed> $raw
     *
     * @return array{label: string, from: string, amount: int, marital_status: ?string, children: int, infirm_adults: int, seniority_years: int, net: ?int, pay_profile_id: ?int}
     *               « net » (net visé garanti par un sursalaire) n'a de sens que si l'on part du salaire de base : sinon il est écarté
     */
    public static function normalize(array $raw, string $fallbackLabel): array
    {
        $label = trim((string) ($raw['label'] ?? ''));
        $from = (string) ($raw['from'] ?? 'base');
        $from = array_key_exists($from, self::FROM) ? $from : 'base';
        $marital = (string) ($raw['marital_status'] ?? '');
        $net = min(self::MAX_AMOUNT, self::digits($raw['net'] ?? ''));
        $profile = self::digits($raw['pay_profile_id'] ?? '');

        return [
            'label' => $label === '' ? $fallbackLabel : mb_substr($label, 0, 40),
            'from' => $from,
            'amount' => min(self::MAX_AMOUNT, self::digits($raw['amount'] ?? '')),
            'marital_status' => in_array($marital, self::MARITAL, true) ? $marital : null,
            'children' => min(30, self::digits($raw['children'] ?? 0)),
            'infirm_adults' => min(10, self::digits($raw['infirm_adults'] ?? 0)),
            'seniority_years' => min(50, self::digits($raw['seniority'] ?? 0)),
            'net' => $from === 'base' && $net > 0 ? $net : null,
            'pay_profile_id' => $profile > 0 ? $profile : null,
        ];
    }

    /** Ce qui manque pour calculer ce scénario, ou null s'il est calculable. @param array{from: string, amount: int} $normalized */
    public static function problem(array $normalized): ?string
    {
        if ($normalized['amount'] > 0) {
            return null;
        }

        return match ($normalized['from']) {
            'gross' => 'Indiquez le brut voulu.',
            'net' => 'Indiquez le net voulu.',
            default => 'Indiquez un salaire de base (ou choisissez un échelon).',
        };
    }

    private static function digits(mixed $value): int
    {
        return (int) preg_replace('/\D/', '', (string) $value);
    }
}
