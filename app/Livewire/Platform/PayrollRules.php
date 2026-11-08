<?php

namespace App\Livewire\Platform;

use App\Livewire\Payroll\Concerns\PayrollGuards;
use App\Models\PayrollRule;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Livewire\Component;

/**
 * Espace plateforme : règles légales de paie (taux, barème ITS) avec versions datées.
 * Une règle validée n'est jamais modifiée : on crée une nouvelle version avec une nouvelle date d'effet.
 */
class PayrollRules extends Component
{
    use PayrollGuards;

    /** null | new_code | edit | new_version */
    public ?string $formMode = null;
    public ?int $ruleId = null;

    public string $code = '';
    public string $label = '';
    public string $party = 'employee';
    public string $kind = 'rate';
    public string $rate = '';
    public string $base = 'cotisable';
    public bool $deductible_from_its = false;
    public string $ceiling_monthly = '';
    public string $effective_from = '';
    public string $source = '';
    public string $notes = '';
    public string $sort = '0';
    public string $paramsJson = '';

    public string $flash = '';

    public function mount(): void
    {
        abort_unless($this->canManage(), 403);
    }

    /** Droit « plans.manage » de la plateforme (même contrôle que le menu et que les autres pages). */
    protected function canManage(): bool
    {
        $user = auth()->user();

        return $user && $user->is_platform_admin && $user->platformCan('plans.manage');
    }

    // ───── Ouverture du formulaire ─────

    public function newCode(): void
    {
        abort_unless($this->canManage(), 403);
        $this->resetForm();
        $this->formMode = 'new_code';
        $this->effective_from = now()->startOfYear()->toDateString();
    }

    public function editRule(int $id): void
    {
        abort_unless($this->canManage(), 403);
        $rule = PayrollRule::findOrFail($id);

        if ($rule->status === 'validated') {
            $this->flash = 'Une règle validée ne se modifie pas : créez une nouvelle version datée.';

            return;
        }

        $this->fillFrom($rule);
        $this->formMode = 'edit';
        $this->ruleId = $rule->id;
        $this->effective_from = $rule->effective_from->format('Y-m-d');
    }

    public function newVersion(int $id): void
    {
        abort_unless($this->canManage(), 403);
        $rule = PayrollRule::findOrFail($id);
        $this->fillFrom($rule);
        $this->formMode = 'new_version';
        $this->ruleId = $rule->id;
        $this->effective_from = '';
        $this->notes = '';
    }

    public function cancel(): void
    {
        $this->resetForm();
    }

    protected function resetForm(): void
    {
        $this->resetErrorBag();
        $this->formMode = null;
        $this->ruleId = null;
        $this->code = '';
        $this->label = '';
        $this->party = 'employee';
        $this->kind = 'rate';
        $this->rate = '';
        $this->base = 'cotisable';
        $this->deductible_from_its = false;
        $this->ceiling_monthly = '';
        $this->effective_from = '';
        $this->source = '';
        $this->notes = '';
        $this->sort = '0';
        $this->paramsJson = '';
    }

    protected function fillFrom(PayrollRule $rule): void
    {
        $this->resetErrorBag();
        $this->code = $rule->code;
        $this->label = $rule->label;
        $this->party = $rule->party;
        $this->kind = $rule->kind;
        $this->rate = $rule->rate !== null ? rtrim(rtrim(number_format((float) $rule->rate, 3, '.', ''), '0'), '.') : '';
        $this->base = $rule->base;
        $this->deductible_from_its = (bool) $rule->deductible_from_its;
        $this->ceiling_monthly = $rule->ceiling_monthly ? (string) $rule->ceiling_monthly : '';
        $this->source = (string) $rule->source;
        $this->notes = (string) $rule->notes;
        $this->sort = (string) $rule->sort;
        $this->paramsJson = $rule->params ? json_encode($rule->params, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) : '';
    }

    // ───── Enregistrement ─────

