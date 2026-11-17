<?php

namespace App\Livewire\Payroll;

use App\Livewire\Payroll\Concerns\PayrollGuards;
use App\Models\PayrollPeriod;
use App\Services\Payroll\LoanSchedule;
use App\Services\Payroll\PayrollAlerts;
use App\Services\Payroll\PayrollBreakdown;
use App\Services\Payroll\PayrollCycle;
use App\Services\Payroll\PayrollDashboardFacts;
use App\Services\Payroll\PayrollDashboardHistory;
use App\Services\Payroll\PayrollDashboardStats;
use App\Services\Payroll\PayrollTrend;
use App\Services\Payroll\SetupProgress;
use Illuminate\Support\Facades\Route;
use Livewire\Attributes\Url;
use Livewire\Component;

/**
 * Panneau « tableau de bord paie » : cycle du mois, chiffres comparés au mois précédent et à l'an dernier, courbe sur 12 mois,
 * répartitions (service, brut vers net, rubriques), envoi des bulletins, alertes.
 * Affiché sur la page Paie > Tableau de bord paie et dans l'onglet « Paie » du tableau de bord de l'entreprise.
 * Droit : lecture de « dashboard_payroll ». Il n'affiche que des totaux : aucun nom, aucun salaire individuel.
 */
class DashboardPanel extends Component
{
    use PayrollGuards;

    #[Url(as: 'mois')]
    public string $month = '';

    /** Indicateur de la courbe et de la répartition par service : net_to_pay | gross | employer_cost. */
    #[Url(as: 'indicateur')]
    public string $metric = 'net_to_pay';

    public function mount(): void
    {
        $this->allow('read', 'dashboard_payroll');

        if (! preg_match('/^\d{4}-(0[1-9]|1[0-2])$/', $this->month)) {
            $this->month = $this->defaultMonth();
        }
    }

    public function previousMonth(): void
    {
        $this->allow('read', 'dashboard_payroll');
        $this->month = LoanSchedule::addMonths($this->month, -1);
    }

    public function nextMonth(): void
    {
        $this->allow('read', 'dashboard_payroll');

        // Pas de mois futur : il n'a aucun chiffre.
        if ($this->month < now()->format('Y-m')) {
            $this->month = LoanSchedule::addMonths($this->month, 1);
        }
    }

    public function currentMonth(): void
    {
        $this->allow('read', 'dashboard_payroll');
        $this->month = $this->defaultMonth();
    }

    public function setMetric(string $metric): void
    {
        $this->allow('read', 'dashboard_payroll');

        if (array_key_exists($metric, PayrollTrend::METRICS)) {
            $this->metric = $metric;
        }
    }

    /** Même règle que l'écran « Paie du mois » : la plus ancienne paie encore ouverte, sinon le mois courant. */
    protected function defaultMonth(): string
    {
        $open = PayrollPeriod::where('company_id', $this->companyId())->where('status', '!=', 'closed')->orderBy('period')->value('period');

        return $open ?: now()->format('Y-m');
    }

