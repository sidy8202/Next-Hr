<?php

namespace App\Livewire\Access;

use App\Livewire\Concerns\AuthorizesResource;
use App\Models\Department;
use App\Models\Permission;
use App\Models\User;
use App\Services\AuditLogger;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;

#[Layout('components.layouts.app')]
#[Title('Droits d’accès')]
class AccessMatrix extends Component
{
    use AuthorizesResource;

    private const ACTIONS = ['create', 'read', 'update', 'delete'];

    private const RANK = ['own' => 1, 'team' => 2, 'all' => 3];

    public ?int $departmentId = null;   // filtre département
    public $pickUserId = null;          // utilisateur choisi dans le select
    
    #[Url(as: 'user')]
    public ?int $userId = null;

    public string $userSearch = '';

    public string $template = '';

    /** @var array<string, array{create:bool, read:bool, update:bool, delete:bool, scope:string}> */
    public array $matrix = [];

    public function mount(): void
    {
        $this->authorizeAction('read', 'permissions');

        if ($this->userId && $this->target) {
            $this->matrix = $this->matrixFor($this->target);
        } else {
            $this->userId = null;
        }
    }

    // ---------- Utilisateur ciblé ----------

    #[Computed]
    public function target(): ?User
    {
        // User n'a pas de filtre automatique : on restreint explicitement à l'entreprise.
        return $this->userId
            ? User::where('company_id', auth()->user()->company_id)->with('employee:id,matricule,first_name,last_name')->find($this->userId)
            : null;
    }

    #[Computed]
    public function userResults()
    {
        $term = trim($this->userSearch);

        return User::where('company_id', auth()->user()->company_id)
            ->where('id', '!=', auth()->id())
            ->with('employee:id,matricule,first_name,last_name,department_id')
            ->when($this->departmentId, fn ($q) => $q->whereHas('employee',
                fn ($e) => $e->where('department_id', $this->departmentId)))
            ->when(strlen($term) >= 2, fn ($q) => $q->where(fn ($w) => $w
                ->where('name', 'like', '%'.$term.'%')
                ->orWhere('email', 'like', $term.'%')
                ->orWhereHas('employee', fn ($e) => $e->where('matricule', 'like', $term.'%'))))
            ->orderBy('name')->limit(100)->get();
    }

    public function updatedPickUserId($value): void
    {
        if ($value) {
            $this->selectUser((int) $value);
            $this->pickUserId = null;
        }
    }

    #[Computed]
    public function locked(): ?string
    {
        $target = $this->target;

        if (! $target) {
            return null;
        }
        if ($target->id === auth()->id()) {
            return 'Vous ne pouvez pas modifier vos propres droits.';
        }
        if ($target->is_company_owner) {
            return 'Le propriétaire de l’entreprise a tous les droits : ils ne se modifient pas ici.';
        }
        if (! auth()->user()->canDo('update', 'permissions')) {
            return 'Vous pouvez consulter les droits, mais pas les modifier.';
        }

        return null;
    }

    public function selectUser(int $id): void
    {
        $this->userId = $id;
        $this->userSearch = '';
        unset($this->target, $this->locked);

        abort_unless($this->target, 404);
        $this->matrix = $this->matrixFor($this->target);
    }

    public function clearUser(): void
    {
        $this->userId = null;
        $this->matrix = [];
        unset($this->target, $this->locked);
    }

    // ---------- Matrice ----------

    private function resources(): array
    {
        return config('permissions.resources');
    }

    private function scopeChoices(string $resource): array
    {
        return in_array($resource, config('permission_meta.employee_scoped'), true) ? ['own', 'team', 'all'] : ['all'];
    }

    private function blankRow(string $resource): array
    {
        return ['create' => false, 'read' => false, 'update' => false, 'delete' => false, 'scope' => $this->scopeChoices($resource)[0]];
    }

