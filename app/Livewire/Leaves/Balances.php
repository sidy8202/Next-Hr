<?php

namespace App\Livewire\Leaves;

use App\Livewire\Concerns\AuthorizesResource;
use App\Models\Employee;
use App\Models\WorkSchedule;
use App\Services\AuditLogger;
use App\Services\LeaveBalanceService;
use App\Services\LeaveService;
use App\Support\LeaveVisibility;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

/** Soldes de congé de tous les employés (RH) : cadence mensuelle, solde initial, corrections. */
#[Layout('components.layouts.app')]
#[Title('Soldes de congé')]
class Balances extends Component
{
    use AuthorizesResource, WithPagination;

    #[Url(as: 'q')]
    public string $search = '';

    /** @var array<int, string|float> */
    public array $rates = [];

    public ?int $adjusting = null;
    public string $pool = 'annual';
    public $adjustDays = '';
    public string $adjustNote = '';

    public function mount(): void
    {
        $user = auth()->user();

        abort_unless(
            $user->is_company_owner || $user->canDo('read', 'leave_balances'),
            403,
            'Il faut le droit « Lire » sur « Soldes de congés » (module Congés) pour ouvrir cette page.'
        );
    }

    /** Modification réservée à la RH : droit « Modifier » sur « Soldes de congés » avec la portée « Tout » (ou propriétaire). */
    #[Computed]
    public function canEdit(): bool
    {
        return LeaveVisibility::isHr(auth()->user(), 'leave_balances');
    }

    private function guard(): void
    {
        abort_unless(
            $this->canEdit,
            403,
            'Modification réservée : il faut le droit « Modifier » avec la portée « Tout » sur « Soldes de congés ».'
        );
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    #[Computed]
    public function employees()
    {
        $term = trim($this->search);
        $ids = LeaveVisibility::employeeIds(auth()->user(), 'leave_balances'); // null = tous ; sinon soi-même / son équipe

        $page = Employee::with('department:id,name')->where('status', 'active')
            ->when($ids !== null, fn ($q) => $q->whereIn('id', $ids))
            ->when($term !== '', fn ($q) => $q->where(fn ($w) => $w
                ->where('first_name', 'like', "%{$term}%")->orWhere('last_name', 'like', "%{$term}%")->orWhere('matricule', 'like', "{$term}%")))
            ->orderBy('last_name')->paginate(20);

        $balances = app(LeaveBalanceService::class);

        $page->getCollection()->each(function (Employee $e) use ($balances) {
            $balances->sync($e);
            $e->setAttribute('balance', $balances->balance($e->id));
            $e->setAttribute('reserved', $balances->reserved($e->id));
            $e->setAttribute('recovery', $balances->balance($e->id, 'recovery'));
            $e->setAttribute('rate', $balances->monthlyRate($e));
        });

        return $page;
    }

    public function saveRate(int $id): void
    {
        $this->guard();

        $value = $this->rates[$id] ?? null;
        $this->validateOnly("rates.{$id}", ["rates.{$id}" => ['required', 'numeric', 'between:0,10']], [], ["rates.{$id}" => 'cadence mensuelle']);

        $employee = Employee::findOrFail($id);
        $old = $employee->leave_days_per_month;

        Employee::whereKey($id)->update(['leave_days_per_month' => (float) $value]);
        AuditLogger::log('updated', 'leave_requests', $id, ['leave_days_per_month' => $old], ['leave_days_per_month' => (float) $value]);

        unset($this->employees);
        $this->dispatch('notify', type: 'success', message: 'Cadence mensuelle enregistrée (elle s’applique aux prochains mois).');
    }

    #[Computed]
    public function schedules()
    {
        return WorkSchedule::orderBy('name')->get(['id', 'name', 'type']);
    }

    /** Affecte un planning (rotation ou semaine standard) à un employé ; vide = semaine standard de l'entreprise. */
    public function assignSchedule(int $employeeId, $scheduleId): void
    {
        $this->guard();

        $scheduleId = ($scheduleId === '' || $scheduleId === null) ? null : (int) $scheduleId;
        abort_if($scheduleId !== null && ! WorkSchedule::whereKey($scheduleId)->exists(), 422);
        Employee::findOrFail($employeeId); // filtré par entreprise

        Employee::whereKey($employeeId)->update(['work_schedule_id' => $scheduleId]);
        AuditLogger::log('updated', 'leave_requests', $employeeId, null, ['work_schedule_id' => $scheduleId]);

        unset($this->employees);
        $this->dispatch('notify', type: 'success', message: 'Planning mis à jour.');
    }

    public function startAdjust(int $id): void
    {
        $this->guard();
        $this->adjusting = $id;
        $this->pool = 'annual';
        $this->adjustDays = '';
        $this->adjustNote = '';
        $this->resetValidation();
    }

    public function stopAdjust(): void
    {
        $this->adjusting = null;
    }

    public function adjust(): void
    {
        $this->guard();
        abort_unless($this->adjusting, 422);

        $this->validate([
            'adjustDays' => ['required', 'numeric', 'between:-365,365'],
            'adjustNote' => ['required', 'string', 'min:3', 'max:255'],
            'pool' => ['required', 'in:annual,recovery'],
        ], [], ['adjustDays' => 'nombre de jours', 'adjustNote' => 'motif']);

        app(LeaveService::class)->adjust(Employee::findOrFail($this->adjusting), $this->pool, (float) $this->adjustDays, $this->adjustNote);

        $this->adjusting = null;
        unset($this->employees);
        $this->dispatch('notify', type: 'success', message: 'Solde ajusté.');
    }

    public function render()
    {
        return view('livewire.leaves.balances');
    }
}