    public function render()
    {
        $this->allow('read', 'dashboard_payroll');

        if (! preg_match('/^\d{4}-(0[1-9]|1[0-2])$/', $this->month)) {
            $this->month = $this->defaultMonth();
        }

        if (! array_key_exists($this->metric, PayrollTrend::METRICS)) {
            $this->metric = 'net_to_pay';
        }

        $facts = PayrollDashboardFacts::load($this->companyId(), $this->month);
        $months = PayrollDashboardHistory::months($this->companyId(), $this->month);

        $totals = $facts['sums'] === null ? null : PayrollDashboardStats::totals($facts['sums']);
        $previousTotals = $facts['previous']['sums'] === null ? null : PayrollDashboardStats::totals($facts['previous']['sums']);
        $deltas = $totals === null ? [] : PayrollDashboardStats::compare($totals, $previousTotals);

        $kpis = [];

        if ($totals !== null) {
            $colors = ['gross' => '#6366f1', 'net_to_pay' => '#10b981', 'deductions' => '#f59e0b', 'employer_total' => '#8b5cf6', 'employer_cost' => '#0ea5e9'];

            foreach (PayrollDashboardStats::METRICS as $key => $label) {
                $kpis[] = [
                    'label' => $label,
                    'value' => $totals[$key],
                    'color' => $colors[$key],
                    'hint' => PayrollDashboardStats::deltaText($deltas[$key] ?? null),
                ];
            }
        }

        $cycle = PayrollCycle::steps([
            'period_status' => $facts['period_status'],
            'pending_variables' => $facts['pending_variables'],
            'payslips' => $totals['count'] ?? 0,
            'pending_checks' => $facts['pending_checks'],
            'delivery' => $facts['delivery'],
        ]);

        foreach ($cycle['steps'] as $i => $step) {
            $cycle['steps'][$i]['url'] = $this->urlFor($step['target']);
        }

        $alerts = PayrollAlerts::build([
            'period_status' => $facts['period_status'],
            'employees' => $facts['setup']['employees'],
            'employees_with_salary' => $facts['setup']['employees_with_salary'],
            'pending_checks' => $facts['pending_checks'],
            'skipped' => $facts['skipped'],
            'pending_variables' => $facts['pending_variables'],
            'legal_state' => $facts['legal']['state'],
            'legal_total' => $facts['legal']['total'],
            'legal_validated' => $facts['legal']['validated'],
            'off_grid' => $facts['off_grid'],
            'active_loans' => $facts['active_loans'],
            'installments_count' => $facts['installments_count'],
            'installments_amount' => $facts['installments_amount'],
        ]);

        foreach ($alerts as $i => $alert) {
            $alerts[$i]['url'] = $alert['target'] ? $this->urlFor($alert['target']) : null;
        }

        // Bandeau de l'assistant : seulement pour qui peut le lire, et tant qu'il reste des étapes obligatoires.
        $setup = SetupProgress::evaluate($facts['setup']);
        $setupBanner = ! $setup['complete'] && $this->allowed('read', 'payroll_settings') && Route::has('payroll.start')
            ? ['percent' => $setup['percent'], 'remaining' => $setup['remaining'], 'url' => route('payroll.start')]
            : null;

        // Courbe sur 12 mois : trou pour un mois non calculé, aucune valeur devinée.
        $series = PayrollTrend::series($months, $this->month, $this->metric);
        $stats = PayrollTrend::stats($series);
        $trendNotes = [];

        if ($stats['count'] >= 2) {
            $trendNotes[] = 'Plus haut : ' . $this->monthLabel($stats['max']['period']) . ' (' . PayrollDashboardStats::number($stats['max']['value']) . ' FCFA).';
            $trendNotes[] = 'Plus bas : ' . $this->monthLabel($stats['min']['period']) . ' (' . PayrollDashboardStats::number($stats['min']['value']) . ' FCFA).';
            $trendNotes[] = 'Moyenne sur ' . $stats['count'] . ' mois calculés : ' . PayrollDashboardStats::number($stats['average']) . ' FCFA.';
        }

        $chart = PayrollTrend::chart($series);

        foreach ($chart['points'] as $i => $pt) {
            $chart['points'][$i]['title'] = $this->monthLabel($pt['period']) . ' : ' . PayrollDashboardStats::number($pt['value']) . ' FCFA' . ($pt['provisional'] ? ' (provisoire)' : '');
        }

        $comparison = PayrollTrend::comparison($months, $this->month);

        // Répartitions : seulement si le mois a des bulletins.
        $departments = null;
        $grossToNet = [];
        $composition = null;

        if ($totals !== null) {
            $periodId = PayrollPeriod::where('company_id', $this->companyId())->where('period', $this->month)->value('id');
            $departments = PayrollBreakdown::byDepartment(PayrollDashboardHistory::departments($this->companyId(), (int) $periodId), $this->metric);
            $grossToNet = PayrollBreakdown::grossToNet($totals, (int) $facts['sums']['its']);
            $composition = PayrollBreakdown::composition(PayrollDashboardHistory::components($this->companyId(), (int) $periodId), $totals['gross']);
        }

        $statusLabels = ['none' => 'À démarrer', 'draft' => 'Brouillon', 'calculated' => 'Calculée, pas encore clôturée', 'closed' => 'Clôturée'];
        $isCurrentMonth = $this->month === now()->format('Y-m');

        return view('livewire.payroll.dashboard-panel', [
            'monthLabel' => $this->monthLabel($this->month),
            'previousLabel' => $this->monthLabel($facts['previous']['period']),
            'isCurrentMonth' => $isCurrentMonth,
            'canNext' => $this->month < now()->format('Y-m'),
            'status' => $facts['period_status'],
            'statusLabel' => $statusLabels[$facts['period_status']] ?? '',
            'provisional' => in_array($facts['period_status'], ['draft', 'calculated'], true),
            'calculatedAt' => $facts['calculated_at'],
            'closedAt' => $facts['closed_at'],
            'count' => $totals['count'] ?? 0,
            'kpis' => $kpis,
            'cycle' => $cycle,
            'alerts' => $alerts,
            'delivery' => $facts['delivery'],
            'setupBanner' => $setupBanner,
            'metric' => $this->metric,
            'metrics' => PayrollTrend::METRICS,
            'metricLabel' => PayrollTrend::METRICS[$this->metric],
            'chart' => $chart,
            'trendNotes' => $trendNotes,
            'comparison' => $comparison,
            'previousPeriodLabel' => $this->monthLabel(PayrollTrend::shift($this->month, -1)),
            'lastYearLabel' => $this->monthLabel(PayrollTrend::shift($this->month, -12)),
            'departments' => $departments,
            'grossToNet' => $grossToNet,
            'composition' => $composition,
        ]);
    }

    /** Lien vers une section d'un pôle, seulement si l'écran existe et si l'utilisateur peut le lire. */
    protected function urlFor(array $target): ?string
    {
        if (! Route::has($target['route']) || ! $this->allowed('read', $target['resource'])) {
            return null;
        }

        return route($target['route']) . '#' . $target['anchor'];
    }

    protected function monthLabel(string $period): string
    {
        $months = ['janvier', 'février', 'mars', 'avril', 'mai', 'juin', 'juillet', 'août', 'septembre', 'octobre', 'novembre', 'décembre'];

        return $months[(int) substr($period, 5, 2) - 1] . ' ' . substr($period, 0, 4);
    }
}
