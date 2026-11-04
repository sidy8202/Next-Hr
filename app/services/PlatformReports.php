<?php

namespace App\Services;

use App\Models\Company;
use App\Models\Employee;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\SubscriptionPayment;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;

/**
 * Moteur des rapports de la plateforme. Tout est calculé en mémoire à partir des filtres :
 * les tableaux et les graphiques reposent sur les MÊMES données, donc ne peuvent jamais se contredire.
 * Uniquement des agrégats : aucune donnée personnelle des employés.
 */
class PlatformReports
{
    public CarbonImmutable $from;
    public CarbonImmutable $to;
    public ?CarbonImmutable $pFrom = null;
    public ?CarbonImmutable $pTo = null;

    private CarbonImmutable $today;
    private Collection $companies;   // entreprises retenues par les filtres, indexées par id
    private Collection $subs;        // abonnements retenus
    private Collection $payments;    // paiements retenus
    private Collection $plans;
    private array $emp = [];         // effectif actif par entreprise
    private array $latest = [];      // dernier abonnement retenu par entreprise
    private array $payIndex = [];    // rang de chaque paiement dans l'historique de son entreprise (0 = premier)
    private array $cover = [];       // périodes couvertes (paiements + essais) par entreprise

    public function __construct(private array $f)
    {
        $this->today = CarbonImmutable::today();
        [$this->from, $this->to] = ReportPeriod::resolve($f['preset'] ?? '12m', $f['from'] ?? null, $f['to'] ?? null);

        if ($prev = ReportPeriod::previous($this->from, $this->to, $f['compare'] ?? 'none')) {
            [$this->pFrom, $this->pTo] = $prev;
        }

        $this->load();
    }

    /* ───────────── Chargement et filtres ───────────── */

    private function load(): void
    {
        $f = $this->f;
        $this->plans = Plan::orderBy('sort')->get()->keyBy('id');
        $this->emp = Employee::withoutGlobalScopes()->whereNull('deleted_at')->where('status', 'active')
            ->selectRaw('company_id, count(*) as c')->groupBy('company_id')->pluck('c', 'company_id')->map(fn ($v) => (int) $v)->all();

        $allSubs = Subscription::orderBy('id')->get();
        $byCompany = $allSubs->groupBy('company_id');
        $planId = ($f['plan'] ?? '') !== '' ? $this->plans->firstWhere('code', $f['plan'])?->id : null;
        $period = ($f['period'] ?? '') !== '' ? (int) $f['period'] : null;

        $subOk = fn ($s) => (! $planId || (int) $s->plan_id === (int) $planId) && (! $period || (int) $s->period_months === $period);

        $this->companies = Company::all()->filter(function (Company $c) use ($f, $byCompany, $subOk, $planId, $period) {
            if (($f['sector'] ?? '') !== '' && $c->sector !== $f['sector']) { return false; }
            if (($f['country'] ?? '') !== '' && $c->country !== $f['country']) { return false; }
            if (($f['status'] ?? '') !== '' && $c->status !== $f['status']) { return false; }
            if (($f['size'] ?? '') !== '' && ReportPeriod::sizeBracket($this->emp[$c->id] ?? 0) !== $f['size']) { return false; }
            if ($planId || $period) { return $byCompany->get($c->id, collect())->contains($subOk); }

            return true;
        })->keyBy('id');

        $this->subs = $allSubs->filter(fn ($s) => $this->companies->has($s->company_id) && $subOk($s))->values();
        $subIds = $this->subs->pluck('id')->flip();
        $this->payments = SubscriptionPayment::orderBy('paid_at')->orderBy('id')->get()->filter(fn ($p) => $subIds->has($p->subscription_id))->values();

        foreach ($this->subs->groupBy('company_id') as $cid => $list) {
            $this->latest[$cid] = $list->last();

            foreach ($list as $s) {
                if ($s->trial_ends_at && $s->starts_at) {
                    $this->cover[$cid][] = [$s->starts_at->toDateString(), $s->trial_ends_at->toDateString()];
                }
            }
        }

        foreach ($this->payments->groupBy('company_id') as $cid => $list) {
            foreach ($list->values() as $i => $p) {
                $this->payIndex[$p->id] = $i;

                if ($p->period_start && $p->period_end) {
                    $this->cover[$cid][] = [$p->period_start->toDateString(), $p->period_end->toDateString()];
                }
            }
        }
    }

