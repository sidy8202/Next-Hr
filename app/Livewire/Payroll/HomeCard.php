<?php

namespace App\Livewire\Payroll;

use App\Livewire\Payroll\Concerns\PayrollGuards;
use App\Models\PayrollPeriod;
use App\Services\Payroll\PayrollAlerts;
use App\Services\Payroll\PayrollCycle;
use App\Services\Payroll\PayrollDashboardFacts;
use App\Services\Payroll\PayrollDashboardStats;
use Illuminate\Support\Facades\Route;
use Livewire\Component;

/**
 * Carte de paie pour l'accueil : étape du cycle, net du mois comparé au mois précédent, nombre d'alertes, lien vers le tableau de bord paie.
 * À poser dans une vue du tableau de bord avec <livewire:payroll.home-card />.
 *
 * Elle se masque seule (sans erreur) si l'utilisateur n'a pas le droit de lire « dashboard_payroll » : on peut donc
 * la placer dans une vue partagée avec d'autres profils sans faire échouer la page.
 */
class HomeCard extends Component
{
    use PayrollGuards;

    public function render()
    {
        if (! $this->allowed('read', 'dashboard_payroll')) {
            return view('livewire.payroll.home-card', ['visible' => false]);
        }
       
        $companyId = $this->companyId();

        // Même mois que l'écran « Paie du mois » : la plus ancienne paie encore ouverte, sinon le mois courant.
        $month = PayrollPeriod::where('company_id', $companyId)->where('status', '!=', 'closed')->orderBy('period')->value('period') ?: now()->format('Y-m');
        $facts = PayrollDashboardFacts::load($companyId, $month);

        $totals = $facts['sums'] === null ? null : PayrollDashboardStats::totals($facts['sums']);
        $previous = $facts['previous']['sums'] === null ? null : PayrollDashboardStats::totals($facts['previous']['sums']);

        $cycle = PayrollCycle::steps([
            'period_status' => $facts['period_status'],
            'pending_variables' => $facts['pending_variables'],
            'payslips' => $totals['count'] ?? 0,
            'pending_checks' => $facts['pending_checks'],
            'delivery' => $facts['delivery'],
        ]);

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

        $critical = count(array_filter($alerts, fn ($a) => $a['severity'] === PayrollAlerts::CRITICAL));
        $warning = count(array_filter($alerts, fn ($a) => $a['severity'] === PayrollAlerts::WARNING));
        $current = null;

        foreach ($cycle['steps'] as $step) {
            if ($step['status'] === PayrollCycle::CURRENT) {
                $current = $step;
            }
        }

        $months = ['janvier', 'février', 'mars', 'avril', 'mai', 'juin', 'juillet', 'août', 'septembre', 'octobre', 'novembre', 'décembre'];
        $statusLabels = ['none' => 'À démarrer', 'draft' => 'Brouillon', 'calculated' => 'Calculée, pas encore clôturée', 'closed' => 'Clôturée'];

        return view('livewire.payroll.home-card', [
            'visible' => true,
            'monthLabel' => $months[(int) substr($month, 5, 2) - 1] . ' ' . substr($month, 0, 4),
            'statusLabel' => $statusLabels[$facts['period_status']] ?? '',
            'provisional' => in_array($facts['period_status'], ['draft', 'calculated'], true),
            'net' => $totals === null ? null : PayrollDashboardStats::number($totals['net_to_pay']),
            'netDelta' => $totals === null ? null : PayrollDashboardStats::deltaShort(PayrollDashboardStats::delta($totals['net_to_pay'], $previous === null ? null : $previous['net_to_pay'])),
            'count' => $totals['count'] ?? 0,
            'cycle' => $cycle,
            'currentStep' => $current,
            'critical' => $critical,
            'warning' => $warning,
            'url' => Route::has('payroll.dashboard') ? route('payroll.dashboard') : null,
        ]);
    }
}
