<?php

namespace App\Services\Payroll;

/**
 * Contrôle PUR du contenu (payload JSON) d'un paquet par secteur. Aucune base de données.
 *
 * Forme attendue :
 *   items    : [{code, label, type, calc_type, subject_to_social?, taxable?, is_compulsory?, default_amount?, default_rate?, ref_code?, scale?, sort?, notes?}]
 *   profiles : [{name, convention?, description?, items: [{code, amount?, rate?}]}]
 *   grids    : [{name, convention?, grades: [{code, label?, steps: [{label, amount}]}]}]
 *   template : {name, show_employer_part?, show_item_codes?, group_lines?, footer_text?}
 *   settings : {convention_name?}
 *
 * Un paquet est autonome : un modèle ne peut citer que des rubriques du même paquet. Les rubriques fournies par le moteur
 * (calc_type « system ») sont interdites : elles viennent des « rubriques de départ ».
 */
class PackageSchema
{
    public const TOP_KEYS = ['items', 'profiles', 'grids', 'template', 'settings'];

    public const ITEM_TYPES = ['gain', 'gross_deduction', 'benefit_in_kind', 'deduction_internal', 'deduction_individual'];

    public const CALC_TYPES = ['fixed', 'percent_base', 'percent_gross', 'percent_item', 'qty_rate', 'scale'];

    /** Droit de lecture/mise à jour nécessaire pour appliquer chaque partie d'un paquet. */
    public const RESOURCES = [
        'items' => 'pay_items',
        'profiles' => 'pay_profiles',
        'grids' => 'salary_grids',
        'template' => 'payroll_settings',
        'settings' => 'payroll_settings',
    ];

    /** @return array<int, string> messages d'erreur (vide = paquet valide) */
    public static function validate(mixed $payload): array
    {
        if (! is_array($payload)) {
            return ['Le contenu du paquet doit être un objet JSON.'];
        }

        $errors = [];

        foreach (array_keys($payload) as $key) {
            if (! in_array($key, self::TOP_KEYS, true)) {
                $errors[] = "Clé inconnue « $key » (attendu : items, profiles, grids, template, settings).";
            }
        }

        $itemCodes = self::validateItems($payload, $errors);
        self::validateProfiles($payload, $itemCodes, $errors);
        self::validateGrids($payload, $errors);
        self::validateTemplate($payload, $errors);
        self::validateSettings($payload, $errors);

        return $errors;
    }

    /** @return array{items: int, profiles: int, grids: int, grades: int, template: int, settings: int} */
    public static function counts(mixed $payload): array
    {
        $payload = is_array($payload) ? $payload : [];
        $grids = is_array($payload['grids'] ?? null) ? $payload['grids'] : [];
        $grades = 0;

        foreach ($grids as $g) {
            $grades += is_array($g) && is_array($g['grades'] ?? null) ? count($g['grades']) : 0;
        }

        return [
            'items' => is_array($payload['items'] ?? null) ? count($payload['items']) : 0,
            'profiles' => is_array($payload['profiles'] ?? null) ? count($payload['profiles']) : 0,
            'grids' => count($grids),
            'grades' => $grades,
            'template' => ! empty($payload['template']) ? 1 : 0,
            'settings' => ! empty($payload['settings']) ? 1 : 0,
        ];
    }

    /** Un paquet sans rien à appliquer est une « coquille vide » : visible sur la plateforme, jamais proposé aux entreprises. */
    public static function isEmpty(mixed $payload): bool
    {
        $c = self::counts($payload);

        return ($c['items'] + $c['profiles'] + $c['grids'] + $c['template'] + $c['settings']) === 0;
    }

    /** @return array<int, string> ressources dont l'utilisateur doit avoir le droit de mise à jour pour appliquer ce paquet */
    public static function requiredResources(mixed $payload): array
    {
        $c = self::counts($payload);
        $resources = [];

        foreach (self::RESOURCES as $part => $resource) {
            if ($c[$part] > 0) {
                $resources[$resource] = $resource;
            }
        }

        return array_values($resources);
    }

    // ───── Contrôles par partie ─────

