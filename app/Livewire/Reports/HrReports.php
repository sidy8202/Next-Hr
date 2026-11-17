<?php

namespace App\Livewire\Reports;

use App\Models\Employee;
use App\Models\SavedReport;
use App\Services\HrReportEngine;
use App\Services\Payroll\PayrollBreakdown;
use App\Services\Payroll\PayrollTrend;
use App\Services\ReportPeriod;
use App\Services\Reports\GridGapReport;
use App\Services\Reports\HistoryGuard;
use App\Services\Reports\PayrollCostReport;
use App\Services\Reports\PayrollReportData;
use App\Services\Reports\ReportCatalog;
use App\Services\Reports\ReportSettings;
use App\Services\Reports\ReportThresholds;
use App\Services\Reports\SavedReportFilters;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;

/**
 * Rapports de l'entreprise. Un rapport est une fiche de la bibliothèque (ReportCatalog) ; l'utilisateur l'ouvre, modifie les critères,
 * puis l'enregistre comme « son rapport » (privé ou partagé). Filtres communs partagés dans l'adresse.
 *
 * Droits : « hr_reports » pour tout l'écran ; les rapports de paie demandent en plus « dashboard_payroll » (données de salaire).
 * RH et Direction voient toute l'entreprise ; un chef de département voit son département dans Synthèse et Effectif.
 * Les rapports de paie ne portent que des totaux : aucun nom, aucun salaire individuel, groupes de moins de 3 personnes masqués.
 */
#[Layout('components.layouts.app')]
#[Title('Rapports')]
class HrReports extends Component
{
    private const PAYROLL_TABS = ['payroll_cost', 'grid_gaps'];

    private const TAB_LABELS = ['library' => 'Bibliothèque', 'mine' => 'Mes rapports', 'synthesis' => 'Synthèse', 'workforce' => 'Effectif', 'payroll_cost' => 'Coût de la paie', 'grid_gaps' => 'Écarts à la grille'];

    #[Url(as: 'onglet')] public string $tab = 'synthesis';
    #[Url(as: 'periode')] public string $preset = '12m';
    #[Url(as: 'du')] public string $from = '';
    #[Url(as: 'au')] public string $to = '';
    #[Url(as: 'comparer')] public string $compare = 'previous';

    #[Url(as: 'dep')] public array $depts = [];
    #[Url(as: 'poste')] public array $positions = [];
    #[Url(as: 'contrat')] public array $contracts = [];
    #[Url(as: 'sexe')] public array $genders = [];
    #[Url(as: 'categorie')] public array $categories = [];

    /** Indicateur du rapport « coût de la paie » : employer_cost | net_to_pay | gross. */
    #[Url(as: 'indicateur')] public string $metric = 'employer_cost';

    public string $saveName = '';
    public string $saveVisibility = 'private';

    /** Seuils en cours de saisie (clé => texte). */
    public array $thresholdInputs = [];

    public function mount(): void
    {
        abort_unless(auth()->user()->canDo('read', 'hr_reports'), 403);

        $this->tab = $this->normalizeTab();

        if (! array_key_exists($this->metric, PayrollTrend::METRICS)) {
            $this->metric = 'employer_cost';
        }
    }

    /** @return \Closure(string, string): bool */
    private function can(): \Closure
    {
        $u = auth()->user();

        return fn (string $action, string $resource): bool => $u->canDo($action, $resource);
    }

    private function cid(): int
    {
        return (int) auth()->user()->company_id;
    }

    /** Onglet demandé s'il est permis, sinon la synthèse. */
    private function normalizeTab(): string
    {
        $allowed = array_merge(['library', 'mine'], array_keys(ReportCatalog::visible($this->can())));

        return in_array($this->tab, $allowed, true) ? $this->tab : 'synthesis';
    }

    /** Département imposé à qui n'a pas la vue Direction et RH (Synthèse et Effectif seulement : la paie a sa propre portée). */
    private function forcedDepartment(): ?int
    {
        $u = auth()->user();

        if (in_array($this->tab, self::PAYROLL_TABS, true) || $u->canDo('read', 'dashboard_hr') || ! $u->employee_id) {
            return null;
        }

        return (int) (Employee::whereKey($u->employee_id)->value('department_id') ?: 0);
    }

    private function filters(): array
    {
        $forced = $this->forcedDepartment();

        return [
            'preset' => $this->preset, 'from' => $this->from, 'to' => $this->to, 'compare' => $this->compare,
            'depts' => $forced !== null ? [$forced] : $this->depts, 'positions' => $this->positions, 'contracts' => $this->contracts, 'genders' => $this->genders,
        ];
    }

