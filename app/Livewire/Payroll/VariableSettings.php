<?php

namespace App\Livewire\Payroll;

use App\Livewire\Payroll\Concerns\PayrollGuards;
use App\Models\OvertimeRate;
use App\Models\PayrollVariableSetting;
use Livewire\Component;

class VariableSettings extends Component
{
    use PayrollGuards;

    public string $pay_basis = 'base_supplement';
    public string $suspension_count_mode = 'working';
    /** @var array<int, int|string> */
    public array $rest = [0];
    public bool $unjustified_absence_unpaid = true;
    public string $loan_effort_alert_percent = '';
    public string $arrival_rule = 'threshold';
    public string $arrival_grace_days = '5';
    public string $departure_rule = 'prorate';
    public string $departure_grace_days = '5';

    public string $newLabel = '';
    public string $newPercent = '';

    public string $flash = '';

    public function mount(): void
    {
        $this->allow('read', 'payroll_settings');

        $s = PayrollVariableSetting::forCompany($this->companyId());
        $this->pay_basis = $s->pay_basis;
        $this->suspension_count_mode = $s->suspension_count_mode;
        $this->rest = array_map('strval', $s->restDays());
        $this->unjustified_absence_unpaid = $s->unjustified_absence_unpaid;
        $this->loan_effort_alert_percent = $s->loan_effort_alert_percent !== null ? rtrim(rtrim(number_format((float) $s->loan_effort_alert_percent, 1, '.', ''), '0'), '.') : '';
        $this->arrival_rule = (string) ($s->arrival_rule ?? 'threshold');
        $this->arrival_grace_days = (string) ($s->arrival_grace_days ?? 5);
        $this->departure_rule = (string) ($s->departure_rule ?? 'prorate');
        $this->departure_grace_days = (string) ($s->departure_grace_days ?? 5);
    }

    public function save(): void
    {
        $this->allow('update', 'payroll_settings');

        $this->loan_effort_alert_percent = str_replace(',', '.', trim($this->loan_effort_alert_percent));

        $this->validate([
            'pay_basis' => 'required|in:base,base_supplement',
            'suspension_count_mode' => 'required|in:working,calendar',
            'rest' => 'array',
            'rest.*' => 'integer|between:0,6',
            'loan_effort_alert_percent' => 'nullable|numeric|between:1,100',
            'arrival_rule' => 'required|in:prorate,full,threshold',
            'arrival_grace_days' => 'required|integer|between:0,30',
            'departure_rule' => 'required|in:prorate,full,threshold',
            'departure_grace_days' => 'required|integer|between:0,30',
        ], [], ['loan_effort_alert_percent' => "seuil d'alerte", 'arrival_grace_days' => 'jour limite d\'arrivée', 'departure_grace_days' => 'nombre de jours de fin de mois']);

        $s = PayrollVariableSetting::forCompany($this->companyId());
        $old = $s->only(['pay_basis', 'suspension_count_mode', 'rest_weekdays', 'unjustified_absence_unpaid', 'loan_effort_alert_percent', 'arrival_rule', 'arrival_grace_days', 'departure_rule', 'departure_grace_days']);

        $s->update([
            'pay_basis' => $this->pay_basis,
            'suspension_count_mode' => $this->suspension_count_mode,
            'rest_weekdays' => array_values(array_unique(array_map('intval', $this->rest))),
            'unjustified_absence_unpaid' => $this->unjustified_absence_unpaid,
            'loan_effort_alert_percent' => $this->loan_effort_alert_percent !== '' ? (float) $this->loan_effort_alert_percent : null,
        ]);

        $s->forceFill([
            'arrival_rule' => $this->arrival_rule,
            'arrival_grace_days' => (int) $this->arrival_grace_days,
            'departure_rule' => $this->departure_rule,
            'departure_grace_days' => (int) $this->departure_grace_days,
        ])->save();

        $this->audit('update', 'payroll_settings', $s->id, $old, $s->only(array_keys($old)) + ['scope' => 'variables']);
        $this->flash = 'Réglages enregistrés.';
    }

    public function addRate(): void
    {
        $this->allow('update', 'payroll_settings');

        $this->newPercent = str_replace(',', '.', trim($this->newPercent));

        $this->validate([
            'newLabel' => 'required|string|max:80',
            'newPercent' => 'required|numeric|between:0,500',
        ], [], ['newLabel' => 'libellé', 'newPercent' => 'majoration']);

        if (OvertimeRate::where('percent', (float) $this->newPercent)->exists()) {
            $this->addError('newPercent', 'Cette majoration existe déjà.');

            return;
        }

        $rate = new OvertimeRate(['label' => trim($this->newLabel), 'percent' => (float) $this->newPercent, 'sort' => (int) OvertimeRate::max('sort') + 1, 'is_active' => true]);
        $rate->company_id = $this->companyId();
        $rate->save();

        $this->audit('create', 'payroll_settings', $rate->id, null, ['overtime_rate' => $rate->label, 'percent' => $rate->percent]);
        $this->newLabel = '';
        $this->newPercent = '';
        $this->flash = 'Majoration ajoutée.';
    }

    public function toggleRate(int $id): void
    {
        $this->allow('update', 'payroll_settings');
        $rate = OvertimeRate::findOrFail($id);
        $rate->update(['is_active' => ! $rate->is_active]);
        $this->audit('update', 'payroll_settings', $rate->id, ['is_active' => ! $rate->is_active], ['is_active' => $rate->is_active, 'overtime_rate' => $rate->label]);
    }

    public function deleteRate(int $id): void
    {
        $this->allow('update', 'payroll_settings');
        $rate = OvertimeRate::findOrFail($id);
        $this->audit('delete', 'payroll_settings', $rate->id, ['overtime_rate' => $rate->label, 'percent' => $rate->percent], null);
        $rate->delete();
        $this->flash = 'Majoration supprimée. Les lignes déjà saisies gardent leur taux.';
    }

    public function render()
    {
        return view('livewire.payroll.variable-settings', [
            'rates' => OvertimeRate::orderBy('sort')->orderBy('percent')->get(),
            'days' => [1 => 'Lundi', 2 => 'Mardi', 3 => 'Mercredi', 4 => 'Jeudi', 5 => 'Vendredi', 6 => 'Samedi', 0 => 'Dimanche'],
            'canEdit' => $this->allowed('update', 'payroll_settings'),
        ])->layout($this->resolveLayout('company'), ['title' => 'Réglages des variables']);
    }
}