    private function idSet(?array $ids): ?array
    {
        return $ids === null ? null : array_flip($ids);
    }

    private function inScope(int $companyId, ?array $set): bool
    {
        return $set === null ? $this->companies->has($companyId) : isset($set[$companyId]);
    }

    private function between(?CarbonInterface $d, string $a, string $b): bool
    {
        if (! $d) { return false; }
        $s = $d->toDateString();

        return $s >= $a && $s <= $b;
    }

    /* ───────────── Mesures de base ───────────── */

    /** Revenu mensuel récurrent à une date (abonnements payants en cours ce jour-là). */
    private function mrrAt(CarbonInterface $d, ?array $set = null): int
    {
        return (int) $this->payingSubsAt($d, $set)->sum(fn ($s) => $s->monthlyAmount());
    }

    private function payingSubsAt(CarbonInterface $d, ?array $set): Collection
    {
        $ds = $d->toDateString();

        return $this->subs->filter(fn ($s) => $this->inScope($s->company_id, $set)
            && ! in_array($s->status, ['trial', 'pending'], true)
            && $s->starts_at && $s->starts_at->toDateString() <= $ds
            && (! $s->ends_at || $s->ends_at->toDateString() >= $ds));
    }

    /** Nouvelles entreprises, souscriptions, encaissements, MRR de fin, entreprises perdues sur une période. */
    private function core(CarbonImmutable $from, CarbonImmutable $to, ?array $set = null): array
    {
        $a = $from->toDateString();
        $b = $to->toDateString();
        $pay = $this->payments->filter(fn ($p) => $this->inScope($p->company_id, $set) && $this->between($p->paid_at, $a, $b));
        $end = $to->gt($this->today) ? $this->today : $to;
        $churned = collect($this->latest)->filter(fn ($s, $cid) => $this->inScope((int) $cid, $set) && in_array($s->status, ['expired', 'cancelled'], true) && $this->between($s->ends_at, $a, $b))->count();

        return [
            'new_companies' => $this->companies->filter(fn ($c) => $this->inScope($c->id, $set) && $this->between($c->created_at, $a, $b))->count(),
            'new_subs' => $this->subs->filter(fn ($s) => $this->inScope($s->company_id, $set) && $this->between($s->created_at, $a, $b))->count(),
            'revenue' => (int) $pay->sum('amount'),
            'payments' => $pay->count(),
            'payers' => $pay->pluck('company_id')->unique()->count(),
            'mrr_end' => $this->mrrAt($end, $set),
            'churned' => $churned,
        ];
    }

    private function metrics(CarbonImmutable $from, CarbonImmutable $to, ?array $ids = null): array
    {
        $set = $this->idSet($ids);
        $m = $this->core($from, $to, $set);
        $a = $from->toDateString();
        $b = $to->toDateString();

        $m['mrr_start'] = $this->mrrAt($from->subDay(), $set);
        $m['arpa'] = $m['payers'] > 0 ? intdiv($m['revenue'], $m['payers']) : 0;

        $activeStart = $this->payingSubsAt($from->subDay(), $set)->pluck('company_id')->unique()->count();
        $m['active_start'] = $activeStart;
        $m['churn_rate'] = $activeStart > 0 ? min(100, (int) round($m['churned'] / $activeStart * 100)) : null;

        $renewals = $this->payments->filter(fn ($p) => $this->inScope($p->company_id, $set) && $p->period_start && ($this->payIndex[$p->id] ?? 0) > 0 && $this->between($p->paid_at, $a, $b))->count();
        $due = $this->subs->filter(fn ($s) => $this->inScope($s->company_id, $set) && ! in_array($s->status, ['cancelled', 'replaced', 'trial'], true) && $this->between($s->ends_at, $a, $b))->count();
        $m['renewals'] = $renewals;
        $m['due'] = $due;
        $m['renewal_rate'] = $due > 0 ? min(100, (int) round($renewals / $due * 100)) : null;

        $won = $lost = 0;

        foreach ($this->subs as $s) {
            if (! $this->inScope($s->company_id, $set) || ! $s->trial_ends_at || ! $this->between($s->created_at, $a, $b)) {
                continue;
            }

            $converted = $this->payments->contains(fn ($p) => (int) $p->company_id === (int) $s->company_id && $p->period_start && $p->paid_at->toDateString() >= $s->created_at->toDateString());

            if ($converted) { $won++; } elseif ($s->trial_ends_at->lt($this->today)) { $lost++; }
        }

        $m['trial_won'] = $won;
        $m['trial_lost'] = $lost;
        $m['trial_rate'] = ($won + $lost) > 0 ? (int) round($won / ($won + $lost) * 100) : null;
        $m['employees'] = array_sum(array_map(fn ($c) => $this->emp[$c->id] ?? 0, $this->companies->filter(fn ($c) => $this->inScope($c->id, $set))->all()));

        return $m;
    }

