<?php

namespace App\Livewire\Payroll;

use App\Livewire\Payroll\Concerns\PayrollGuards;
use App\Models\PayrollSetting;
use Livewire\Component;

class Settings extends Component
{
    use PayrollGuards;

    public string $country = 'ML';
    public string $convention_name = '';
    public string $days_basis = '30';
    public string $hours_month = '173.33';
    public string $rounding = 'nearest';
    public bool $show_employer_part = false;
    public string $flash = '';

    public function mount(): void
    {
        $this->allow('read', 'payroll_settings');

        $s = PayrollSetting::forCompany($this->companyId());
        $this->country = (string) $s->country;
        $this->convention_name = (string) $s->convention_name;
        $this->days_basis = (string) $s->days_basis;
        $this->hours_month = rtrim(rtrim(number_format((float) $s->hours_month, 2, '.', ''), '0'), '.');
        $this->rounding = (string) $s->rounding;
        $this->show_employer_part = (bool) $s->show_employer_part;
    }

    public function save(): void
    {
        $this->allow('update', 'payroll_settings');

        $this->hours_month = str_replace(',', '.', trim($this->hours_month));

        $this->validate([
            'country' => 'required|in:ML',
            'convention_name' => 'nullable|string|max:150',
            'days_basis' => 'required|integer|between:1,31',
            'hours_month' => 'required|numeric|between:1,744',
            'rounding' => 'required|in:nearest,floor,ceil',
            'show_employer_part' => 'boolean',
        ], [], [
            'convention_name' => 'convention collective',
            'days_basis' => 'base de jours',
            'hours_month' => 'heures du mois',
            'rounding' => 'arrondi',
        ]);

        $s = PayrollSetting::forCompany($this->companyId());
        $old = $s->only(['country', 'convention_name', 'days_basis', 'hours_month', 'rounding', 'show_employer_part']);

        $s->update([
            'country' => $this->country,
            'convention_name' => trim($this->convention_name) ?: null,
            'days_basis' => (int) $this->days_basis,
            'hours_month' => (float) $this->hours_month,
            'rounding' => $this->rounding,
            'show_employer_part' => $this->show_employer_part,
        ]);

        $this->audit('update', 'payroll_settings', $s->id, $old, $s->only(array_keys($old)));
        $this->flash = 'Paramètres de paie enregistrés.';
    }

    public function render()
    {
        return view('livewire.payroll.settings', [
            'canEdit' => $this->allowed('update', 'payroll_settings'),
        ])->layout($this->resolveLayout('company'), ['title' => 'Paramètres de paie']);
    }
}
