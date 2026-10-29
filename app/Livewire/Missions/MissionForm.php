<?php

namespace App\Livewire\Missions;

use App\Livewire\Concerns\AuthorizesResource;
use App\Models\Employee;
use App\Models\Mission;
use App\Services\MissionEmployeeLookup;
use App\Services\MissionService;
use App\Services\PerdiemCalculator;
use Carbon\CarbonImmutable;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Throwable;

/** Saisie d'une mission : informations communes, puis participants (identité seulement), perdiem et paiement. */
#[Layout('components.layouts.app')]
#[Title('Ordre de mission')]
class MissionForm extends Component
{
    use AuthorizesResource;

    public ?int $missionId = null;

    public string $purpose = '';
    public string $destination_zone = '';
    public string $lodging = '';
    public string $start_date = '';
    public string $end_date = '';
    public string $program = '';

    /** Lignes : employee_id, name, matricule, position, department_id, department, role, perdiem_mode, daily_rate, payment_mode */
    public array $participants = [];

    public string $search = '';

    public function mount(?int $mission = null): void
    {
        if ($mission) {
            $m = Mission::with('participants.employee.position', 'participants.department')->findOrFail($mission);
            abort_unless(app(MissionService::class)->canEdit(auth()->user(), $m), 403);
            $this->authorizeAction('update', 'mission_orders');

            $this->missionId = $m->id;
            $this->purpose = $m->purpose;
            $this->destination_zone = $m->destination_zone;
            $this->lodging = (string) $m->lodging;
            $this->start_date = $m->start_date->format('Y-m-d');
            $this->end_date = $m->end_date->format('Y-m-d');
            $this->program = (string) $m->program;

            foreach ($m->participants as $p) {
                $own = (int) $p->department_id === (int) $m->department_id;
                $this->participants[] = [
                    'employee_id' => $p->employee_id,
                    'name' => $p->employee?->full_name,
                    'matricule' => $p->employee?->matricule,
                    'position' => $p->employee?->position?->title,
                    'department_id' => $p->department_id,
                    'department' => $p->department?->name,
                    'role' => $p->role->value,
                    'perdiem_mode' => $own ? $p->perdiem_mode->value : 'to_define',
                    'daily_rate' => $own && $p->daily_rate ? (string) $p->daily_rate : '',
                    'payment_mode' => $p->payment_mode,
                ];
            }
        } else {
            $this->authorizeAction('create', 'mission_orders');
        }
    }

    #[Computed]
    public function ownDepartmentId(): ?int
    {
        if ($this->missionId) {
            return Mission::whereKey($this->missionId)->value('department_id');
        }

        $id = auth()->user()->employee_id;

        return $id ? Employee::withoutGlobalScopes()->whereKey($id)->value('department_id') : null;
    }

    private function addedIds(): array
    {
        return array_map('intval', array_column($this->participants, 'employee_id'));
    }

    #[Computed]
    public function results()
    {
        return app(MissionEmployeeLookup::class)->search(auth()->user()->company_id, $this->search, $this->addedIds());
    }

    #[Computed]
    public function myTeam()
    {
        return $this->ownDepartmentId
            ? app(MissionEmployeeLookup::class)->byDepartment(auth()->user()->company_id, $this->ownDepartmentId, $this->addedIds())
            : collect();
    }

    #[Computed]
    public function days(): ?int
    {
        try {
            return ($this->start_date && $this->end_date)
                ? PerdiemCalculator::days(CarbonImmutable::parse($this->start_date), CarbonImmutable::parse($this->end_date))
                : null;
        } catch (Throwable) {
            return null;
        }
    }

    public function addParticipant(int $employeeId): void
    {
        if (in_array($employeeId, $this->addedIds(), true)) {
            return;
        }

        $row = app(MissionEmployeeLookup::class)->find(auth()->user()->company_id, $employeeId);

        if (! $row) {
            return;
        }

        $own = (int) $row['department_id'] === (int) $this->ownDepartmentId;

        $this->participants[] = $row + [
            'role' => 'member',
            'perdiem_mode' => $own ? 'manual' : 'to_define',
            'daily_rate' => '',
            'payment_mode' => 'after',
        ];

        $this->search = '';
    }

    public function removeParticipant(int $index): void
    {
        unset($this->participants[$index]);
        $this->participants = array_values($this->participants);
    }

    public function saveDraft(): void
    {
        $this->persist(false);
    }

    public function submit(): void
    {
        $this->persist(true);
    }

    private function persist(bool $submit): void
    {
        $this->authorizeAction($this->missionId ? 'update' : 'create', 'mission_orders');

        $this->validate([
            'purpose' => ['required', 'string', 'min:5', 'max:190'],
            'destination_zone' => ['required', 'string', 'max:190'],
            'lodging' => ['nullable', 'string', 'max:190'],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
            'program' => ['nullable', 'string', 'max:3000'],
            'participants' => ['required', 'array', 'min:1'],
            'participants.*.role' => ['required', 'in:member,chief,driver'],
            'participants.*.payment_mode' => ['required', 'in:'.implode(',', array_keys(config('missions.payment_modes')))],
            'participants.*.daily_rate' => ['nullable', 'integer', 'min:0', 'max:10000000'],
        ], [
            'participants.required' => 'Ajoutez au moins un participant.',
            'participants.min' => 'Ajoutez au moins un participant.',
        ], ['purpose' => 'objet', 'destination_zone' => 'destination', 'start_date' => 'date de départ', 'end_date' => 'date de retour']);

        try {
            $mission = app(MissionService::class)->save(
                auth()->user(),
                $this->missionId ? Mission::findOrFail($this->missionId) : null,
                $this->only(['purpose', 'destination_zone', 'lodging', 'start_date', 'end_date', 'program']),
                $this->participants,
                $submit,
            );
        } catch (ValidationException|HttpException $e) {
            throw $e;
        } catch (Throwable $e) {
            report($e);
            $this->addError('mission', config('app.debug') ? 'Enregistrement impossible : '.$e->getMessage() : 'Enregistrement impossible : rien n’a été enregistré.');

            return;
        }

        session()->flash('status', $submit ? 'Mission envoyée à la RH pour vérification.' : 'Brouillon enregistré.');
        $this->redirectRoute('missions.show', ['mission' => $mission->id], navigate: true);
    }

    public function render()
    {
        return view('livewire.missions.mission-form');
    }
}
