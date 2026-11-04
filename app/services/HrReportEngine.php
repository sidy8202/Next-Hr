<?php

namespace App\Services;

use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Throwable;

/**
 * Moteur des rapports RH d'UNE entreprise. Lit les tables directement avec le filtre d'entreprise explicite :
 * il ne dépend ni des scopes de société ni du contenu des modèles (Termination, Contract…).
 * Chaque source est protégée : une table absente ou différente donne un bloc vide, jamais une page en erreur.
 * Les tableaux et les graphiques viennent des MÊMES données.
 */
class HrReportEngine
{
    public CarbonImmutable $from;
    public CarbonImmutable $to;
    public ?CarbonImmutable $pFrom = null;
    public ?CarbonImmutable $pTo = null;

    private CarbonImmutable $today;
    private array $emps = [];          // id => employé retenu par les filtres
    private array $exit = [];          // id => [date, type, reason, category]
    private array $contract = [];      // id => dernier contrat
    private array $unknownExit = [];   // inactifs sans sortie enregistrée (exclus des calculs, signalés)
    private array $depts = [];
    private array $positions = [];
    private array $leaves = [];

    public static function forget(int $companyId): void
    {
        Cache::forget("hr.base.{$companyId}");
    }

    public function __construct(private int $cid, private array $f, private bool $showNames = false)
    {
        $this->today = CarbonImmutable::today();
        [$this->from, $this->to] = ReportPeriod::resolve($f['preset'] ?? '12m', $f['from'] ?? null, $f['to'] ?? null);

        if ($prev = ReportPeriod::previous($this->from, $this->to, $f['compare'] ?? 'none')) {
            [$this->pFrom, $this->pTo] = $prev;
        }

        $this->load();
    }

    private static function safe(callable $fn, mixed $default): mixed
    {
        try {
            return $fn();
        } catch (Throwable) {
            return $default;
        }
    }

    private function base(): array
    {
        return Cache::remember("hr.base.{$this->cid}", 60, function () {
            $cid = $this->cid;

            return [
                'emps' => DB::table('employees')->where('company_id', $cid)->whereNull('deleted_at')
                    ->get(['id', 'first_name', 'last_name', 'department_id', 'position_id', 'gender', 'birth_date', 'hire_date', 'status', 'work_permit_expires_at'])->map(fn ($r) => (array) $r)->all(),
                'contracts' => self::safe(fn () => DB::table('contracts')->where('company_id', $cid)
                    ->whereIn('id', function ($q) use ($cid) { $q->from('contracts')->selectRaw('max(id)')->where('company_id', $cid)->groupBy('employee_id'); })
                    ->get(['employee_id', 'type', 'status', 'end_date', 'trial_end_date', 'base_salary'])->map(fn ($r) => (array) $r)->all(), []),
                'terms' => self::safe(fn () => DB::table('terminations as t')->leftJoin('termination_reasons as r', 'r.id', '=', 't.termination_reason_id')
                    ->where('t.company_id', $cid)->get(['t.employee_id', 't.type', 't.last_day', 'r.label as reason', 'r.category'])->map(fn ($r) => (array) $r)->all(), []),
                'leaves' => self::safe(fn () => DB::table('leave_requests')->where('company_id', $cid)->where('status', 'approved')
                    ->get(['employee_id', 'start_date', 'days'])->map(fn ($r) => (array) $r)->all(), []),
                'depts' => self::safe(fn () => DB::table('departments')->where('company_id', $cid)->pluck('name', 'id')->all(), []),
                'positions' => self::safe(fn () => DB::table('positions')->where('company_id', $cid)->pluck('title', 'id')->all(), []),
            ];
        });
    }