    public function filteredCompanies(): int
    {
        return $this->companies->count();
    }

    /* ───────────── Vue d'ensemble ───────────── */

    public function overview(): array
    {
        $cur = $this->metrics($this->from, $this->to);
        $prev = $this->pFrom ? $this->metrics($this->pFrom, $this->pTo) : null;
        $delta = fn (string $k) => $prev ? ReportPeriod::delta($cur[$k], $prev[$k]) : null;

        $rows = [];

        foreach (ReportPeriod::months($this->from, $this->to) as $m) {
            $a = $m->lt($this->from) ? $this->from : $m;
            $b = $m->endOfMonth()->startOfDay()->gt($this->to) ? $this->to : $m->endOfMonth()->startOfDay();
            $rows[] = ['label' => $m->translatedFormat('M Y')] + $this->core($a, $b);
        }

        return [
            'cur' => $cur, 'prev' => $prev, 'delta' => array_combine(['new_companies', 'new_subs', 'revenue', 'mrr_end', 'arpa', 'churned'], array_map($delta, ['new_companies', 'new_subs', 'revenue', 'mrr_end', 'arpa', 'churned'])),
            'months' => $rows, 'companies' => $this->companies->count(),
            'from' => $this->from, 'to' => $this->to, 'pFrom' => $this->pFrom, 'pTo' => $this->pTo,
        ];
    }

    /* ───────────── Saisonnalité ───────────── */

    public function seasonality(string $metric): array
    {
        $years = range(max((int) $this->minYear(), $this->today->year - 4), $this->today->year);
        $grid = [];

        foreach ($years as $y) {
            $grid[$y] = array_fill(1, 12, 0);
        }

        $add = function (?CarbonInterface $d, int $v) use (&$grid) {
            if ($d && isset($grid[$d->year])) { $grid[$d->year][$d->month] += $v; }
        };

        match ($metric) {
            'revenue' => $this->payments->each(fn ($p) => $add($p->paid_at, (int) $p->amount)),
            'companies' => $this->companies->each(fn ($c) => $add($c->created_at, 1)),
            default => $this->subs->each(fn ($s) => $add($s->created_at, 1)),
        };

        $totals = array_fill(1, 12, 0);

        foreach ($grid as $row) {
            foreach ($row as $m => $v) { $totals[$m] += $v; }
        }

        $sum = max(1, array_sum($totals));
        $months = [];

        foreach ($totals as $m => $v) {
            $months[] = ['n' => $m, 'label' => CarbonImmutable::create(2000, $m, 1)->translatedFormat('F'), 'short' => CarbonImmutable::create(2000, $m, 1)->translatedFormat('M'), 'value' => $v, 'share' => (int) round($v / $sum * 100)];
        }

        $quarters = [];

        foreach ([1 => [1, 2, 3], 2 => [4, 5, 6], 3 => [7, 8, 9], 4 => [10, 11, 12]] as $q => $ms) {
            $v = array_sum(array_map(fn ($m) => $totals[$m], $ms));
            $quarters[] = ['label' => 'T'.$q, 'value' => $v, 'share' => (int) round($v / $sum * 100)];
        }

        return ['years' => array_map(fn ($y) => (string) $y, $years), 'grid' => $grid, 'months' => $months, 'quarters' => $quarters, 'total' => array_sum($totals), 'metric' => $metric];
    }

    private function minYear(): int
    {
        $d = collect([$this->companies->min('created_at'), $this->subs->min('created_at')])->filter()->min();

        return $d ? CarbonImmutable::parse($d)->year : $this->today->year;
    }

