<?php

namespace App\Services;

use App\Models\Company;
use App\Models\Employee;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\SubscriptionPayment;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Cache;
use Throwable;

/** Toutes les données du dashboard et des rapports de la plateforme : uniquement des agrégats, jamais de données personnelles. */
class PlatformStats
{
    public static function forget(): void
    {
        Cache::forget('platform.stats');
    }

    public static function get(): array
    {
        return Cache::remember('platform.stats', 60, fn () => (new self)->build());
    }

    public function build(): array
    {
        $companies = Company::all();
        $plans = Plan::orderBy('sort')->get()->keyBy('id');
        $allSubs = Subscription::orderBy('id')->get();
        $current = $allSubs->groupBy('company_id')->map->last();
        $usable = $current->filter(fn ($s) => in_array($s->status, Subscription::USABLE, true));
        $employees = Employee::withoutGlobalScopes()->whereNull('deleted_at')->where('status', 'active')
            ->selectRaw('company_id, count(*) as c')->groupBy('company_id')->pluck('c', 'company_id');

        $rows = $companies->map(function (Company $c) use ($usable, $plans, $employees, $current) {
            $sub = $usable->get($c->id);
            $plan = $sub ? $plans->get($sub->plan_id) : null;

            return [
                'id' => $c->id, 'name' => $c->name, 'sector' => $c->sector ?: 'unknown', 'country' => $c->country ?: 'unknown',
                'status' => $c->status, 'plan' => $plan?->code ?? 'none', 'period' => $sub?->period_months, 'sub_status' => $current->get($c->id)?->status,
                'employees' => (int) ($employees[$c->id] ?? 0), 'mrr' => ($sub && in_array($sub->status, ['active', 'past_due'], true)) ? $sub->monthlyAmount() : 0,
                'ends_at' => $sub?->ends_at?->toDateString(), 'max' => $plan?->max_employees, 'created_at' => $c->created_at,
            ];
        })->values();

        $monthStart = CarbonImmutable::now()->startOfMonth();
        $payments = SubscriptionPayment::where('paid_at', '>=', $monthStart->subMonths(11))->get(['amount', 'paid_at']);
        $collected = fn (CarbonImmutable $m) => (int) $payments->filter(fn ($p) => $p->paid_at->isSameMonth($m))->sum('amount');

        $months = collect(range(11, 0))->map(fn ($i) => $monthStart->subMonths($i));

        $mrrAt = function (CarbonImmutable $m) use ($allSubs) {
            $end = $m->endOfMonth();

            return (int) $allSubs->filter(fn ($s) => ! in_array($s->status, ['trial', 'pending'], true) && $s->starts_at && $s->starts_at->lte($end) && (! $s->ends_at || $s->ends_at->gte($m)))
                ->sum(fn ($s) => $s->monthlyAmount());
        };

        $newBy = fn (CarbonImmutable $m) => $companies->filter(fn ($c) => $c->created_at->isSameMonth($m))->count();
        $lostBy = fn (CarbonImmutable $m) => $allSubs->filter(fn ($s) => in_array($s->status, ['expired', 'cancelled'], true) && $s->ends_at && $s->ends_at->isSameMonth($m))->pluck('company_id')->unique()->count();

        $planNames = $plans->mapWithKeys(fn ($p) => [$p->code => ['name' => $p->name, 'color' => $p->color]])->all() + ['none' => ['name' => 'Sans formule', 'color' => '#94a3b8']];

        return [
            'generated_at' => now()->toDateTimeString(),
            'plans' => $planNames,
            'rows' => $rows->all(),
            'kpis' => [
                'companies' => $companies->count(),
                'active' => $rows->whereIn('sub_status', ['active', 'past_due'])->count(),
                'trial' => $rows->where('sub_status', 'trial')->count(),
                'suspended' => $companies->where('status', 'suspended')->count(),
                'no_plan' => $rows->where('plan', 'none')->count(),
                'employees' => (int) $employees->sum(),
                'mrr' => (int) $rows->sum('mrr'),
                'collected' => $collected($monthStart),
                'collected_prev' => $collected($monthStart->subMonth()),
                'new_this' => $newBy($monthStart),
                'new_prev' => $newBy($monthStart->subMonth()),
                'spark_mrr' => $months->map($mrrAt)->all(),
                'spark_new' => $months->map($newBy)->all(),
            ],
            'revenue' => [
                'labels' => $months->map(fn ($m) => $m->translatedFormat('M'))->all(),
                'collected' => $months->map($collected)->all(),
                'mrr' => $months->map($mrrAt)->all(),
            ],
            'growth' => [
                'labels' => $months->map(fn ($m) => $m->translatedFormat('M'))->all(),
                'new' => $months->map($newBy)->all(),
                'lost' => $months->map($lostBy)->all(),
            ],
            'distribution' => collect($planNames)->map(fn ($p, $code) => ['label' => $p['name'], 'color' => $p['color'], 'value' => $rows->where('plan', $code)->count()])->values()->all(),
            'sectors' => $rows->groupBy('sector')->map(fn ($g, $k) => [
                'label' => config('platform.sectors.'.$k, 'Non renseigné'), 'value' => (int) $g->sum('employees'), 'sub' => $g->count().' entreprise(s)',
            ])->sortByDesc('value')->take(8)->values()->all(),
            'adoption' => $this->adoption($rows, $planNames),
            'alerts' => $this->alerts($rows),
        ];
    }