    /**
     * @param array<string, mixed> $payload
     * @param array<int, string> $errors
     *
     * @return array<string, bool> codes de rubriques du paquet (en majuscules)
     */
    protected static function validateItems(array $payload, array &$errors): array
    {
        $codes = [];
        $items = self::listOf($payload, 'items', $errors);

        foreach ($items as $i => $it) {
            $at = "items[$i]";

            if (! is_array($it)) {
                $errors[] = "$at : une rubrique doit être un objet.";

                continue;
            }

            if (array_key_exists('is_system', $it) || array_key_exists('system_key', $it)) {
                $errors[] = "$at : les rubriques fournies par le moteur ne se mettent pas dans un paquet.";
            }

            $code = $it['code'] ?? null;

            if (! is_string($code) || ! preg_match('/^[A-Za-z0-9]{1,10}$/', $code)) {
                $errors[] = "$at.code : 1 à 10 lettres ou chiffres attendus.";
            } else {
                $upper = strtoupper($code);

                if (isset($codes[$upper])) {
                    $errors[] = "$at.code : le code « $code » est en double.";
                }

                $codes[$upper] = true;
            }

            if (! is_string($it['label'] ?? null) || trim($it['label']) === '' || mb_strlen($it['label']) > 120) {
                $errors[] = "$at.label : un libellé de 1 à 120 caractères est attendu.";
            }

            if (! in_array($it['type'] ?? null, self::ITEM_TYPES, true)) {
                $errors[] = "$at.type : valeur attendue parmi " . implode(', ', self::ITEM_TYPES) . '.';
            }

            $calc = $it['calc_type'] ?? null;

            if (! in_array($calc, self::CALC_TYPES, true)) {
                $errors[] = "$at.calc_type : valeur attendue parmi " . implode(', ', self::CALC_TYPES) . '.';
            }

            foreach (['subject_to_social', 'taxable', 'is_compulsory'] as $flag) {
                if (array_key_exists($flag, $it) && ! is_bool($it[$flag])) {
                    $errors[] = "$at.$flag : true ou false attendu.";
                }
            }

            if (isset($it['default_amount']) && ! self::isUint($it['default_amount'])) {
                $errors[] = "$at.default_amount : un montant entier positif ou nul est attendu.";
            }

            if (isset($it['default_rate']) && ! self::isRate($it['default_rate'])) {
                $errors[] = "$at.default_rate : un taux entre 0 et 100 est attendu.";
            }

            if (isset($it['sort']) && ! self::isUint($it['sort'])) {
                $errors[] = "$at.sort : un entier positif ou nul est attendu.";
            }

            if (array_key_exists('notes', $it) && $it['notes'] !== null && ! is_string($it['notes'])) {
                $errors[] = "$at.notes : un texte est attendu.";
            }

            if ($calc === 'scale') {
                self::validateScale($it['scale'] ?? [], $at, $errors);
            } elseif (! empty($it['scale'])) {
                $errors[] = "$at.scale : un barème n'est permis que pour le type de calcul « scale ».";
            }
        }

        // La rubrique de référence d'un « % d'une autre rubrique » doit exister dans le paquet.
        foreach ($items as $i => $it) {
            if (is_array($it) && ($it['calc_type'] ?? null) === 'percent_item') {
                $ref = $it['ref_code'] ?? null;
                $own = is_string($it['code'] ?? null) ? strtoupper($it['code']) : null;

                if (! is_string($ref) || ! isset($codes[strtoupper($ref)])) {
                    $errors[] = "items[$i].ref_code : la rubrique de référence doit exister dans le paquet.";
                } elseif (strtoupper($ref) === $own) {
                    $errors[] = "items[$i].ref_code : une rubrique ne peut pas se référencer elle-même.";
                }
            } elseif (is_array($it) && ! empty($it['ref_code'])) {
                $errors[] = "items[$i].ref_code : n'est permis que pour le type de calcul « percent_item ».";
            }
        }

        return $codes;
    }