    private function load(): void
    {
        $b = $this->base();
        $this->depts = $b['depts'];
        $this->positions = $b['positions'];

        foreach ($b['terms'] as $t) {
            $this->exit[(int) $t['employee_id']] = ['date' => substr((string) $t['last_day'], 0, 10), 'type' => $t['type'], 'reason' => $t['reason'], 'category' => $t['category']];
        }

        foreach ($b['contracts'] as $c) {
            $this->contract[(int) $c['employee_id']] = $c;
        }

        $deptSet = array_map('intval', $this->f['depts'] ?? []);
        $posSet = array_map('intval', $this->f['positions'] ?? []);
        $ctSet = array_map('strtoupper', $this->f['contracts'] ?? []);
        $gSet = $this->f['genders'] ?? [];

        foreach ($b['emps'] as $e) {
            $id = (int) $e['id'];
            $e['hire_date'] = substr((string) $e['hire_date'], 0, 10);
            $e['g'] = HrMath::genderKey($e['gender']);
            $e['ctype'] = isset($this->contract[$id]) ? strtoupper(trim((string) $this->contract[$id]['type'])) : 'SANS CONTRAT';

            if ($deptSet && ! in_array((int) $e['department_id'], $deptSet, true)) { continue; }
            if ($posSet && ! in_array((int) $e['position_id'], $posSet, true)) { continue; }
            if ($gSet && ! in_array($e['g'], $gSet, true)) { continue; }
            if ($ctSet && ! in_array($e['ctype'], $ctSet, true)) { continue; }

            $this->emps[$id] = $e;

            if (($e['status'] ?? 'active') !== 'active' && ! isset($this->exit[$id])) {
                $this->unknownExit[$id] = true;
            }
        }

        $ids = array_flip(array_keys($this->emps));
        $this->leaves = array_values(array_filter($b['leaves'], fn ($l) => isset($ids[(int) $l['employee_id']])));
    }

    /** Listes proposées dans les filtres (cases à cocher). */
    public function options(): array
    {
        $b = $this->base();
        $types = collect($b['contracts'])->pluck('type')->map(fn ($t) => strtoupper(trim((string) $t)))->filter()->unique()->sort()->values()->all();

        return [
            'depts' => collect($b['depts'])->sort()->all(),
            'positions' => collect($b['positions'])->sort()->all(),
            'contracts' => array_merge($types, ['SANS CONTRAT']),
            'genders' => ['F' => 'Femmes', 'M' => 'Hommes', 'N' => 'Non renseigné'],
        ];
    }

    /* ───────────── Mesures de base ───────────── */

    /** Identifiants des employés en poste à la fin du jour $d. Entrée le jour même incluse ; dernier jour de travail = encore en poste ce jour-là. */
    private function headAt(string $d, ?array $only = null): array
    {
        $out = [];

        foreach ($this->emps as $id => $e) {
            if (isset($this->unknownExit[$id]) || $e['hire_date'] > $d) { continue; }
            $x = $this->exit[$id]['date'] ?? null;
            if ($x !== null && $x <= $d) { continue; }
            if ($only !== null && ! isset($only[$id])) { continue; }
            $out[] = $id;
        }

        return $out;
    }

    private function core(CarbonImmutable $from, CarbonImmutable $to, ?array $only = null): array
    {
        $a = $from->toDateString();
        $b = $to->toDateString();
        $end = ($to->gt($this->today) ? $this->today : $to)->toDateString();
        $start = $this->headAt($from->subDay()->toDateString(), $only);
        $endHead = $this->headAt($end, $only);

        $entries = $exits = 0;

        foreach ($this->emps as $id => $e) {
            if (isset($this->unknownExit[$id]) || ($only !== null && ! isset($only[$id]))) { continue; }
            if ($e['hire_date'] >= $a && $e['hire_date'] <= $b) { $entries++; }
            $x = $this->exit[$id]['date'] ?? null;
            if ($x !== null && $x >= $a && $x <= $b) { $exits++; }
        }

        return ['head_start' => count($start), 'head_end' => count($endHead), 'head_ids' => $endHead, 'entries' => $entries, 'exits' => $exits, 'turnover' => HrMath::turnover($exits, count($start), count($endHead))];
    }

    private function metrics(CarbonImmutable $from, CarbonImmutable $to): array
    {
        $m = $this->core($from, $to);
        $end = ($to->gt($this->today) ? $this->today : $to)->toDateString();
        $a = $from->toDateString();
        $b = $to->toDateString();

        $sen = $ages = [];
        $f = $mm = 0;
        $salary = 0;
        $known = 0;

        foreach ($m['head_ids'] as $id) {
            $e = $this->emps[$id];
            $sen[] = HrMath::years($e['hire_date'], $end);
            if (($age = HrMath::ageAt($e['birth_date'], $end)) !== null) { $ages[] = $age; }
            if ($e['g'] === 'F') { $f++; } elseif ($e['g'] === 'M') { $mm++; }

            $sal = (float) ($this->contract[$id]['base_salary'] ?? 0);
            if ($sal > 0) { $salary += $sal; $known++; }
        }

        $ids = array_flip($m['head_ids']);

        return $m + [
            'seniority_avg' => $sen ? round(array_sum($sen) / count($sen), 1) : null,
            'age_avg' => $ages ? round(array_sum($ages) / count($ages), 1) : null,
            'women_pct' => ($f + $mm) > 0 ? (int) round($f / ($f + $mm) * 100) : null,
            'leave_days' => (int) round(collect($this->leaves)->filter(fn ($l) => substr((string) $l['start_date'], 0, 10) >= $a && substr((string) $l['start_date'], 0, 10) <= $b)->sum('days')),
            // Source salaire : salaires de base des contrats. Quand le module de paie sera installé, ce bloc passera aux bulletins.
            'salary_mass' => (int) round($salary), 'salary_known' => $known,
        ];
    }