    #[Computed]
    public function engine(): HrReportEngine
    {
        return new HrReportEngine($this->cid(), $this->filters(), auth()->user()->canDo('read', 'employees'));
    }

    public function removeFilter(string $group, string $value): void
    {
        if (in_array($group, ['depts', 'positions', 'contracts', 'genders', 'categories'], true)) {
            $this->{$group} = array_values(array_filter($this->{$group}, fn ($v) => (string) $v !== $value));
        }
    }

    public function clearGroup(string $group): void
    {
        if (in_array($group, ['depts', 'positions', 'contracts', 'genders', 'categories'], true)) {
            $this->{$group} = [];
        }
    }

    public function resetFilters(): void
    {
        $this->reset(['preset', 'from', 'to', 'compare', 'depts', 'positions', 'contracts', 'genders', 'categories']);
    }

    public function refreshData(): void
    {
        HrReportEngine::forget($this->cid());
        $this->dispatch('notify', type: 'success', message: 'Données actualisées.');
    }

    public function setMetric(string $metric): void
    {
        if (array_key_exists($metric, PayrollTrend::METRICS)) {
            $this->metric = $metric;
        }
    }

    /** Ouvre un rapport de la bibliothèque (seulement si l'utilisateur en a le droit). */
    public function openFiche(string $key): void
    {
        abort_unless(ReportCatalog::canOpen($key, $this->can()), 403);

        $this->tab = $key;
    }

    // ---------- Mes rapports ----------

    /** Enregistre le rapport affiché, avec ses critères, comme « mon rapport ». */
    public function saveReport(): void
    {
        $key = $this->normalizeTab();

        abort_unless(in_array($key, ReportCatalog::savableKeys(), true) && ReportCatalog::canOpen($key, $this->can()), 403);

        $this->validate(
            ['saveName' => ['required', 'string', 'max:60'], 'saveVisibility' => ['required', 'in:private,shared']],
            [],
            ['saveName' => 'nom du rapport', 'saveVisibility' => 'visibilité']
        );

        if (SavedReport::where('company_id', $this->cid())->where('user_id', auth()->id())->count() >= 30) {
            $this->addError('saveName', 'Vous avez atteint la limite de 30 rapports enregistrés : supprimez-en un avant d\'en ajouter.');

            return;
        }

        SavedReport::create([
            'user_id' => auth()->id(),
            'report_key' => $key,
            'name' => trim($this->saveName),
            'filters' => SavedReportFilters::sanitize($this->currentFilters()),
            'visibility' => $this->saveVisibility,
        ]);

        $this->reset(['saveName']);
        $this->saveVisibility = 'private';
        $this->dispatch('report-saved');
        $this->dispatch('notify', type: 'success', message: 'Rapport enregistré dans « Mes rapports ».');
    }

    /** Ouvre un rapport enregistré : le sien, ou un rapport partagé dont on a le droit d'ouvrir le rapport de base. */
    public function openSaved(int $id): void
    {
        $r = SavedReport::where('company_id', $this->cid())->find($id);

        abort_unless($r !== null && ((int) $r->user_id === (int) auth()->id() || $r->visibility === 'shared'), 404);
        abort_unless(ReportCatalog::canOpen((string) $r->report_key, $this->can()), 403);

        $this->applyFilters(SavedReportFilters::sanitize((array) $r->filters));
        $this->tab = (string) $r->report_key;
    }

    public function deleteSaved(int $id): void
    {
        $r = SavedReport::where('company_id', $this->cid())->where('user_id', auth()->id())->find($id);

        abort_unless($r !== null, 404);

        $r->delete();
        $this->dispatch('notify', type: 'success', message: 'Rapport supprimé.');
    }

    public function toggleShare(int $id): void
    {
        $r = SavedReport::where('company_id', $this->cid())->where('user_id', auth()->id())->find($id);

        abort_unless($r !== null, 404);

        $r->visibility = $r->visibility === 'shared' ? 'private' : 'shared';
        $r->save();
    }

    /** Critères actuels de l'écran, avant nettoyage par SavedReportFilters. */
    private function currentFilters(): array
    {
        return [
            'preset' => $this->preset, 'from' => $this->from, 'to' => $this->to, 'compare' => $this->compare,
            'depts' => $this->depts, 'positions' => $this->positions, 'contracts' => $this->contracts, 'genders' => $this->genders,
            'categories' => $this->categories, 'metric' => $this->metric,
        ];
    }

