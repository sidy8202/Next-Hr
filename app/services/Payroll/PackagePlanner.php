<?php

namespace App\Services\Payroll;

/**
 * Plan PUR d'application d'un paquet à une entreprise (aucune base de données).
 * Principe : on crée ce qui manque, on n'écrase JAMAIS, on rapporte ce qui est créé et ce qui est ignoré.
 *
 * Correspondances : rubriques par code (sans tenir compte de la casse) ; modèles, grilles et modèles de bulletin par nom
 * (sans tenir compte de la casse ni des espaces autour). Le modèle de bulletin ne devient « par défaut » que si l'entreprise n'en a pas.
 * Les réglages se limitent au nom de la convention, rempli seulement s'il est vide.
 *
 * @phpstan-type Existing array{item_codes: array<int, string>, profile_names: array<int, string>, grid_names: array<int, string>, template_names: array<int, string>, has_default_template: bool, convention_name: ?string}
 */
class PackagePlanner
{
    /**
     * @param array<string, mixed> $payload contenu du paquet (supposé valide : voir PackageSchema)
     * @param array<string, mixed> $existing ce que l'entreprise a déjà (voir PackageApplier::existing)
     *
     * @return array<string, mixed>
     */
    public static function plan(array $payload, array $existing): array
    {
        $codes = array_flip(array_map(fn ($c) => strtoupper((string) $c), (array) ($existing['item_codes'] ?? [])));
        $profiles = array_flip(array_map([self::class, 'name'], (array) ($existing['profile_names'] ?? [])));
        $grids = array_flip(array_map([self::class, 'name'], (array) ($existing['grid_names'] ?? [])));
        $templates = array_flip(array_map([self::class, 'name'], (array) ($existing['template_names'] ?? [])));

        $plan = [
            'items' => ['create' => [], 'skip' => []],
            'profiles' => ['create' => [], 'skip' => []],
            'grids' => ['create' => [], 'skip' => []],
            'template' => ['action' => 'none', 'row' => null, 'make_default' => false],
            'settings' => ['action' => 'none', 'convention_name' => null],
        ];

        foreach ((array) ($payload['items'] ?? []) as $i => $it) {
            $code = strtoupper((string) $it['code']);

            if (isset($codes[$code])) {
                $plan['items']['skip'][] = ['code' => $code, 'label' => (string) $it['label']];

                continue;
            }

            $codes[$code] = true;
            $plan['items']['create'][] = [
                'code' => $code,
                'label' => trim((string) $it['label']),
                'type' => $it['type'],
                'calc_type' => $it['calc_type'],
                'subject_to_social' => (bool) ($it['subject_to_social'] ?? true),
                'taxable' => (bool) ($it['taxable'] ?? true),
                'is_compulsory' => (bool) ($it['is_compulsory'] ?? false),
                'default_amount' => isset($it['default_amount']) ? (int) $it['default_amount'] : null,
                'default_rate' => isset($it['default_rate']) ? (float) $it['default_rate'] : null,
                'ref_code' => isset($it['ref_code']) ? strtoupper((string) $it['ref_code']) : null,
                'scale' => array_map(fn ($r) => ['from_years' => (int) $r['from_years'], 'rate' => (float) $r['rate']], (array) ($it['scale'] ?? [])),
                'sort' => isset($it['sort']) ? (int) $it['sort'] : 200 + $i,
                'notes' => isset($it['notes']) ? (string) $it['notes'] : null,
            ];
        }

        foreach ((array) ($payload['profiles'] ?? []) as $p) {
            $key = self::name($p['name']);

            if (isset($profiles[$key])) {
                $plan['profiles']['skip'][] = ['name' => trim((string) $p['name'])];

                continue;
            }

            $profiles[$key] = true;
            $plan['profiles']['create'][] = [
                'name' => trim((string) $p['name']),
                'convention' => isset($p['convention']) ? (string) $p['convention'] : null,
                'description' => isset($p['description']) ? (string) $p['description'] : null,
                'lines' => array_map(fn ($l) => [
                    'code' => strtoupper((string) $l['code']),
                    'amount' => isset($l['amount']) ? (int) $l['amount'] : null,
                    'rate' => isset($l['rate']) ? (float) $l['rate'] : null,
                ], (array) ($p['items'] ?? [])),
            ];
        }

        foreach ((array) ($payload['grids'] ?? []) as $g) {
            $key = self::name($g['name']);

            if (isset($grids[$key])) {
                $plan['grids']['skip'][] = ['name' => trim((string) $g['name'])];

                continue;
            }

            $grids[$key] = true;
            $plan['grids']['create'][] = [
                'name' => trim((string) $g['name']),
                'convention' => isset($g['convention']) ? (string) $g['convention'] : null,
                'grades' => array_map(fn ($grade) => [
                    'code' => trim((string) $grade['code']),
                    'label' => isset($grade['label']) ? (string) $grade['label'] : null,
                    'steps' => array_map(fn ($s) => ['label' => trim((string) $s['label']), 'amount' => (int) $s['amount']], (array) ($grade['steps'] ?? [])),
                ], (array) ($g['grades'] ?? [])),
            ];
        }

        $template = $payload['template'] ?? null;

        if (is_array($template) && $template !== []) {
            if (isset($templates[self::name($template['name'])])) {
                $plan['template'] = ['action' => 'skip', 'row' => ['name' => trim((string) $template['name'])], 'make_default' => false];
            } else {
                $plan['template'] = [
                    'action' => 'create',
                    'make_default' => empty($existing['has_default_template']),
                    'row' => [
                        'name' => trim((string) $template['name']),
                        'show_employer_part' => (bool) ($template['show_employer_part'] ?? false),
                        'show_item_codes' => (bool) ($template['show_item_codes'] ?? true),
                        'group_lines' => (bool) ($template['group_lines'] ?? true),
                        'footer_text' => isset($template['footer_text']) ? (string) $template['footer_text'] : null,
                    ],
                ];
            }
        }

        $settings = $payload['settings'] ?? null;

        if (is_array($settings) && isset($settings['convention_name']) && trim((string) $settings['convention_name']) !== '') {
            $filled = trim((string) ($existing['convention_name'] ?? '')) !== '';

            $plan['settings'] = [
                'action' => $filled ? 'skip' : 'fill',
                'convention_name' => trim((string) $settings['convention_name']),
            ];
        }

        $plan['totals'] = self::totals($plan);

        return $plan;
    }