    /** Part des entreprises de chaque formule qui utilisent réellement chaque module (au moins un enregistrement). */
    private function adoption($rows, array $planNames): array
    {
        $modules = [
            'Congés' => \App\Models\LeaveRequest::class, 'Contrats' => \App\Models\Contract::class, 'Documents' => \App\Models\Document::class,
            'Autorisations d’absence' => \App\Models\AbsencePermission::class, 'Bulletins de paie' => \App\Models\Payslip::class,
            'Sorties d’effectif' => \App\Models\Termination::class, 'Recrutement' => \App\Models\JobOffer::class,
            'Demandes d’explication' => \App\Models\ExplanationCase::class, 'Missions' => \App\Models\Mission::class,
        ];

        $groups = [];

        foreach (array_keys($planNames) as $code) {
            $groups[$code] = $rows->where('plan', $code)->pluck('id')->all();
        }

        $out = ['cols' => collect($planNames)->map(fn ($p) => $p['name'])->values()->all(), 'rows' => []];

        foreach ($modules as $label => $class) {
            $used = $this->companiesUsing($class);

            if ($used === null) {
                continue;
            }

            $out['rows'][$label] = collect($groups)->map(fn ($ids) => count($ids) ? (int) round(count(array_intersect($ids, $used)) / count($ids) * 100) : null)->values()->all();
        }

        return $out;
    }

    private function companiesUsing(string $class): ?array
    {
        if (! class_exists($class)) {
            return null;
        }

        try {
            return $class::withoutGlobalScopes()->distinct()->pluck('company_id')->all();
        } catch (Throwable) {
            return null;
        }
    }

    private function alerts($rows): array
    {
        $today = today();
        $alerts = [];

        foreach ($rows as $r) {
            $days = $r['ends_at'] ? (int) $today->diffInDays(CarbonImmutable::parse($r['ends_at']), false) : null;

            if ($r['sub_status'] === 'past_due') {
                $alerts[] = ['level' => 'danger', 'company_id' => $r['id'], 'company' => $r['name'], 'text' => 'Paiement en retard depuis '.abs((int) $days).' jour(s)'];
            } elseif ($r['sub_status'] === 'active' && $days !== null && $days >= 0 && $days <= 30) {
                $alerts[] = ['level' => $days <= 7 ? 'warning' : 'info', 'company_id' => $r['id'], 'company' => $r['name'], 'text' => "Échéance dans {$days} jour(s)"];
            } elseif ($r['sub_status'] === 'trial' && $days !== null && $days >= 0 && $days <= 7) {
                $alerts[] = ['level' => 'warning', 'company_id' => $r['id'], 'company' => $r['name'], 'text' => "Fin d’essai dans {$days} jour(s)"];
            }

            if ($r['max'] && $r['employees'] / $r['max'] >= 0.9) {
                $alerts[] = ['level' => 'info', 'company_id' => $r['id'], 'company' => $r['name'], 'text' => "Effectif à ".round($r['employees'] / $r['max'] * 100).' % du plafond : montée de formule possible'];
            }
        }

        $order = ['danger' => 0, 'warning' => 1, 'info' => 2];
        usort($alerts, fn ($a, $b) => $order[$a['level']] <=> $order[$b['level']]);

        return array_slice($alerts, 0, 10);
    }

    /** Tableau croisé : lignes (secteur, pays ou période) × formules, mesure (entreprises, effectif, revenu mensuel). */
    public static function pivot(array $rows, string $by, string $metric): array
    {
        $plans = collect(self::get()['plans']);
        $key = fn ($r) => match ($by) {
            'country' => config('platform.countries.'.$r['country'], 'Non renseigné'),
            'period' => $r['period'] ? config('platform.periods.'.$r['period']) : 'Sans abonnement',
            default => config('platform.sectors.'.$r['sector'], 'Non renseigné'),
        };
        $value = fn ($group) => match ($metric) {
            'employees' => (int) $group->sum('employees'),
            'mrr' => (int) $group->sum('mrr'),
            default => $group->count(),
        };

        $table = [];

        foreach (collect($rows)->groupBy($key) as $label => $group) {
            $cells = [];

            foreach ($plans->keys() as $code) {
                $cells[$code] = $value($group->where('plan', $code));
            }

            $table[$label] = $cells + ['_total' => $value($group)];
        }

        uasort($table, fn ($a, $b) => $b['_total'] <=> $a['_total']);

        $totals = [];

        foreach ($plans->keys() as $code) {
            $totals[$code] = $value(collect($rows)->where('plan', $code));
        }

        return ['cols' => $plans->map(fn ($p) => $p['name'])->all(), 'rows' => $table, 'totals' => $totals + ['_total' => $value(collect($rows))]];
    }
}
