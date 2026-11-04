<?php

namespace App\Livewire\Platform;

use App\Models\Plan;
use App\Services\PlatformReports;
use App\Services\ReportPeriod;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;

#[Layout('components.layouts.platform')]
#[Title('Rapports et analyses')]
class Reports extends Component
{
    #[Url(as: 'onglet')] public string $tab = 'overview';
    #[Url(as: 'periode')] public string $preset = '12m';
    #[Url(as: 'du')] public string $from = '';
    #[Url(as: 'au')] public string $to = '';
    #[Url(as: 'comparer')] public string $compare = 'previous';

    #[Url] public string $sector = '';
    #[Url] public string $country = '';
    #[Url(as: 'formule')] public string $plan = '';
    #[Url(as: 'facturation')] public string $period = '';
    #[Url(as: 'statut')] public string $status = '';
    #[Url(as: 'taille')] public string $size = '';

    #[Url(as: 'dim')] public string $segDim = 'sector';
    #[Url(as: 'mesure')] public string $segMetric = 'companies';
    #[Url(as: 'saison')] public string $seasonMetric = 'subs';

    public string $cmpDim = 'sector';
    public array $cmpValues = [];
    public string $cmpMetric = 'revenue';

    public function mount(): void
    {
        abort_unless(auth()->user()->platformCan('reports.view'), 403);
    }

    public function updatedCmpDim(): void
    {
        $this->cmpValues = [];
    }

    public function updatedCmpValues(): void
    {
        $this->cmpValues = array_slice(array_values(array_unique($this->cmpValues)), 0, 5);
    }

    public function resetFilters(): void
    {
        $this->reset(['preset', 'from', 'to', 'compare', 'sector', 'country', 'plan', 'period', 'status', 'size']);
    }

    private function filters(): array
    {
        return [
            'preset' => $this->preset, 'from' => $this->from, 'to' => $this->to, 'compare' => $this->compare,
            'sector' => $this->sector, 'country' => $this->country, 'plan' => $this->plan, 'period' => $this->period, 'status' => $this->status, 'size' => $this->size,
        ];
    }

    #[Computed]
    public function report(): PlatformReports
    {
        return new PlatformReports($this->filters());
    }

    /** Export CSV (séparateur « ; », encodage UTF-8 avec BOM : s'ouvre directement dans Excel) de l'onglet affiché. */
    public function export()
    {
        abort_unless(auth()->user()->platformCan('reports.view'), 403);

        [$name, $headers, $rows] = $this->report->exportRows($this->tab, ['season' => $this->seasonMetric, 'dim' => $this->tab === 'comparison' ? $this->cmpDim : $this->segDim, 'values' => $this->cmpValues ?: $this->defaultCompare()]);

        return response()->streamDownload(function () use ($headers, $rows) {
            echo "\xEF\xBB\xBF";
            $out = fopen('php://output', 'w');
            fputcsv($out, $headers, ';');

            foreach ($rows as $row) {
                fputcsv($out, array_map(fn ($v) => $v ?? '', $row), ';');
            }

            fclose($out);
        }, 'rapport-'.$name.'-'.now()->format('Ymd').'.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    private function defaultCompare(): array
    {
        return array_slice(array_map('strval', array_keys($this->report->candidates($this->cmpDim))), 0, 3);
    }

    public function render()
    {
        if ($this->tab === 'comparison' && ! $this->cmpValues) {
            $this->cmpValues = $this->defaultCompare();   // pré-coche les 3 premiers éléments
        }

        $r = $this->report;
        $data = ['plans' => Plan::orderBy('sort')->get(['code', 'name']), 'companiesCount' => $r->filteredCompanies(), 'from_d' => $r->from, 'to_d' => $r->to];

        match ($this->tab) {
            'seasonality' => $data['season'] = $r->seasonality($this->seasonMetric),
            'segments' => $data['seg'] = $r->segments($this->segDim),
            'retention' => $data['ret'] = $r->retention(),
            'forecast' => $data['fc'] = $r->forecast(),
            'comparison' => $data['cmp'] = $r->comparison($this->cmpDim, $this->cmpValues ?: $this->defaultCompare()),
            default => $this->overviewData($r, $data),
        };

        return view('livewire.platform.reports', $data);
    }

    private function overviewData(PlatformReports $r, array &$data): void
    {
        $this->tab = 'overview';
        $data['ov'] = $r->overview();
        $seg = $r->segments($this->segDim);
        $data['insights'] = $r->insights($data['ov'], $seg, $r->seasonality('subs'), $r->forecast(), $r->retention());
    }
}