    /* ───────────── Segments (types d'entreprises) ───────────── */

    public const DIMENSIONS = ['sector' => 'Secteur d’activité', 'country' => 'Pays', 'plan' => 'Formule', 'period' => 'Période de facturation', 'size' => 'Taille (effectif)', 'status' => 'Statut'];

    private function keyOf(Company $c, string $dim): array
    {
        $sub = $this->latest[$c->id] ?? null;

        return match ($dim) {
            'country' => [$c->country ?: 'none', config('platform.countries.'.$c->country, 'Non renseigné')],
            'plan' => $sub ? [$this->plans->get($sub->plan_id)?->code ?? 'none', $this->plans->get($sub->plan_id)?->name ?? 'Sans formule'] : ['none', 'Sans formule'],
            'period' => $sub ? [(string) $sub->period_months, config('platform.periods.'.$sub->period_months, '—')] : ['none', 'Sans abonnement'],
            'size' => [ReportPeriod::sizeBracket($this->emp[$c->id] ?? 0), ReportPeriod::sizeBracket($this->emp[$c->id] ?? 0).' employés'],
            'status' => [$c->status, ['trial' => 'Essai', 'active' => 'Actif', 'suspended' => 'Suspendu'][$c->status] ?? $c->status],
            default => [$c->sector ?: 'none', config('platform.sectors.'.$c->sector, 'Non renseigné')],
        };
    }

    /** Une ligne par valeur de la dimension choisie, avec les mêmes mesures partout. */
    public function segments(string $dim): array
    {
        $dim = array_key_exists($dim, self::DIMENSIONS) ? $dim : 'sector';
        $groups = [];

        foreach ($this->companies as $c) {
            [$key, $label] = $this->keyOf($c, $dim);
            $groups[$key]['label'] = $label;
            $groups[$key]['ids'][] = $c->id;
        }

        $total = max(1, $this->companies->count());
        $rows = [];

        foreach ($groups as $key => $g) {
            $m = $this->metrics($this->from, $this->to, $g['ids']);
            $rows[] = [
                'key' => (string) $key, 'label' => $g['label'], 'companies' => count($g['ids']), 'share' => (int) round(count($g['ids']) / $total * 100),
                'employees' => $m['employees'], 'avg_employees' => count($g['ids']) ? (int) round($m['employees'] / count($g['ids'])) : 0,
                'mrr' => $this->mrrAt($this->today, $this->idSet($g['ids'])), 'revenue' => $m['revenue'], 'arpa' => $m['arpa'],
                'new' => $m['new_companies'], 'churned' => $m['churned'], 'churn_rate' => $m['churn_rate'],
            ];
        }

        usort($rows, fn ($a, $b) => $b['companies'] <=> $a['companies']);

        return ['dim' => $dim, 'label' => self::DIMENSIONS[$dim], 'rows' => $rows];
    }

    /* ───────────── Entonnoir et fidélisation ───────────── */