    /** @return array{labels: array, head: array, entries: array, exits: array} un point par mois de la période (36 mois maximum). */
    private function monthly(): array
    {
        $months = array_slice(ReportPeriod::months($this->from, $this->to), -36);
        $long = count($months) > 12;
        $out = ['labels' => [], 'head' => [], 'entries' => [], 'exits' => []];

        foreach ($months as $m) {
            $a = $m->lt($this->from) ? $this->from : $m;
            $z = $m->endOfMonth()->startOfDay();
            $b = $z->gt($this->to) ? $this->to : $z;
            $c = $this->core($a, $b);
            $out['labels'][] = $m->translatedFormat($long ? 'M y' : 'M');
            $out['head'][] = $c['head_end'];
            $out['entries'][] = $c['entries'];
            $out['exits'][] = $c['exits'];
        }

        return $out;
    }

    private function deptName(?int $id): string
    {
        return $id && isset($this->depts[$id]) ? $this->depts[$id] : 'Sans département';
    }

    private function name(array $e): string
    {
        return trim(($e['first_name'] ?? '').' '.($e['last_name'] ?? ''));
    }

    /** Une ligne par département : effectif, ancienneté, féminisation, âge, entrées, sorties, rotation. */
    private function byDepartment(string $end): array
    {
        $groups = [];

        foreach ($this->emps as $id => $e) {
            if (! isset($this->unknownExit[$id])) { $groups[(int) $e['department_id']][$id] = true; }
        }

        $rows = [];

        foreach ($groups as $deptId => $set) {
            $c = $this->core($this->from, $this->to, $set);

            if ($c['head_end'] === 0 && $c['exits'] === 0 && $c['entries'] === 0) { continue; }

            $sen = $ages = [];
            $f = $m = 0;

            foreach ($c['head_ids'] as $id) {
                $e = $this->emps[$id];
                $sen[] = HrMath::years($e['hire_date'], $end);
                if (($age = HrMath::ageAt($e['birth_date'], $end)) !== null) { $ages[] = $age; }
                if ($e['g'] === 'F') { $f++; } elseif ($e['g'] === 'M') { $m++; }
            }

            $rows[] = [
                'label' => $this->deptName($deptId), 'head' => $c['head_end'], 'entries' => $c['entries'], 'exits' => $c['exits'], 'turnover' => $c['turnover'],
                'seniority' => $sen ? round(array_sum($sen) / count($sen), 1) : null, 'age' => $ages ? round(array_sum($ages) / count($ages), 1) : null,
                'women' => ($f + $m) > 0 ? (int) round($f / ($f + $m) * 100) : null,
            ];
        }

        usort($rows, fn ($a, $b) => $b['head'] <=> $a['head']);

        return $rows;
    }

    /* ───────────── Onglet Synthèse ───────────── */