    /** @param array<string, mixed> $f résultat de SavedReportFilters::sanitize() */
    private function applyFilters(array $f): void
    {
        $this->preset = $f['preset'];
        $this->from = $f['from'];
        $this->to = $f['to'];
        $this->compare = $f['compare'];
        // Les cases à cocher comparent du texte : on remet les identifiants en texte.
        $this->depts = array_map('strval', $f['depts']);
        $this->positions = array_map('strval', $f['positions']);
        $this->contracts = $f['contracts'];
        $this->genders = $f['genders'];
        $this->categories = array_map('strval', $f['categories']);
        $this->metric = $f['metric'];
    }

    // ---------- Seuils ----------

    public function saveThresholds(): void
    {
        $tab = $this->normalizeTab();

        abort_unless(in_array($tab, self::PAYROLL_TABS, true) && ReportCatalog::canOpen($tab, $this->can()) && auth()->user()->canDo('update', 'payroll_settings'), 403);

        $clean = [];
        $failed = false;

        foreach (ReportThresholds::forReport($tab) as $key => $def) {
            $v = ReportThresholds::sanitizeValue($key, $this->thresholdInputs[$key] ?? null);

            if ($v === null) {
                $this->addError('thresholdInputs.' . $key, 'Entrez un nombre entier entre ' . $def['min'] . ' et ' . $def['max'] . '.');
                $failed = true;
            } else {
                $clean[$key] = $v;
            }
        }

        if ($failed) {
            return;
        }

        foreach ($clean as $key => $v) {
            ReportSettings::save($this->cid(), $key, $v, auth()->id());
        }

        $this->dispatch('notify', type: 'success', message: 'Seuils enregistrés.');
    }

    // ---------- Export ----------