    /**
     * @param array<int, string> $errors
     */
    protected static function validateScale(mixed $scale, string $at, array &$errors): void
    {
        if (! is_array($scale) || ! array_is_list($scale)) {
            $errors[] = "$at.scale : une liste de lignes {from_years, rate} est attendue.";

            return;
        }

        $seen = [];

        foreach ($scale as $j => $row) {
            if (! is_array($row) || ! self::isUint($row['from_years'] ?? null) || ! self::isRate($row['rate'] ?? null)) {
                $errors[] = "$at.scale[$j] : {from_years: entier, rate: 0 à 100} attendu.";

                continue;
            }

            $years = (int) $row['from_years'];

            if (isset($seen[$years])) {
                $errors[] = "$at.scale[$j] : « à partir de $years ans » est en double.";
            }

            $seen[$years] = true;
        }
    }

    /**
     * @param array<string, mixed> $payload
     * @param array<string, bool> $itemCodes
     * @param array<int, string> $errors
     */
    protected static function validateProfiles(array $payload, array $itemCodes, array &$errors): void
    {
        $names = [];

        foreach (self::listOf($payload, 'profiles', $errors) as $i => $p) {
            $at = "profiles[$i]";

            if (! is_array($p)) {
                $errors[] = "$at : un modèle doit être un objet.";

                continue;
            }

            $name = $p['name'] ?? null;

            if (! is_string($name) || trim($name) === '' || mb_strlen($name) > 120) {
                $errors[] = "$at.name : un nom de 1 à 120 caractères est attendu.";
            } else {
                $key = mb_strtolower(trim($name));

                if (isset($names[$key])) {
                    $errors[] = "$at.name : le nom « $name » est en double.";
                }

                $names[$key] = true;
            }

            foreach (['convention' => 150, 'description' => 2000] as $field => $max) {
                if (isset($p[$field]) && (! is_string($p[$field]) || mb_strlen($p[$field]) > $max)) {
                    $errors[] = "$at.$field : un texte de $max caractères au plus est attendu.";
                }
            }

            $lines = $p['items'] ?? [];

            if (! is_array($lines) || ! array_is_list($lines)) {
                $errors[] = "$at.items : une liste de lignes {code, amount?, rate?} est attendue.";

                continue;
            }

            $seen = [];

            foreach ($lines as $j => $line) {
                $lat = "$at.items[$j]";
                $code = is_array($line) ? ($line['code'] ?? null) : null;

                if (! is_string($code) || ! isset($itemCodes[strtoupper($code)])) {
                    $errors[] = "$lat.code : la rubrique doit exister dans le paquet.";

                    continue;
                }

                if (isset($seen[strtoupper($code)])) {
                    $errors[] = "$lat.code : la rubrique « $code » est déjà dans ce modèle.";
                }

                $seen[strtoupper($code)] = true;

                if (isset($line['amount']) && ! self::isUint($line['amount'])) {
                    $errors[] = "$lat.amount : un montant entier positif ou nul est attendu.";
                }

                if (isset($line['rate']) && ! self::isRate($line['rate'])) {
                    $errors[] = "$lat.rate : un taux entre 0 et 100 est attendu.";
                }
            }
        }
    }

    /**
     * @param array<string, mixed> $payload
     * @param array<int, string> $errors
     */
    protected static function validateGrids(array $payload, array &$errors): void
    {
        $names = [];

        foreach (self::listOf($payload, 'grids', $errors) as $i => $g) {
            $at = "grids[$i]";

            if (! is_array($g)) {
                $errors[] = "$at : une grille doit être un objet.";

                continue;
            }

            $name = $g['name'] ?? null;

            if (! is_string($name) || trim($name) === '' || mb_strlen($name) > 120) {
                $errors[] = "$at.name : un nom de 1 à 120 caractères est attendu.";
            } else {
                $key = mb_strtolower(trim($name));

                if (isset($names[$key])) {
                    $errors[] = "$at.name : le nom « $name » est en double.";
                }

                $names[$key] = true;
            }

            if (isset($g['convention']) && (! is_string($g['convention']) || mb_strlen($g['convention']) > 150)) {
                $errors[] = "$at.convention : un texte de 150 caractères au plus est attendu.";
            }

            $grades = $g['grades'] ?? [];

            if (! is_array($grades) || ! array_is_list($grades)) {
                $errors[] = "$at.grades : une liste de catégories est attendue.";

                continue;
            }

            $codes = [];

            foreach ($grades as $j => $grade) {
                $gat = "$at.grades[$j]";

                if (! is_array($grade)) {
                    $errors[] = "$gat : une catégorie doit être un objet.";

                    continue;
                }

                $code = $grade['code'] ?? null;

                if (! is_string($code) || trim($code) === '' || mb_strlen($code) > 20) {
                    $errors[] = "$gat.code : un code de 1 à 20 caractères est attendu.";
                } else {
                    if (isset($codes[strtoupper($code)])) {
                        $errors[] = "$gat.code : le code « $code » est en double.";
                    }

                    $codes[strtoupper($code)] = true;
                }

                if (isset($grade['label']) && (! is_string($grade['label']) || mb_strlen($grade['label']) > 120)) {
                    $errors[] = "$gat.label : un texte de 120 caractères au plus est attendu.";
                }

                $steps = $grade['steps'] ?? [];

                if (! is_array($steps) || ! array_is_list($steps)) {
                    $errors[] = "$gat.steps : une liste d'échelons {label, amount} est attendue.";

                    continue;
                }

                foreach ($steps as $k => $step) {
                    if (! is_array($step) || ! is_string($step['label'] ?? null) || trim($step['label']) === '' || mb_strlen($step['label']) > 60 || ! self::isUint($step['amount'] ?? null)) {
                        $errors[] = "$gat.steps[$k] : {label: 1 à 60 caractères, amount: entier positif ou nul} attendu.";
                    }
                }
            }
        }
    }

