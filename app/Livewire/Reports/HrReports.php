<?php

namespace App\Livewire\Reports;

use App\Models\Employee;
use App\Services\HrReportEngine;
use App\Services\ReportPeriod;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;

/**
 * Rapports RH de l'entreprise. Filtres communs à tous les onglets (période, comparaison, cases à cocher) partagés dans l'adresse.
 * RH et Direction voient toute l'entreprise ; un chef de département voit uniquement son département.
 */
#[Layout('components.layouts.app')]
#[Title('Rapports RH')]
class HrReports extends Component
{
    #[Url(as: 'onglet')] public string $tab = 'synthesis';
    #[Url(as: 'periode')] public string $preset = '12m';
    #[Url(as: 'du')] public string $from = '';
    #[Url(as: 'au')] public string $to = '';
    #[Url(as: 'comparer')] public string $compare = 'previous';

    #[Url(as: 'dep')] public array $depts = [];
    #[Url(as: 'poste')] public array $positions = [];
    #[Url(as: 'contrat')] public array $contracts = [];
    #[Url(as: 'sexe')] public array $genders = [];

    public function mount(): void
    {
        abort_unless(auth()->user()->canDo('read', 'hr_reports'), 403);

        if (! in_array($this->tab, ['synthesis', 'workforce'], true)) {
            $this->tab = 'synthesis';
        }
    }

    /** Département imposé à qui n'a pas la vue Direction et RH. */
    private function forcedDepartment(): ?int
    {
        $u = auth()->user();

        if ($u->canDo('read', 'dashboard_hr') || ! $u->employee_id) {
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
        return new HrReportEngine((int) auth()->user()->company_id, $this->filters(), auth()->user()->canDo('read', 'employees'));
    }

    public function removeFilter(string $group, string $value): void
    {
        if (in_array($group, ['depts', 'positions', 'contracts', 'genders'], true)) {
            $this->{$group} = array_values(array_filter($this->{$group}, fn ($v) => (string) $v !== $value));
        }
    }

    public function clearGroup(string $group): void
    {
        if (in_array($group, ['depts', 'positions', 'contracts', 'genders'], true)) {
            $this->{$group} = [];
        }
    }

    public function resetFilters(): void
    {
        $this->reset(['preset', 'from', 'to', 'compare', 'depts', 'positions', 'contracts', 'genders']);
    }

    public function refreshData(): void
    {
        HrReportEngine::forget((int) auth()->user()->company_id);
        $this->dispatch('notify', type: 'success', message: 'Données actualisées.');
    }

    /** Export CSV (séparateur « ; », UTF-8 avec BOM : s'ouvre directement dans Excel) du tableau de l'onglet affiché. */
    public function export()
    {
        abort_unless(auth()->user()->canDo('read', 'hr_reports'), 403);

        [$name, $headers, $rows] = $this->engine->exportRows($this->tab);

        return response()->streamDownload(function () use ($headers, $rows) {
            echo "\xEF\xBB\xBF";
            $out = fopen('php://output', 'w');
            fputcsv($out, $headers, ';');

            foreach ($rows as $row) {
                fputcsv($out, array_map(fn ($v) => $v ?? '', $row), ';');
            }

            fclose($out);
        }, 'rapport-rh-'.$name.'-'.now()->format('Ymd').'.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    public function render()
    {
        $e = $this->engine;
        $data = ['options' => $e->options(), 'from_d' => $e->from, 'to_d' => $e->to, 'forced' => $this->forcedDepartment()];

        if ($this->tab === 'workforce') {
            $data['w'] = $e->workforce();
        } else {
            $this->tab = 'synthesis';
            $data['s'] = $e->synthesis();
            $data['canSalary'] = auth()->user()->canDo('read', 'dashboard_payroll');
        }

        return view('livewire.reports.hr-reports', $data);
    }
}