    public function synthesis(): array
    {
        $cur = $this->metrics($this->from, $this->to);
        $prev = $this->pFrom ? $this->metrics($this->pFrom, $this->pTo) : null;
        $keys = ['head_end', 'entries', 'exits', 'seniority_avg', 'leave_days', 'salary_mass'];
        $delta = [];

        foreach ($keys as $k) {
            $delta[$k] = $prev && $prev[$k] !== null && $cur[$k] !== null ? ReportPeriod::delta($cur[$k], $prev[$k]) : null;
        }

        $delta['turnover_pts'] = $prev && $prev['turnover'] !== null && $cur['turnover'] !== null ? round($cur['turnover'] - $prev['turnover'], 1) : null;

        $end = ($this->to->gt($this->today) ? $this->today : $this->to)->toDateString();
        $byDept = $this->byDepartment($end);
        $alerts = $this->alerts();

        return [
            'cur' => $cur, 'prev' => $prev, 'delta' => $delta, 'monthly' => $this->monthly(), 'by_dept' => $byDept,
            'rotation' => collect($byDept)->filter(fn ($r) => $r['exits'] > 0)->sortByDesc('exits')->take(8)->map(fn ($r) => ['label' => $r['label'], 'value' => $r['exits'], 'sub' => $r['turnover'] === null ? null : 'rotation '.str_replace('.', ',', (string) $r['turnover']).' %'])->values()->all(),
            'alerts' => $alerts, 'quality' => count($this->unknownExit),
            'insights' => $this->insights($cur, $prev, $delta, $byDept),
            'from' => $this->from, 'to' => $this->to, 'pFrom' => $this->pFrom, 'pTo' => $this->pTo, 'population' => count($this->emps),
        ];
    }

    private function alerts(): array
    {
        $t = $this->today->toDateString();
        $in60 = $this->today->addDays(60)->toDateString();
        $in30 = $this->today->addDays(30)->toDateString();
        $now = $this->headAt($t);
        $out = ['contract_end' => [], 'trial_end' => [], 'permit' => [], 'no_contract' => 0];

        foreach ($now as $id) {
            $e = $this->emps[$id];
            $c = $this->contract[$id] ?? null;

            if (! $c) { $out['no_contract']++; continue; }

            $end = $c['end_date'] ? substr((string) $c['end_date'], 0, 10) : null;
            $trial = $c['trial_end_date'] ? substr((string) $c['trial_end_date'], 0, 10) : null;

            if ($end && $end >= $t && $end <= $in60) { $out['contract_end'][] = ['label' => $this->showNames ? $this->name($e) : $this->deptName((int) $e['department_id']), 'date' => $end, 'type' => $c['type']]; }
            if ($trial && $trial >= $t && $trial <= $in30) { $out['trial_end'][] = ['label' => $this->showNames ? $this->name($e) : $this->deptName((int) $e['department_id']), 'date' => $trial]; }

            $permit = $e['work_permit_expires_at'] ? substr((string) $e['work_permit_expires_at'], 0, 10) : null;
            if ($permit && $permit >= $t && $permit <= $in60) { $out['permit'][] = ['label' => $this->showNames ? $this->name($e) : $this->deptName((int) $e['department_id']), 'date' => $permit]; }
        }

        foreach (['contract_end', 'trial_end', 'permit'] as $k) {
            usort($out[$k], fn ($a, $b) => $a['date'] <=> $b['date']);
        }

        return $out;
    }

    private function insights(array $cur, ?array $prev, array $delta, array $byDept): array
    {
        $out = [];
        $f = fn ($v) => str_replace('.', ',', (string) $v);

        if ($cur['head_end'] > 0) {
            $out[] = 'L’effectif est de '.$cur['head_end'].' personne(s) à la fin de la période, avec '.$cur['entries'].' arrivée(s) et '.$cur['exits'].' départ(s).';
        }

        if ($cur['turnover'] !== null) {
            $txt = 'Le taux de rotation est de '.$f($cur['turnover']).' % sur la période';
            $txt .= $delta['turnover_pts'] !== null ? ($delta['turnover_pts'] == 0 ? ', stable par rapport à la comparaison.' : ', soit '.($delta['turnover_pts'] > 0 ? '+' : '').$f($delta['turnover_pts']).' point(s) par rapport à la comparaison.') : '.';
            $out[] = $txt;
        }

        $worst = collect($byDept)->filter(fn ($r) => $r['exits'] > 0 && $r['turnover'] !== null)->sortByDesc('turnover')->first();

        if ($worst && $cur['turnover'] !== null && $worst['turnover'] > max($cur['turnover'] * 1.5, 5) && count($byDept) > 1) {
            $out[] = 'Point d’attention : le département « '.$worst['label'].' » a une rotation de '.$f($worst['turnover']).' %, nettement au-dessus de la moyenne ('.$f($cur['turnover']).' %).';
        }

        if ($cur['seniority_avg'] !== null) {
            $out[] = 'L’ancienneté moyenne est de '.$f($cur['seniority_avg']).' an(s)'.($cur['women_pct'] !== null ? ' et les femmes représentent '.$cur['women_pct'].' % de l’effectif.' : '.');
        }

        if ($prev && $delta['head_end'] !== null && $delta['head_end'] != 0) {
            $out[] = 'L’effectif a '.($delta['head_end'] > 0 ? 'progressé' : 'reculé').' de '.abs($delta['head_end']).' % par rapport à la comparaison.';
        }

        return $out;
    }