    /**
     * @param array<string, mixed> $payload
     * @param array<int, string> $errors
     */
    protected static function validateTemplate(array $payload, array &$errors): void
    {
        if (! array_key_exists('template', $payload) || $payload['template'] === null) {
            return;
        }

        $t = $payload['template'];

        if (! is_array($t) || array_is_list($t) && $t !== []) {
            $errors[] = 'template : un objet est attendu.';

            return;
        }

        if ($t === []) {
            return;
        }

        if (! is_string($t['name'] ?? null) || trim($t['name']) === '' || mb_strlen($t['name']) > 120) {
            $errors[] = 'template.name : un nom de 1 à 120 caractères est attendu.';
        }

        foreach (['show_employer_part', 'show_item_codes', 'group_lines'] as $flag) {
            if (array_key_exists($flag, $t) && ! is_bool($t[$flag])) {
                $errors[] = "template.$flag : true ou false attendu.";
            }
        }

        if (isset($t['footer_text']) && (! is_string($t['footer_text']) || mb_strlen($t['footer_text']) > 2000)) {
            $errors[] = 'template.footer_text : un texte de 2000 caractères au plus est attendu.';
        }
    }

    /**
     * @param array<string, mixed> $payload
     * @param array<int, string> $errors
     */
    protected static function validateSettings(array $payload, array &$errors): void
    {
        if (! array_key_exists('settings', $payload) || $payload['settings'] === null || $payload['settings'] === []) {
            return;
        }

        $s = $payload['settings'];

        if (! is_array($s) || array_is_list($s)) {
            $errors[] = 'settings : un objet est attendu.';

            return;
        }

        foreach (array_keys($s) as $key) {
            if ($key !== 'convention_name') {
                $errors[] = "settings.$key : seule la clé « convention_name » est permise (un paquet ne change jamais les autres réglages).";
            }
        }

        if (isset($s['convention_name']) && (! is_string($s['convention_name']) || trim($s['convention_name']) === '' || mb_strlen($s['convention_name']) > 150)) {
            $errors[] = 'settings.convention_name : un texte de 1 à 150 caractères est attendu.';
        }
    }

    // ───── Petits outils ─────

    /**
     * @param array<string, mixed> $payload
     * @param array<int, string> $errors
     *
     * @return array<int, mixed>
     */
    protected static function listOf(array $payload, string $key, array &$errors): array
    {
        $value = $payload[$key] ?? [];

        if (! is_array($value) || ! array_is_list($value)) {
            $errors[] = "$key : une liste est attendue.";

            return [];
        }

        return $value;
    }

    protected static function isUint(mixed $v): bool
    {
        return (is_int($v) && $v >= 0) || (is_string($v) && ctype_digit($v));
    }

    protected static function isRate(mixed $v): bool
    {
        return is_numeric($v) && ! is_string($v) && $v >= 0 && $v <= 100;
    }
}