    public function save(): void
    {
        abort_unless($this->canManage(), 403);

        $this->rate = str_replace(',', '.', trim($this->rate));
        $this->code = strtoupper(trim($this->code));

        $this->validate([
            'code' => ['required', 'regex:/^[A-Z0-9_]{2,30}$/'],
            'label' => 'required|string|max:120',
            'party' => 'required|in:employee,employer',
            'kind' => 'required|in:rate,its',
            'rate' => 'nullable|numeric|between:0,100',
            'base' => 'required|in:cotisable,brut,imposable',
            'ceiling_monthly' => 'nullable|string|max:15',
            'effective_from' => 'required|date',
            'source' => 'nullable|string|max:255',
            'notes' => 'nullable|string|max:2000',
            'sort' => 'required|integer|between:0,999',
        ], [
            'code.regex' => 'Le code doit contenir 2 à 30 caractères : lettres majuscules, chiffres ou « _ ».',
        ], [
            'label' => 'libellé', 'effective_from' => "date d'effet", 'ceiling_monthly' => 'plafond mensuel',
        ]);

        $params = null;

        if ($this->kind === 'rate') {
            if ($this->rate === '') {
                $this->addError('rate', 'Indiquez le taux en %.');

                return;
            }
        } else {
            $params = json_decode($this->paramsJson, true);

            if (! is_array($params) || empty($params['brackets']) || ! is_array($params['brackets'])) {
                $this->addError('paramsJson', 'Paramètres invalides : un JSON avec au moins la liste « brackets » (tranches [de, à, taux]) est attendu.');

                return;
            }

            foreach ($params['brackets'] as $b) {
                if (! is_array($b) || count($b) < 3 || ! is_numeric($b[0]) || ! is_numeric($b[2])) {
                    $this->addError('paramsJson', 'Chaque tranche doit être de la forme [début, fin ou null, taux].');

                    return;
                }
            }
        }

        $from = Carbon::parse($this->effective_from)->startOfDay();
        $data = [
            'label' => trim($this->label),
            'party' => $this->party,
            'kind' => $this->kind,
            'rate' => $this->kind === 'rate' ? (float) $this->rate : null,
            'params' => $params,
            'base' => $this->base,
            'deductible_from_its' => $this->deductible_from_its,
            'ceiling_monthly' => $this->digits($this->ceiling_monthly) ?: null,
            'effective_from' => $from->toDateString(),
            'source' => trim($this->source) ?: null,
            'notes' => trim($this->notes) ?: null,
            'sort' => (int) $this->sort,
        ];

        if ($this->formMode === 'edit') {
            $rule = PayrollRule::findOrFail((int) $this->ruleId);

            if ($rule->status === 'validated') {
                $this->flash = 'Une règle validée ne se modifie pas : créez une nouvelle version datée.';
                $this->resetForm();

                return;
            }

            if (PayrollRule::where('country', $rule->country)->where('code', $rule->code)->whereDate('effective_from', $from->toDateString())->where('id', '!=', $rule->id)->exists()) {
                $this->addError('effective_from', 'Une autre version de cette règle existe déjà à cette date.');

                return;
            }

            $old = $rule->only(array_keys($data));
            $rule->update($data);
            $this->audit('update', 'payroll_rules', $rule->id, $old, $data);
            $this->flash = 'Règle ' . $rule->code . ' mise à jour (toujours à valider).';
        } elseif ($this->formMode === 'new_version') {
            $base = PayrollRule::findOrFail((int) $this->ruleId);

            $latest = PayrollRule::where('country', $base->country)->where('code', $base->code)->max('effective_from');

            if ($latest && $from->lte(Carbon::parse($latest))) {
                $this->addError('effective_from', 'La nouvelle version doit commencer après la dernière version existante (' . Carbon::parse($latest)->format('d/m/Y') . ').');

                return;
            }

            $rule = DB::transaction(function () use ($base, $data, $from) {
                // La version précédente se termine la veille ; elle n'est ni supprimée ni modifiée autrement.
                PayrollRule::where('country', $base->country)->where('code', $base->code)
                    ->where(fn ($q) => $q->whereNull('effective_until')->orWhereDate('effective_until', '>=', $from->toDateString()))
                    ->update(['effective_until' => $from->copy()->subDay()->toDateString()]);

                return PayrollRule::create($data + ['country' => $base->country, 'code' => $base->code, 'status' => 'to_validate']);
            });

            $this->audit('create', 'payroll_rules', $rule->id, null, $data + ['code' => $rule->code]);
            $this->flash = 'Nouvelle version de ' . $rule->code . ' créée à partir du ' . $from->format('d/m/Y') . ' : à valider par un comptable.';
        } else {
            $this->validate(['code' => [Rule::unique('payroll_rules', 'code')->where('country', 'ML')]], ['code.unique' => 'Ce code existe déjà : utilisez « Nouvelle version » depuis la règle existante.']);

            $rule = PayrollRule::create($data + ['country' => 'ML', 'code' => $this->code, 'status' => 'to_validate']);
            $this->audit('create', 'payroll_rules', $rule->id, null, $data + ['code' => $rule->code]);
            $this->flash = 'Règle ' . $rule->code . ' créée : à valider par un comptable.';
        }

        $this->resetForm();
    }

    // ───── Validation et suppression ─────

    public function validateRule(int $id): void
    {
        abort_unless($this->canManage(), 403);
        $rule = PayrollRule::findOrFail($id);

        if ($rule->status === 'validated') {
            return;
        }

        $rule->update(['status' => 'validated', 'validated_by' => auth()->id(), 'validated_at' => now()]);
        $this->audit('validate', 'payroll_rules', $rule->id, ['status' => 'to_validate'], ['status' => 'validated', 'code' => $rule->code]);
        $this->flash = 'Règle ' . $rule->code . ' validée.';
    }

    public function deleteRule(int $id): void
    {
        abort_unless($this->canManage(), 403);
        $rule = PayrollRule::findOrFail($id);

        if ($rule->status === 'validated') {
            $this->flash = 'Une règle validée ne se supprime pas.';

            return;
        }

        // Si on supprime la dernière version, la précédente redevient ouverte.
        DB::transaction(function () use ($rule) {
            $previous = PayrollRule::where('country', $rule->country)->where('code', $rule->code)
                ->whereDate('effective_from', '<', $rule->effective_from->toDateString())->orderByDesc('effective_from')->first();

            $hasLater = PayrollRule::where('country', $rule->country)->where('code', $rule->code)
                ->whereDate('effective_from', '>', $rule->effective_from->toDateString())->exists();

            if ($previous && ! $hasLater && $previous->effective_until && $previous->effective_until->lt($rule->effective_from)) {
                $previous->update(['effective_until' => $rule->effective_until]);
            }

            $this->audit('delete', 'payroll_rules', $rule->id, ['code' => $rule->code, 'effective_from' => $rule->effective_from->toDateString()], null);
            $rule->delete();
        });

        $this->flash = 'Version supprimée.';
    }

    public function render()
    {
        $rules = PayrollRule::with('validator')->where('country', 'ML')
            ->orderBy('sort')->orderBy('code')->orderByDesc('effective_from')->get()
            ->groupBy('code');

        return view('livewire.platform.payroll-rules', [
            'groups' => $rules,
            'pending' => $rules->flatten()->where('status', 'to_validate')->count(),
        ])->layout($this->resolveLayout('platform'), ['title' => 'Règles de paie']);
    }
}