    /** État actuel en base, complété avec une ligne vide pour chaque ressource. */
    private function matrixFor(User $user): array
    {
        $existing = Permission::where('user_id', $user->id)->get()->keyBy('resource');
        $matrix = [];

        foreach (array_keys($this->resources()) as $resource) {
            $row = $this->blankRow($resource);

            if ($p = $existing->get($resource)) {
                $row = [
                    'create' => (bool) $p->can_create, 'read' => (bool) $p->can_read,
                    'update' => (bool) $p->can_update, 'delete' => (bool) $p->can_delete,
                    'scope' => in_array($p->scope, $this->scopeChoices($resource), true) ? $p->scope : $this->scopeChoices($resource)[0],
                ];
            }

            $matrix[$resource] = $row;
        }

        return $matrix;
    }

    public function toggleColumn(string $module, string $action): void
    {
        abort_unless(in_array($action, self::ACTIONS, true), 422);

        $resources = collect($this->resources())->filter(fn ($m) => $m['module'] === $module)->keys();
        $allOn = $resources->every(fn ($r) => $this->matrix[$r][$action] ?? false);

        foreach ($resources as $resource) {
            $this->matrix[$resource][$action] = ! $allOn;
            $this->enforce($resource, $action);
        }
    }

    public function applyTemplate(): void
    {
        $template = config("permission_meta.templates.{$this->template}");

        if (! $template) {
            return;
        }

        foreach (array_keys($this->resources()) as $resource) {
            $this->matrix[$resource] = $this->blankRow($resource);
        }

        foreach ($template['permissions'] as $resource => [$letters, $scope]) {
            if (! isset($this->matrix[$resource])) {
                continue; // ressource inconnue de config/permissions.php
            }

            $this->matrix[$resource] = [
                'create' => str_contains($letters, 'c'), 'read' => str_contains($letters, 'r'),
                'update' => str_contains($letters, 'u'), 'delete' => str_contains($letters, 'd'),
                'scope' => in_array($scope, $this->scopeChoices($resource), true) ? $scope : $this->scopeChoices($resource)[0],
            ];
        }

        $this->dispatch('notify', type: 'success', message: 'Modèle appliqué : vérifiez, puis enregistrez.');
    }

    // ---------- Enregistrement ----------

    public function save(): void
    {
        $me = auth()->user();
        abort_unless($me->canDo('update', 'permissions'), 403);

        $target = $this->target;
        abort_unless($target, 404);
        abort_if($this->locked, 403);

        $before = $this->matrixFor($target);
        $new = $this->normalized();

        if ($errors = $this->grantErrors($me, $before, $new)) {
            $this->addError('matrix', implode(' ', array_slice($errors, 0, 4)));

            return;
        }

        DB::transaction(function () use ($target, $new, $before) {
            foreach ($new as $resource => $row) {
                if (! ($row['create'] || $row['read'] || $row['update'] || $row['delete'])) {
                    Permission::where('user_id', $target->id)->where('resource', $resource)->delete();

                    continue;
                }

                $perm = Permission::where('user_id', $target->id)->where('resource', $resource)->first() ?? new Permission();
        $perm->forceFill([
            'user_id' => $target->id, 'resource' => $resource,
            'can_create' => $row['create'], 'can_read' => $row['read'],
            'can_update' => $row['update'], 'can_delete' => $row['delete'],
            'scope' => $row['scope'],
        ])->save();
            }

            $changed = array_keys(array_filter($new, fn ($row, $res) => $row !== $before[$res], ARRAY_FILTER_USE_BOTH));

            AuditLogger::log('permissions_updated', 'permissions', $target->id,
                array_intersect_key($before, array_flip($changed)), array_intersect_key($new, array_flip($changed)));
        });

        $this->matrix = $this->matrixFor($target);
        $this->resetErrorBag('matrix');
        $this->dispatch('notify', type: 'success', message: 'Droits enregistrés.');
    }

