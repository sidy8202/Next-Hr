<?php

namespace App\Livewire\Platform;

use App\Models\Plan;
use App\Models\PlanFeature;
use App\Models\PlanPrice;
use App\Services\PlanGate;
use App\Services\PlatformAudit;
use App\Services\PlatformStats;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('components.layouts.platform')]
#[Title('Formules et matrice')]
class PlanMatrix extends Component
{
    public array $limits = [];   // [plan_id => name, max_employees, storage_gb, audit_retention_months, is_active]
    public array $prices = [];   // [plan_id => [months => price]]
    public array $flags = [];    // [plan_id => [feature => bool]]

    public function mount(): void
    {
        abort_unless(auth()->user()->platformCan('plans.manage'), 403);
        $this->load();
    }

    private function load(): void
    {
        $this->limits = $this->prices = $this->flags = [];

        foreach (Plan::with(['prices', 'features'])->orderBy('sort')->get() as $p) {
            $this->limits[$p->id] = ['name' => $p->name, 'max_employees' => $p->max_employees === null ? '' : (string) $p->max_employees, 'storage_gb' => (string) $p->storage_gb, 'audit_retention_months' => (string) $p->audit_retention_months, 'is_active' => (bool) $p->is_active];

            foreach (array_keys(config('platform.periods')) as $m) {
                $this->prices[$p->id][$m] = ($v = $p->priceFor($m)) === null ? '' : (string) $v;
            }

            foreach (array_keys(config('plans.features')) as $f) {
                $this->flags[$p->id][$f] = $p->hasFeature($f);
            }
        }
    }

    public function save(): void
    {
        abort_unless(auth()->user()->platformCan('plans.manage'), 403);

        $this->validate([
            'limits.*.name' => ['required', 'string', 'max:60'],
            'limits.*.max_employees' => ['nullable', 'integer', 'min:1', 'max:1000000'],
            'limits.*.storage_gb' => ['required', 'integer', 'min:1', 'max:100000'],
            'limits.*.audit_retention_months' => ['required', 'integer', 'min:1', 'max:600'],
            'prices.*.*' => ['nullable', 'integer', 'min:0', 'max:1000000000000'],
        ], [], ['limits.*.name' => 'nom', 'limits.*.max_employees' => 'plafond d’employés', 'limits.*.storage_gb' => 'stockage', 'limits.*.audit_retention_months' => 'conservation de l’audit', 'prices.*.*' => 'prix']);

        foreach ($this->limits as $planId => $l) {
            Plan::whereKey($planId)->update([
                'name' => $l['name'], 'max_employees' => $l['max_employees'] === '' ? null : (int) $l['max_employees'],
                'storage_gb' => (int) $l['storage_gb'], 'audit_retention_months' => (int) $l['audit_retention_months'], 'is_active' => (bool) $l['is_active'],
            ]);

            foreach ($this->prices[$planId] ?? [] as $months => $price) {
                PlanPrice::updateOrCreate(['plan_id' => $planId, 'months' => $months], ['price' => $price === '' ? null : (int) $price]);
            }

            foreach ($this->flags[$planId] ?? [] as $feature => $on) {
                PlanFeature::updateOrCreate(['plan_id' => $planId, 'feature' => $feature], ['enabled' => (bool) $on]);
            }
        }

        PlanGate::flushAll();
        PlatformStats::forget();
        PlatformAudit::log('plans_updated', 'Formules, prix et matrice des fonctionnalités modifiés');
        $this->dispatch('notify', type: 'success', message: 'Formules enregistrées : la matrice s’applique immédiatement.');
    }

    public function render()
    {
        return view('livewire.platform.plan-matrix', ['plans' => Plan::orderBy('sort')->get(['id', 'code', 'name', 'color'])]);
    }
}