    /** Export CSV (séparateur « ; », UTF-8 avec BOM : s'ouvre directement dans Excel) du tableau de l'onglet affiché. */
    public function export()
    {
        $tab = $this->normalizeTab();

        abort_unless(ReportCatalog::canOpen($tab, $this->can()), 403);

        if ($tab === 'payroll_cost') {
            $c = $this->costData();
            $name = 'cout-paie';
            $headers = ['Mois', 'Statut', 'Bulletins', 'Masse brute', 'Net à payer', 'Coût employeur'];
            $rows = array_map(fn ($r) => [$r['label'], $r['status'], $r['n'], $r['gross'], $r['net_to_pay'], $r['employer_cost']], $c['table']);
        } elseif ($tab === 'grid_gaps') {
            $g = $this->gridData();
            $name = 'ecarts-grille';
            $headers = ['Catégorie', 'Comparés', 'Sous le minimum', 'Au-dessus du maximum', 'Écart à l\'échelon'];
            $rows = array_map(fn ($r) => [$r['name'], $r['count'], $r['below'], $r['above'], $r['step_mismatch']], $g['summary']['categories']['items']);
        } elseif (in_array($tab, ['synthesis', 'workforce'], true)) {
            [$name, $headers, $rows] = $this->engine->exportRows($tab);
        } else {
            abort(404);
        }

        return response()->streamDownload(function () use ($headers, $rows) {
            echo "\xEF\xBB\xBF";
            $out = fopen('php://output', 'w');
            fputcsv($out, $headers, ';');

            foreach ($rows as $row) {
                fputcsv($out, array_map(fn ($v) => $v ?? '', $row), ';');
            }

            fclose($out);
        }, 'rapport-rh-' . $name . '-' . now()->format('Ymd') . '.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    // ---------- Données des rapports de paie ----------

    /** @return array<int, string> mois AAAA-MM couverts par la période (36 derniers au plus) */
    private function periodsOf(CarbonImmutable $from, CarbonImmutable $to): array
    {
        return array_slice(array_map(fn ($d) => $d->format('Y-m'), ReportPeriod::months($from, $to)), -36);
    }

    private function monthLabel(string $period, bool $withYear = true): string
    {
        return CarbonImmutable::createFromFormat('Y-m-d', $period . '-01')->translatedFormat($withYear ? 'M y' : 'M');
    }

    /** @return array<int, int> */
    private function deptIds(): array
    {
        return array_values(array_filter(array_map('intval', $this->depts)));
    }

    /** Tout le rapport « masse salariale et coût employeur », prêt à afficher. */
    private function costData(): array
    {
        $cid = $this->cid();
        $e = $this->engine;
        $depts = $this->deptIds();
        $thr = ReportSettings::thresholds($cid);
        $metric = array_key_exists($this->metric, PayrollTrend::METRICS) ? $this->metric : 'employer_cost';

        $monthly = PayrollReportData::months($cid, $this->periodsOf($e->from, $e->to), $depts);
        $prevMonthly = $e->pFrom ? PayrollReportData::months($cid, $this->periodsOf($e->pFrom, $e->pTo), $depts) : [];

        $cur = PayrollCostReport::aggregate($monthly);
        $prev = $e->pFrom ? PayrollCostReport::aggregate($prevMonthly) : null;
        $kpis = PayrollCostReport::kpis($cur, $prev);

        $periods = array_keys($monthly);
        $curMonth = $periods ? end($periods) : null;
        $refMonth = $curMonth ? PayrollTrend::shift($curMonth, -$thr['cost_trend_months']) : null;
        $refCosts = $refMonth ? PayrollReportData::employeeCosts($cid, $refMonth, $depts) : [];
        $curCosts = $curMonth ? PayrollReportData::employeeCosts($cid, $curMonth, $depts) : [];

        $departments = PayrollBreakdown::byDepartment(PayrollReportData::departments($cid, $periods, $depts), $metric);

        $perHead = fn (?array $a): ?int => $a !== null && $a['n_sum'] > 0 ? intdiv($a['employer_cost'] * 2 + $a['n_sum'], 2 * $a['n_sum']) : null;

        $sentences = PayrollCostReport::sentences([
            'ref_month' => $refMonth, 'cur_month' => $curMonth, 'months_back' => $thr['cost_trend_months'],
            'decomposition' => PayrollCostReport::decompose($refCosts, $curCosts), 'departments' => $departments,
            'cost_per_head' => $perHead($cur), 'cost_per_head_prev' => $perHead($prev),
            'headcount_ref' => $refCosts === [] ? null : count($refCosts), 'headcount_cur' => $curCosts === [] ? null : count($curCosts), 'metric' => $metric,
        ], $thr);

        $history = HistoryGuard::monthsOfHistory(PayrollReportData::firstPeriod($cid), $curMonth);
        $long = count($periods) > 12;

        $deptBars = array_map(fn ($i) => ['label' => $i['name'], 'value' => $i['value'], 'sub' => $i['share'] . ' % · ' . $i['count'] . ' pers.'], $departments['items']);

        $statusLabels = ['calculated' => 'Provisoire', 'closed' => 'Clôturée'];
        $table = [];

        foreach ($monthly as $period => $m) {
            $table[] = ['label' => substr($period, 5, 2) . '/' . substr($period, 0, 4), 'status' => $statusLabels[$m['status']] ?? $m['status']] + $m;
        }

        return [
            'hasData' => $cur['months'] > 0,
            'kpis' => $kpis,
            'chart' => ['labels' => array_map(fn ($p) => $this->monthLabel($p, $long), $periods), 'values' => array_map(fn ($m) => $m[$metric], array_values($monthly))],
            'table' => $table,
            'departments' => $departments, 'deptBars' => $deptBars,
            'sentences' => $sentences,
            'metric' => $metric, 'metricLabel' => PayrollTrend::METRICS[$metric], 'metrics' => PayrollTrend::METRICS,
            'seasonNote' => HistoryGuard::seasonalityNote($history), 'history' => $history,
            'minGroup' => PayrollBreakdown::MIN_GROUP,
        ];
    }

    /** Tout le rapport « écarts à la grille », prêt à afficher. */
    private function gridData(): array
    {
        $cid = $this->cid();
        $e = $this->engine;
        $today = CarbonImmutable::today();
        $asOf = ($e->to->gt($today) ? $today : $e->to)->toDateString();
        $categories = array_values(array_filter(array_map('intval', $this->categories)));

        $data = PayrollReportData::grid($cid, $asOf, $this->deptIds(), $categories);
        $summary = GridGapReport::summarize($data['rows'], $data['no_salary']);

        return [
            'asOf' => $asOf, 'summary' => $summary, 'sentences' => GridGapReport::sentences($summary, ReportSettings::thresholds($cid)),
            'salariesUrl' => Route::has('payroll.people') && auth()->user()->canDo('read', 'employee_salaries') ? route('payroll.people') : null,
            'minGroup' => GridGapReport::MIN_GROUP,
        ];
    }

    // ---------- Bibliothèque et « Mes rapports » ----------

    /** @return array<int, array{label: string, items: array<int, array<string, mixed>>}> */
    private function libraryCards(): array
    {
        $visible = ReportCatalog::visible($this->can());
        $cards = [];

        foreach (ReportCatalog::FAMILIES as $familyKey => $familyLabel) {
            $items = [];

            foreach (ReportCatalog::all() as $fiche) {
                if ($fiche['family'] !== $familyKey || ($fiche['status'] === 'available' && ! isset($visible[$fiche['key']]))) {
                    continue;
                }

                $items[] = $fiche;
            }

            if ($items !== []) {
                $cards[] = ['label' => $familyLabel, 'items' => $items];
            }
        }

        return $cards;
    }

    /** @return array{mine: array<int, array<string, mixed>>, shared: array<int, array<string, mixed>>} */
    private function mineData(): array
    {
        $uid = (int) auth()->id();
        $keys = array_keys(ReportCatalog::visible($this->can()));

        $all = SavedReport::where('company_id', $this->cid())->whereIn('report_key', $keys)
            ->where(fn ($q) => $q->where('user_id', $uid)->orWhere('visibility', 'shared'))->orderByDesc('updated_at')->get();

        $owners = DB::table('users')->whereIn('id', $all->pluck('user_id')->unique()->all())->pluck('name', 'id');

        $map = fn ($r) => [
            'id' => $r->id, 'name' => $r->name, 'title' => ReportCatalog::find($r->report_key)['title'] ?? $r->report_key,
            'visibility' => $r->visibility, 'owner' => $owners[$r->user_id] ?? '—', 'updated' => $r->updated_at?->format('d/m/Y'),
        ];

        return [
            'mine' => $all->filter(fn ($r) => (int) $r->user_id === $uid)->map($map)->values()->all(),
            'shared' => $all->filter(fn ($r) => (int) $r->user_id !== $uid)->map($map)->values()->all(),
        ];
    }

    public function render()
    {
        $this->tab = $this->normalizeTab();

        if (! array_key_exists($this->metric, PayrollTrend::METRICS)) {
            $this->metric = 'employer_cost';
        }

        $e = $this->engine;
        $can = $this->can();
        $options = $e->options();
        $payroll = in_array($this->tab, self::PAYROLL_TABS, true);
        $browse = in_array($this->tab, ['library', 'mine'], true);

        $tabs = [];

        foreach (['library', 'mine', 'synthesis', 'workforce', 'payroll_cost', 'grid_gaps'] as $k) {
            if ($k === 'library' || $k === 'mine' || ReportCatalog::canOpen($k, $can)) {
                $tabs[$k] = self::TAB_LABELS[$k];
            }
        }

        $groups = ['depts' => ['Départements', $options['depts']]];

        if (! $payroll) {
            $groups['positions'] = ['Postes', $options['positions']];
            $groups['contracts'] = ['Types de contrat', collect($options['contracts'])->mapWithKeys(fn ($c) => [$c => $c])->all()];
            $groups['genders'] = ['Sexe', $options['genders']];
        }

        if ($this->tab === 'grid_gaps') {
            $groups['categories'] = ['Catégories salariales', PayrollReportData::gradeOptions($this->cid())];
        }

        $selected = [];

        foreach (array_keys($groups) as $key) {
            $selected[$key] = $this->{$key};
        }

        $data = [
            'options' => $options, 'from_d' => $e->from, 'to_d' => $e->to, 'forced' => $this->forcedDepartment(),
            'tabs' => $tabs, 'payroll' => $payroll, 'browse' => $browse,
            'groups' => $groups, 'selected' => $selected, 'activeCount' => collect($selected)->flatten()->count(),
            'showCompare' => in_array($this->tab, ['synthesis', 'workforce', 'payroll_cost'], true),
            'canSave' => in_array($this->tab, ReportCatalog::savableKeys(), true),
            'canExport' => ! $browse,
        ];

        if ($payroll) {
            $thr = ReportSettings::thresholds($this->cid());
            $defs = ReportThresholds::forReport($this->tab);

            foreach ($defs as $key => $def) {
                $this->thresholdInputs[$key] ??= (string) $thr[$key];
            }

            $data += ['thresholds' => $thr, 'thresholdDefs' => $defs, 'canEditThresholds' => auth()->user()->canDo('update', 'payroll_settings')];
        }

        if ($this->tab === 'library') {
            $data['cards'] = $this->libraryCards();
        } elseif ($this->tab === 'mine') {
            $data += $this->mineData();
        } elseif ($this->tab === 'workforce') {
            $data['w'] = $e->workforce();
        } elseif ($this->tab === 'payroll_cost') {
            $data['cost'] = $this->costData();
        } elseif ($this->tab === 'grid_gaps') {
            $data['grid'] = $this->gridData();
        } else {
            $this->tab = 'synthesis';
            $data['s'] = $e->synthesis();
            $data['canSalary'] = auth()->user()->canDo('read', 'dashboard_payroll');
        }

        return view('livewire.reports.hr-reports', $data);
    }
}
