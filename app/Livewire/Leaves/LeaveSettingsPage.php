<?php

namespace App\Livewire\Leaves;

use App\Livewire\Concerns\AuthorizesResource;
use App\Models\Holiday;
use App\Models\LeaveSetting;
use App\Models\LeaveType;
use App\Models\Employee;
use App\Models\WorkSchedule;
use Illuminate\Validation\ValidationException;
use App\Services\AuditLogger;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('components.layouts.app')]
#[Title('Paramètres des congés')]
class LeaveSettingsPage extends Component
{
    use AuthorizesResource;

    public $negative_tolerance = 5;
    public $default_monthly_days = 2.5;
    public $recovery_validity_days = 90;
    public bool $deduct_unjustified = true;

    /** @var array<int, int> */
    public array $workdays = [1, 2, 3, 4, 5];

    public string $count_mode = 'calendar';
    public string $accrual_mode = 'monthly';
    public $accrual_per_days = 26;
    public string $bonuses_text = '';

    public string $sched_name = '';
    public string $sched_type = 'rotation';
    public array $sched_workdays = [1, 2, 3, 4, 5];
    public $sched_on = 14;
    public $sched_off = 14;
    public string $sched_anchor = '';
    public bool $sched_grants_leave = false;

    public string $holiday_date = '';
    public string $holiday_name = '';

    public string $type_name = '';
    public string $type_pool = 'annual';
    public bool $type_requires_document = false;
    public $type_max_days = '';

    public function mount(): void
    {
        $this->authorizeAction('read', 'leave_settings');

        $companyId = auth()->user()->company_id;
        LeaveType::ensureDefaults($companyId);
        $s = LeaveSetting::for($companyId);

        $this->negative_tolerance = $s->negative_tolerance;
        $this->default_monthly_days = $s->default_monthly_days;
        $this->recovery_validity_days = $s->recovery_validity_days;
        $this->deduct_unjustified = $s->deduct_unjustified;
        $this->workdays = $s->workdayList();
        $this->count_mode = $s->count_mode ?: 'calendar';
        $this->accrual_mode = $s->accrual_mode ?: 'monthly';
        $this->accrual_per_days = $s->accrual_per_days ?: 26;
        $this->bonuses_text = collect($s->bonusRules())->map(fn ($b) => "{$b[0]}:{$b[1]}")->implode(', ');
    }

    #[Computed]
    public function holidays()
    {
        return Holiday::orderBy('date')->whereYear('date', '>=', now()->year - 1)->get();
    }

    #[Computed]
    public function types()
    {
        return LeaveType::orderBy('sort')->get();
    }

    public function saveSettings(): void
    {
        $this->authorizeAction('update', 'leave_settings');

        $this->validate([
            'negative_tolerance' => ['required', 'numeric', 'between:0,30'],
            'default_monthly_days' => ['required', 'numeric', 'between:0,10'],
            'recovery_validity_days' => ['required', 'integer', 'between:1,730'],
            'deduct_unjustified' => ['boolean'],
            'workdays' => ['required', 'array', 'min:1'],
            'workdays.*' => ['integer', 'between:1,7'],
            'count_mode' => ['required', 'in:'.implode(',', array_keys(LeaveSetting::COUNT_MODES))],
            'accrual_mode' => ['required', 'in:'.implode(',', array_keys(LeaveSetting::ACCRUAL_MODES))],
            'accrual_per_days' => ['required', 'integer', 'between:1,365'],
        ], [], ['negative_tolerance' => 'tolérance', 'default_monthly_days' => 'cadence par défaut', 'workdays' => 'jours ouvrés']);

        $bonuses = $this->parseBonuses();

        $setting = LeaveSetting::for(auth()->user()->company_id);
        $old = $setting->only(['negative_tolerance', 'default_monthly_days', 'workdays', 'deduct_unjustified', 'recovery_validity_days', 'count_mode', 'accrual_mode', 'accrual_per_days', 'seniority_bonuses']);

        $setting->update([
            'negative_tolerance' => (float) $this->negative_tolerance,
            'default_monthly_days' => (float) $this->default_monthly_days,
            'recovery_validity_days' => (int) $this->recovery_validity_days,
            'deduct_unjustified' => $this->deduct_unjustified,
            'workdays' => array_values(array_unique(array_map('intval', $this->workdays))),
            'count_mode' => $this->count_mode,
            'accrual_mode' => $this->accrual_mode,
            'accrual_per_days' => (int) $this->accrual_per_days,
            'seniority_bonuses' => $bonuses,
        ]);

        AuditLogger::log('updated', 'leave_settings', $setting->id, $old, $setting->only(array_keys($old)));
        $this->dispatch('notify', type: 'success', message: 'Paramètres enregistrés.');
    }

    /** « 15:2, 20:4, 25:6 » → [[15, 2.0], [20, 4.0], [25, 6.0]] (années de service : jours en plus par an). */
    private function parseBonuses(): array
    {
        $out = [];

        foreach (preg_split('/[;,]+/', trim($this->bonuses_text)) ?: [] as $chunk) {
            $chunk = trim($chunk);

            if ($chunk === '') {
                continue;
            }
            if (! preg_match('/^(\d{1,2})\s*[:=]\s*(\d{1,2}(?:[.,]\d)?)$/', $chunk, $m)) {
                throw ValidationException::withMessages(['bonuses_text' => 'Format attendu : 15:2, 20:4, 25:6 (années de service : jours en plus par an).']);
            }

            $out[] = [(int) $m[1], (float) str_replace(',', '.', $m[2])];
        }

        usort($out, fn ($a, $b) => $a[0] <=> $b[0]);

        return $out;
    }