    /** @return array{created: int, skipped: int} */
    public static function totals(array $plan): array
    {
        $created = count($plan['items']['create']) + count($plan['profiles']['create']) + count($plan['grids']['create'])
            + ($plan['template']['action'] === 'create' ? 1 : 0)
            + ($plan['settings']['action'] === 'fill' ? 1 : 0);

        $skipped = count($plan['items']['skip']) + count($plan['profiles']['skip']) + count($plan['grids']['skip'])
            + ($plan['template']['action'] === 'skip' ? 1 : 0)
            + ($plan['settings']['action'] === 'skip' ? 1 : 0);

        return ['created' => $created, 'skipped' => $skipped];
    }

    /**
     * Rapport léger à garder dans l'historique : qui a été créé, qui a été ignoré (sans les lignes détaillées).
     *
     * @return array<string, mixed>
     */
    public static function summary(array $plan): array
    {
        return [
            'items_created' => array_column($plan['items']['create'], 'code'),
            'items_skipped' => array_column($plan['items']['skip'], 'code'),
            'profiles_created' => array_column($plan['profiles']['create'], 'name'),
            'profiles_skipped' => array_column($plan['profiles']['skip'], 'name'),
            'grids_created' => array_column($plan['grids']['create'], 'name'),
            'grids_skipped' => array_column($plan['grids']['skip'], 'name'),
            'template' => $plan['template']['action'],
            'template_is_default' => (bool) $plan['template']['make_default'],
            'settings' => $plan['settings']['action'],
            'totals' => $plan['totals'] ?? self::totals($plan),
        ];
    }

    /**
     * Simule ce que l'entreprise possède APRÈS l'application du plan (sert à vérifier qu'une 2e application ne crée rien).
     *
     * @param array<string, mixed> $existing
     *
     * @return array<string, mixed>
     */
    public static function afterApply(array $existing, array $plan): array
    {
        foreach ($plan['items']['create'] as $row) {
            $existing['item_codes'][] = $row['code'];
        }

        foreach ($plan['profiles']['create'] as $row) {
            $existing['profile_names'][] = $row['name'];
        }

        foreach ($plan['grids']['create'] as $row) {
            $existing['grid_names'][] = $row['name'];
        }

        if ($plan['template']['action'] === 'create') {
            $existing['template_names'][] = $plan['template']['row']['name'];
            $existing['has_default_template'] = ! empty($existing['has_default_template']) || $plan['template']['make_default'];
        }

        if ($plan['settings']['action'] === 'fill') {
            $existing['convention_name'] = $plan['settings']['convention_name'];
        }

        return $existing;
    }

    protected static function name(mixed $name): string
    {
        return mb_strtolower(trim((string) $name));
    }
}