    public function retention(): array
    {
        $a = $this->from->toDateString();
        $b = $this->to->toDateString();
        $set = $this->companies->filter(fn ($c) => $this->between($c->created_at, $a, $b));

        $hasTrial = fn ($id) => $this->subs->contains(fn ($s) => (int) $s->company_id === (int) $id && $s->trial_ends_at);
        $paid = fn ($id) => $this->payments->where('company_id', $id)->filter(fn ($p) => $p->period_start)->count();

        $stages = [
            ['label' => 'Entreprises créées', 'value' => $set->count()],
            ['label' => 'Ont démarré un essai', 'value' => $set->filter(fn ($c) => $hasTrial($c->id))->count()],
            ['label' => 'Ont payé un premier abonnement', 'value' => $set->filter(fn ($c) => $paid($c->id) >= 1)->count()],
            ['label' => 'Ont renouvelé au moins une fois', 'value' => $set->filter(fn ($c) => $paid($c->id) >= 2)->count()],
        ];

        foreach ($stages as $i => &$s) {
            $s['of_prev'] = $i === 0 ? null : ($stages[$i - 1]['value'] > 0 ? (int) round($s['value'] / $stages[$i - 1]['value'] * 100) : null);
            $s['of_first'] = $stages[0]['value'] > 0 ? (int) round($s['value'] / $stages[0]['value'] * 100) : null;
        }
        unset($s);

        // Cohortes : mois du premier abonnement ; part encore couverte (payée ou en essai) k mois plus tard.
        $cohorts = [];

        for ($i = 11; $i >= 0; $i--) {
            $start = $this->today->startOfMonth()->subMonthsNoOverflow($i);
            $ids = $this->companies->filter(function ($c) use ($start) {
                $first = $this->subs->where('company_id', $c->id)->map(fn ($s) => $s->starts_at ?? $s->created_at)->min();

                return $first && CarbonImmutable::parse($first)->startOfMonth()->equalTo($start);
            })->keys()->all();

            $cells = [];

            for ($k = 0; $k <= 11; $k++) {
                $d = $start->addMonthsNoOverflow($k)->addDays(14);
                $cells[] = ($d->gt($this->today) || ! $ids) ? null : (int) round(count(array_filter($ids, fn ($id) => $this->covered($id, $d->toDateString()))) / count($ids) * 100);
            }

            $cohorts[$start->translatedFormat('M Y').' ('.count($ids).')'] = $cells;
        }

        $m = $this->metrics($this->from, $this->to);
        $life = $this->companies->filter(fn ($c) => isset($this->latest[$c->id]))->map(function ($c) {
            $first = $this->subs->where('company_id', $c->id)->min('starts_at');
            $last = $this->latest[$c->id];
            $end = in_array($last->status, ['expired', 'cancelled'], true) ? ($last->ends_at ?? $this->today) : $this->today;

            return $first ? max(0, (int) round(CarbonImmutable::parse($first)->diffInMonths(CarbonImmutable::parse($end)))) : null;
        })->filter(fn ($v) => $v !== null);

        return ['stages' => $stages, 'cohorts' => $cohorts, 'metrics' => $m, 'avg_life' => $life->count() ? round($life->avg(), 1) : null];
    }

    private function covered(int|string $cid, string $date): bool
    {
        foreach ($this->cover[$cid] ?? [] as [$a, $b]) {
            if ($a <= $date && $date <= $b) { return true; }
        }

        return false;
    }

    /* ───────────── Prévisions à 90 jours ───────────── */

    public function forecast(): array
    {
        $limit = $this->today->addDays(90);
        $rows = [];

        foreach ($this->latest as $cid => $s) {
            if (! in_array($s->status, ['active', 'past_due', 'trial', 'pending'], true)) { continue; }

            $end = $s->status === 'trial' ? $s->trial_ends_at : $s->ends_at;

            if (! $end || CarbonImmutable::parse($end)->gt($limit)) { continue; }

            $end = CarbonImmutable::parse($end);
            $days = (int) $this->today->diffInDays($end, false);
            $rows[] = [
                'company' => $this->companies->get($cid)?->name, 'company_id' => $cid, 'plan' => $this->plans->get($s->plan_id)?->name, 'status' => $s->status,
                'status_label' => $s->statusLabel(), 'end' => $end, 'days' => $days, 'amount' => (int) $s->price,
                'risk' => $s->status === 'past_due' ? 'En retard : à relancer' : ($s->status === 'trial' ? 'Essai : conversion attendue' : ($days <= 7 ? 'Échéance imminente' : null)),
            ];
        }

        usort($rows, fn ($a, $b) => $a['end'] <=> $b['end']);

        $within = fn (int $n) => (int) collect($rows)->filter(fn ($r) => $r['days'] <= $n)->sum('amount');
        $buckets = [];

        for ($i = 0; $i < 4; $i++) {
            $m = $this->today->startOfMonth()->addMonthsNoOverflow($i);
            $buckets[$m->format('Y-m')] = ['label' => $m->translatedFormat('M Y'), 'value' => 0];
        }

        foreach ($rows as $r) {
            $key = $r['days'] < 0 ? $this->today->format('Y-m') : $r['end']->format('Y-m');
            if (isset($buckets[$key])) { $buckets[$key]['value'] += $r['amount']; }
        }

        return [
            'rows' => $rows, 'd30' => $within(30), 'd60' => $within(60), 'd90' => $within(90),
            'at_risk' => (int) collect($rows)->where('status', 'past_due')->sum('amount'), 'at_risk_count' => collect($rows)->where('status', 'past_due')->count(),
            'buckets' => array_values($buckets), 'count' => count($rows),
        ];
    }