    #[Computed]
    public function schedules()
    {
        return WorkSchedule::orderBy('name')->get();
    }

    public function addSchedule(): void
    {
        $this->authorizeAction('update', 'leave_settings');

        $this->validate([
            'sched_name' => ['required', 'string', 'max:80'],
            'sched_type' => ['required', 'in:standard,rotation'],
            'sched_workdays' => ['required_if:sched_type,standard', 'array'],
            'sched_workdays.*' => ['integer', 'between:1,7'],
            'sched_on' => ['required_if:sched_type,rotation', 'nullable', 'integer', 'between:1,120'],
            'sched_off' => ['required_if:sched_type,rotation', 'nullable', 'integer', 'between:1,120'],
            'sched_anchor' => ['required_if:sched_type,rotation', 'nullable', 'date'],
        ], [], ['sched_name' => 'nom du planning', 'sched_on' => 'jours de travail', 'sched_off' => 'jours de repos', 'sched_anchor' => 'premier jour de travail']);

        $rotation = $this->sched_type === 'rotation';

        WorkSchedule::create([
            'name' => trim($this->sched_name),
            'type' => $this->sched_type,
            'workdays' => $rotation ? null : array_values(array_map('intval', $this->sched_workdays)),
            'on_days' => $rotation ? (int) $this->sched_on : null,
            'off_days' => $rotation ? (int) $this->sched_off : null,
            'anchor_date' => $rotation ? $this->sched_anchor : null,
            'grants_annual_leave' => $rotation ? $this->sched_grants_leave : true,
        ]);

        $this->reset(['sched_name', 'sched_anchor']);
        unset($this->schedules);
    }

    public function deleteSchedule(int $id): void
    {
        $this->authorizeAction('update', 'leave_settings');

        if (Employee::where('work_schedule_id', $id)->exists()) {
            throw ValidationException::withMessages(['sched_name' => 'Ce planning est affecté à des employés : changez d’abord leur planning (page Soldes).']);
        }

        WorkSchedule::findOrFail($id)->delete();
        unset($this->schedules);
    }

    /** Jours fériés FIXES du Mali (liste du guide consulté ; à confirmer). Les fêtes religieuses variables se saisissent à la main. */
    public function addMaliHolidays(): void
    {
        $this->authorizeAction('update', 'leave_settings');

        $fixed = ['01-01' => 'Nouvel An', '01-20' => 'Fête de l’Armée', '03-26' => 'Fête de la Démocratie', '05-01' => 'Fête du Travail',
            '05-25' => 'Fête de l’Afrique', '09-22' => 'Fête de l’Indépendance', '12-25' => 'Noël'];

        foreach ([now()->year, now()->year + 1] as $year) {
            foreach ($fixed as $md => $name) {
                if (! Holiday::whereDate('date', "{$year}-{$md}")->exists()) {
                    Holiday::create(['date' => "{$year}-{$md}", 'name' => $name]);
                }
            }
        }

        unset($this->holidays);
        $this->dispatch('notify', type: 'success', message: 'Jours fériés fixes ajoutés. Ajoutez les fêtes religieuses (dates variables) à la main.');
    }

    public function addHoliday(): void
    {
        $this->authorizeAction('update', 'leave_settings');

        $this->validate([
            'holiday_date' => ['required', 'date', Rule::unique('holidays', 'date')->where('company_id', auth()->user()->company_id)],
            'holiday_name' => ['required', 'string', 'max:120'],
        ], [], ['holiday_date' => 'date', 'holiday_name' => 'nom']);

        Holiday::create(['date' => $this->holiday_date, 'name' => trim($this->holiday_name)]);

        $this->reset(['holiday_date', 'holiday_name']);
        unset($this->holidays);
    }

    public function deleteHoliday(int $id): void
    {
        $this->authorizeAction('update', 'leave_settings');
        Holiday::findOrFail($id)->delete();
        unset($this->holidays);
    }

    public function addType(): void
    {
        $this->authorizeAction('update', 'leave_settings');

        $this->validate([
            'type_name' => ['required', 'string', 'max:100'],
            'type_pool' => ['required', Rule::in(['annual', 'recovery', 'none'])],
            'type_requires_document' => ['boolean'],
            'type_max_days' => ['nullable', 'numeric', 'between:0.5,365'],
        ], [], ['type_name' => 'nom du type']);

        $code = 'c'.substr(md5($this->type_name.microtime()), 0, 8);

        LeaveType::create([
            'code' => $code, 'name' => trim($this->type_name), 'pool' => $this->type_pool === 'none' ? null : $this->type_pool,
            'requires_document' => $this->type_requires_document, 'max_days' => $this->type_max_days !== '' ? (float) $this->type_max_days : null,
            'paid' => true, 'active' => true, 'sort' => ($this->types->max('sort') ?? 0) + 1,
        ]);

        $this->reset(['type_name', 'type_requires_document', 'type_max_days']);
        $this->type_pool = 'annual';
        unset($this->types);
    }

    public function toggleType(int $id, string $field): void
    {
        $this->authorizeAction('update', 'leave_settings');
        abort_unless(in_array($field, ['active', 'requires_document'], true), 422);

        $type = LeaveType::findOrFail($id);
        $type->update([$field => ! $type->{$field}]);

        unset($this->types);
    }

    public function render()
    {
        return view('livewire.leaves.leave-settings-page');
    }
}