    /* ───────────── Onglet Effectif ───────────── */

    public function workforce(): array
    {
        $end = ($this->to->gt($this->today) ? $this->today : $this->to)->toDateString();
        $c = $this->core($this->from, $this->to);
        $ids = $c['head_ids'];
        $total = max(1, count($ids));

        $dept = $pos = $ctype = $gender = [];
        $sen = array_fill(0, 5, 0);
        $age = array_fill(0, 6, 0);
        $matrix = [];

        foreach ($ids as $id) {
            $e = $this->emps[$id];
            $years = HrMath::years($e['hire_date'], $end);
            $si = HrMath::seniorityIndex($years);
            $d = $this->deptName((int) $e['department_id']);

            $dept[$d] = ($dept[$d] ?? 0) + 1;
            $p = $e['position_id'] && isset($this->positions[$e['position_id']]) ? $this->positions[$e['position_id']] : 'Sans poste';
            $pos[$p] = ($pos[$p] ?? 0) + 1;
            $ctype[$e['ctype']] = ($ctype[$e['ctype']] ?? 0) + 1;
            $gender[$e['g']] = ($gender[$e['g']] ?? 0) + 1;
            $sen[$si]++;
            $age[HrMath::ageIndex(HrMath::ageAt($e['birth_date'], $end))]++;
            $matrix[$d] ??= array_fill(0, 5, 0);
            $matrix[$d][$si]++;
        }

        arsort($dept);
        arsort($pos);
        arsort($ctype);
        uasort($matrix, fn ($a, $b) => array_sum($b) <=> array_sum($a));

        $colors = ['#6366f1', '#10b981', '#f59e0b', '#f43f5e', '#0ea5e9', '#8b5cf6', '#14b8a6', '#94a3b8'];
        $gLabels = ['F' => 'Femmes', 'M' => 'Hommes', 'N' => 'Non renseigné'];
        $gColors = ['F' => '#f43f5e', 'M' => '#0ea5e9', 'N' => '#94a3b8'];

        return [
            'total' => count($ids), 'end' => $end,
            'dept_bars' => collect($dept)->take(10)->map(fn ($n, $l) => ['label' => $l, 'value' => $n, 'sub' => (int) round($n / $total * 100).' %'])->values()->all(),
            'positions' => collect($pos)->take(12)->map(fn ($n, $l) => ['label' => $l, 'value' => $n, 'share' => (int) round($n / $total * 100)])->values()->all(),
            'contracts' => collect($ctype)->values()->map(fn ($n, $i) => ['label' => collect($ctype)->keys()[$i], 'value' => $n, 'color' => $colors[$i % 8]])->all(),
            'gender' => collect($gender)->map(fn ($n, $k) => ['label' => $gLabels[$k], 'value' => $n, 'color' => $gColors[$k]])->values()->all(),
            'seniority' => ['labels' => HrMath::SENIORITY, 'values' => $sen],
            'ages' => ['labels' => HrMath::AGES, 'values' => $age],
            'matrix' => array_slice($matrix, 0, 10, true), 'matrix_cols' => HrMath::SENIORITY,
            'table' => $this->byDepartment($end),
            'quality' => count($this->unknownExit),
        ];
    }

    /* ───────────── Export ───────────── */

    /** @return array{0: string, 1: array, 2: array} */
    public function exportRows(string $tab): array
    {
        if ($tab === 'workforce') {
            $w = $this->workforce();

            return ['effectif', ['Département', 'Effectif', 'Ancienneté moyenne (ans)', 'Âge moyen', 'Femmes (%)', 'Entrées', 'Sorties', 'Rotation (%)'],
                array_map(fn ($r) => [$r['label'], $r['head'], $r['seniority'], $r['age'], $r['women'], $r['entries'], $r['exits'], $r['turnover']], $w['table'])];
        }

        $s = $this->synthesis();
        $m = $s['monthly'];
        $rows = [];

        foreach ($m['labels'] as $i => $label) {
            $rows[] = [$label, $m['head'][$i], $m['entries'][$i], $m['exits'][$i]];
        }

        return ['synthese', ['Mois', 'Effectif fin de mois', 'Entrées', 'Sorties'], $rows];
    }
}