    /* ───────────── Comparaisons ───────────── */

    public const COMPARE_DIMS = ['company' => 'Entreprise', 'sector' => 'Secteur d’activité', 'country' => 'Pays', 'plan' => 'Formule', 'period' => 'Période de facturation', 'size' => 'Taille'];
    public const COMPARE_METRICS = ['revenue' => 'Encaissements', 'mrr' => 'Revenu mensuel récurrent', 'companies' => 'Entreprises', 'employees' => 'Effectif', 'new_subs' => 'Souscriptions', 'arpa' => 'Revenu moyen par entreprise'];

    /** @return array<string,string> clé => libellé des éléments comparables pour la dimension. */
    public function candidates(string $dim): array
    {
        if ($dim === 'company') {
            return $this->companies->sortBy('name')->mapWithKeys(fn ($c) => [(string) $c->id => $c->name])->all();
        }

        return collect($this->segments($dim)['rows'])->mapWithKeys(fn ($r) => [$r['key'] => $r['label']])->all();
    }

    private function idsFor(string $dim, string $key): array
    {
        return $dim === 'company'
            ? ($this->companies->has((int) $key) ? [(int) $key] : [])
            : $this->companies->filter(fn ($c) => (string) $this->keyOf($c, $dim)[0] === (string) $key)->keys()->all();
    }

    public function comparison(string $dim, array $keys): array
    {
        $dim = array_key_exists($dim, self::COMPARE_DIMS) ? $dim : 'sector';
        $labels = $this->candidates($dim);
        $keys = array_values(array_filter(array_slice($keys, 0, 5), fn ($k) => isset($labels[(string) $k])));
        $months = ReportPeriod::months($this->from, $this->to);
        $items = [];

        foreach ($keys as $key) {
            $ids = $this->idsFor($dim, (string) $key);
            $set = $this->idSet($ids);
            $m = $this->metrics($this->from, $this->to, $ids);
            $series = array_map(function (CarbonImmutable $mo) use ($set) {
                $a = $mo->lt($this->from) ? $this->from : $mo;
                $b = $mo->endOfMonth()->startOfDay()->gt($this->to) ? $this->to : $mo->endOfMonth()->startOfDay();

                return $this->core($a, $b, $set)['revenue'];
            }, $months);

            $items[] = [
                'key' => (string) $key, 'label' => $labels[(string) $key], 'companies' => count($ids), 'employees' => $m['employees'], 'revenue' => $m['revenue'],
                'mrr' => $this->mrrAt($this->today, $set), 'new_subs' => $m['new_subs'], 'arpa' => $m['arpa'], 'churn_rate' => $m['churn_rate'], 'series' => $series,
            ];
        }

        return ['dim' => $dim, 'items' => $items, 'month_labels' => array_map(fn ($m) => $m->translatedFormat('M'), $months), 'candidates' => $labels];
    }

    /* ───────────── Constats automatiques ───────────── */