    private function normalized(): array
{
    $out = [];

    foreach (array_keys($this->resources()) as $resource) {
        $row = $this->matrix[$resource] ?? $this->blankRow($resource);
        $choices = $this->scopeChoices($resource);

        // Règle serveur : la lecture (read) est obligatoire dès que créer, modifier ou supprimer est actif.
        $read = (bool) ($row['read'] ?? false) 
            || (bool) ($row['create'] ?? false) 
            || (bool) ($row['update'] ?? false) 
            || (bool) ($row['delete'] ?? false);

        $out[$resource] = [
            'create' => (bool) ($row['create'] ?? false),
            'read'   => $read,
            'update' => (bool) ($row['update'] ?? false),
            'delete' => (bool) ($row['delete'] ?? false),
            'scope'  => in_array($row['scope'] ?? '', $choices, true) ? $row['scope'] : $choices[0],
        ];
    }

    return $out;
}

    /**
     * On ne peut accorder que ce qu'on possède soi-même (droit ET portée), sauf le propriétaire.
     * Seuls les droits NOUVEAUX ou ÉLARGIS sont contrôlés : retirer un droit est toujours permis.
     */
    private function grantErrors(User $me, array $before, array $new): array
    {
        if ($me->is_company_owner) {
            return [];
        }

        $errors = [];
        $labels = ['create' => 'Créer', 'read' => 'Lire', 'update' => 'Modifier', 'delete' => 'Supprimer'];

        foreach ($new as $resource => $row) {
            foreach (self::ACTIONS as $action) {
                if (! $row[$action]) {
                    continue;
                }

                $wasGranted = $before[$resource][$action];
                $widened = self::RANK[$row['scope']] > self::RANK[$before[$resource]['scope']];

                if ($wasGranted && ! $widened) {
                    continue; // déjà accordé à l'identique : pas une nouvelle attribution
                }

                $iHaveIt = $me->canDo($action, $resource)
                    && self::RANK[$me->permissionScope($resource) ?? 'own'] >= self::RANK[$row['scope']];

                if (! $iHaveIt) {
                    $errors[] = "Vous ne pouvez pas accorder « {$labels[$action]} » sur « {$this->resources()[$resource]['label']} » (portée {$row['scope']}) : vous ne le possédez pas.";
                }
            }
        }

        return $errors;
    }

    public function render()
    {
        $grouped = collect($this->resources())->groupBy('module', true);   // true = conserver les clés
        return view('livewire.access.access-matrix', [
            'modules' => $grouped,
            'templates' => config('permission_meta.templates'),
            'scopeLabels' => config('permissions.scopes'),
            'employeeScoped' => config('permission_meta.employee_scoped'),
        ]);
    }

    #[Computed]
    public function departments()
    {
        return Department::orderBy('name')->get(['id', 'name']); // Filtré par entreprise via le trait
    }

    // --- Dépendances et actions de masse ---
public function updated($name, $value = null): void
{
    if (! is_string($name) || ! str_starts_with($name, 'matrix.')) {
        return;
    }

    [, $resource, $field] = array_pad(explode('.', $name), 3, null);

    if ($resource !== null && isset($this->matrix[$resource]) && in_array($field, self::ACTIONS, true)) {
        $this->enforce($resource, $field);
    }
}
private function enforce(string $resource, string $changed): void
{
    $row = $this->matrix[$resource];

    if ($changed === 'read' && ! $row['read']) {
        $row['create'] = $row['update'] = $row['delete'] = false;
    } elseif ($changed !== 'read' && $row[$changed]) {
        $row['read'] = true;
    }

    $this->matrix[$resource] = $row;
}

public function toggleRow(string $resource): void
{
    abort_unless(isset($this->matrix[$resource]), 422);

    $on = ! collect(self::ACTIONS)->every(fn ($a) => $this->matrix[$resource][$a]);

    foreach (self::ACTIONS as $a) {
        $this->matrix[$resource][$a] = $on;
    }
}


public function resetMatrix(): void
{
    foreach (array_keys($this->resources()) as $resource) {
        $this->matrix[$resource] = $this->blankRow($resource);
    }

    $this->dispatch('notify', type: 'success', message: 'Tout est décoché : rien n’est enregistré avant « Enregistrer ».');
}



}