    public function insights(array $overview, array $segments, array $season, array $forecast, array $retention): array
    {
        $out = [];
        $cur = $overview['cur'];
        $f = fn (int $v) => PlanPricing::fcfa($v);

        if ($overview['prev'] && ($d = $overview['delta']['revenue']) !== null) {
            $out[] = 'Les encaissements sont '.($d >= 0 ? 'en hausse' : 'en baisse').' de '.abs($d).' % par rapport à la période de comparaison ('.$f($cur['revenue']).' contre '.$f($overview['prev']['revenue']).').';
        } elseif ($cur['revenue'] > 0) {
            $out[] = 'Sur la période, '.$f($cur['revenue']).' ont été encaissés auprès de '.$cur['payers'].' entreprise(s), soit '.$f($cur['arpa']).' en moyenne.';
        }

        $topNew = collect($segments['rows'])->sortByDesc('new')->first();
        if ($topNew && $topNew['new'] > 0 && $cur['new_companies'] > 0) {
            $out[] = 'Le segment « '.$topNew['label'].' » ('.strtolower($segments['label']).') apporte '.$topNew['new'].' des '.$cur['new_companies'].' nouvelles entreprises ('.(int) round($topNew['new'] / $cur['new_companies'] * 100).' %).';
        }

        $topRev = collect($segments['rows'])->sortByDesc('revenue')->first();
        if ($topRev && $topRev['revenue'] > 0 && $cur['revenue'] > 0) {
            $out[] = '« '.$topRev['label'].' » génère '.(int) round($topRev['revenue'] / $cur['revenue'] * 100).' % des encaissements de la période.';
        }

        $best = collect($season['months'])->sortByDesc('value')->first();
        if ($best && $best['value'] > 0 && $season['total'] > 0) {
            $out[] = 'Saisonnalité : '.$best['label'].' est le mois le plus fort ('.$best['share'].' % du total historique).';
        }

        if (($rate = $cur['churn_rate'] ?? null) !== null) {
            $out[] = $rate > 10 ? 'Attention : '.$rate.' % des entreprises payantes ont été perdues sur la période.' : 'Le taux de perte est de '.$rate.' % sur la période.';
        }

        if (($t = $retention['metrics']['trial_rate'] ?? null) !== null) {
            $out[] = $t.' % des essais terminés se sont transformés en abonnement payant.';
        }

        if ($forecast['d30'] > 0) {
            $out[] = $f($forecast['d30']).' sont attendus dans les 30 prochains jours'.($forecast['at_risk'] > 0 ? ', dont '.$f($forecast['at_risk']).' déjà en retard.' : '.');
        }

        return $out;
    }

    /* ───────────── Export ───────────── */

    /** @return array{0: string, 1: array, 2: array} nom de fichier, en-têtes, lignes */
    public function exportRows(string $tab, array $opts = []): array
    {
        switch ($tab) {
            case 'seasonality':
                $s = $this->seasonality($opts['season'] ?? 'subs');

                return ['saisonnalite', array_merge(['Année'], array_column($s['months'], 'label')), collect($s['grid'])->map(fn ($row, $y) => array_merge([$y], array_values($row)))->values()->all()];
            case 'segments':
                $s = $this->segments($opts['dim'] ?? 'sector');

                return ['segments', [$s['label'], 'Entreprises', 'Part (%)', 'Effectif', 'Effectif moyen', 'MRR', 'Encaissements', 'Revenu moyen', 'Nouvelles', 'Perdues', 'Taux de perte (%)'],
                    array_map(fn ($r) => [$r['label'], $r['companies'], $r['share'], $r['employees'], $r['avg_employees'], $r['mrr'], $r['revenue'], $r['arpa'], $r['new'], $r['churned'], $r['churn_rate']], $s['rows'])];
            case 'retention':
                $r = $this->retention();

                return ['cohortes', array_merge(['Cohorte'], array_map(fn ($k) => 'M'.$k, range(0, 11))), collect($r['cohorts'])->map(fn ($cells, $label) => array_merge([$label], $cells))->values()->all()];
            case 'forecast':
                $f = $this->forecast();

                return ['previsions', ['Entreprise', 'Formule', 'Statut', 'Échéance', 'Jours restants', 'Montant attendu', 'Signal'],
                    array_map(fn ($r) => [$r['company'], $r['plan'], $r['status_label'], $r['end']->format('d/m/Y'), $r['days'], $r['amount'], $r['risk']], $f['rows'])];
            case 'comparison':
                $c = $this->comparison($opts['dim'] ?? 'sector', $opts['values'] ?? []);

                return ['comparaison', ['Élément', 'Entreprises', 'Effectif', 'Encaissements', 'MRR', 'Souscriptions', 'Revenu moyen', 'Taux de perte (%)'],
                    array_map(fn ($i) => [$i['label'], $i['companies'], $i['employees'], $i['revenue'], $i['mrr'], $i['new_subs'], $i['arpa'], $i['churn_rate']], $c['items'])];
            default:
                $o = $this->overview();

                return ['synthese', ['Mois', 'Nouvelles entreprises', 'Souscriptions', 'Encaissements', 'MRR fin de mois', 'Entreprises perdues'],
                    array_map(fn ($r) => [$r['label'], $r['new_companies'], $r['new_subs'], $r['revenue'], $r['mrr_end'], $r['churned']], $o['months'])];
        }
    }
}
