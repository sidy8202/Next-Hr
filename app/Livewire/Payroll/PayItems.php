<?php

namespace App\Livewire\Payroll;

use App\Livewire\Payroll\Concerns\PayrollGuards;
use App\Models\EmployeePayItem;
use App\Models\PayItem;
use App\Models\PayItemScaleRow;
use App\Models\PayProfileItem;
use App\Services\Payroll\PayrollUiMode;
use Livewire\Component;

class PayItems extends Component
{
    use PayrollGuards;

    public string $filterType = '';
    public bool $showForm = false;
    public ?int $editingId = null;

    public string $code = '';
    public string $label = '';
    public string $type = 'gain';
    public bool $subject_to_social = true;
    public bool $taxable = true;
    public bool $is_compulsory = false;
    public string $calc_type = 'fixed';
    public string $default_amount = '';
    public string $default_rate = '';
    public $ref_item_id = '';
    public string $floor_amount = '';
    public string $cap_amount = '';
    public bool $show_on_payslip = true;
    public string $sort = '100';
    public bool $is_active = true;
    public string $notes = '';

    public string $scaleYears = '';
    public string $scaleRate = '';

    public string $flash = '';

    public function mount(): void
    {
        $this->allow('read', 'pay_items');
    }

    // ───── Formulaire ─────

    public function newItem(): void
    {
        $this->allow('update', 'pay_items');
        $this->resetErrorBag();
        $this->editingId = null;
        $this->code = '';
        $this->label = '';
        $this->type = 'gain';
        $this->subject_to_social = true;
        $this->taxable = true;
        $this->is_compulsory = false;
        $this->calc_type = 'fixed';
        $this->default_amount = '';
        $this->default_rate = '';
        $this->ref_item_id = '';
        $this->floor_amount = '';
        $this->cap_amount = '';
        $this->show_on_payslip = true;
        $this->sort = '100';
        $this->is_active = true;
        $this->notes = '';
        $this->scaleYears = '';
        $this->scaleRate = '';
        $this->showForm = true;
    }

    public function editItem(int $id): void
    {
        $this->allow('update', 'pay_items');
        $this->resetErrorBag();
        $item = PayItem::findOrFail($id);

        $this->editingId = $item->id;
        $this->code = $item->code;
        $this->label = $item->label;
        $this->type = $item->type;
        $this->subject_to_social = $item->subject_to_social;
        $this->taxable = $item->taxable;
        $this->is_compulsory = $item->is_compulsory;
        $this->calc_type = $item->calc_type;
        $this->default_amount = $item->default_amount !== null ? (string) $item->default_amount : '';
        $this->default_rate = $item->default_rate !== null ? rtrim(rtrim(number_format((float) $item->default_rate, 3, '.', ''), '0'), '.') : '';
        $this->ref_item_id = $item->ref_item_id ? (string) $item->ref_item_id : '';
        $this->floor_amount = $item->floor_amount !== null ? (string) $item->floor_amount : '';
        $this->cap_amount = $item->cap_amount !== null ? (string) $item->cap_amount : '';
        $this->show_on_payslip = $item->show_on_payslip;
        $this->sort = (string) $item->sort;
        $this->is_active = $item->is_active;
        $this->notes = (string) $item->notes;
        $this->scaleYears = '';
        $this->scaleRate = '';
        $this->showForm = true;
    }

    public function cancel(): void
    {
        $this->showForm = false;
        $this->resetErrorBag();
    }

    public function save(): void
    {
        $this->allow('update', 'pay_items');

        $existing = $this->editingId ? PayItem::findOrFail($this->editingId) : null;
        $locked = $existing?->is_system;

        // Une rubrique système garde son code, son type et son calcul : le moteur en dépend.
        if ($locked) {
            $this->code = $existing->code;
            $this->type = $existing->type;
            $this->calc_type = $existing->calc_type;
        }

        $this->code = strtoupper(trim($this->code));
        $this->default_rate = str_replace(',', '.', trim($this->default_rate));

        $this->validate([
            'code' => ['required', 'regex:/^[A-Z0-9]{3,10}$/'],
            'label' => 'required|string|max:120',
            'type' => 'required|in:' . implode(',', array_keys(PayItem::TYPES)),
            'calc_type' => 'required|in:' . implode(',', array_keys(PayItem::CALC_TYPES)),
            'default_rate' => 'nullable|numeric|between:0,100',
            'sort' => 'required|integer|between:0,999',
            'notes' => 'nullable|string|max:1000',
        ], [
            'code.regex' => 'Le code doit contenir 3 à 10 caractères : lettres majuscules ou chiffres.',
        ], ['label' => 'libellé', 'default_rate' => 'taux', 'sort' => 'ordre']);

        if (PayItem::where('code', $this->code)->when($existing, fn ($q) => $q->where('id', '!=', $existing->id))->exists()) {
            $this->addError('code', 'Ce code existe déjà dans votre catalogue.');

            return;
        }

        if (! $locked && $this->calc_type === 'system') {
            $this->addError('calc_type', 'Ce type de calcul est réservé aux rubriques système.');

            return;
        }

        $refId = null;

        if ($this->calc_type === 'percent_item') {
            $refId = $this->ref_item_id !== '' ? (int) $this->ref_item_id : null;

            if (! $refId || ($existing && $refId === $existing->id) || ! PayItem::whereKey($refId)->exists()) {
                $this->addError('ref_item_id', "Choisissez la rubrique de référence (différente de celle-ci).");

                return;
            }
        }

        $floor = $this->floor_amount !== '' ? $this->digits($this->floor_amount) : null;
        $cap = $this->cap_amount !== '' ? $this->digits($this->cap_amount) : null;

        if ($floor !== null && $cap !== null && $floor > $cap) {
            $this->addError('floor_amount', 'Le plancher ne peut pas dépasser le plafond.');

            return;
        }

        $data = [
            'code' => $this->code,
            'label' => trim($this->label),
            'type' => $this->type,
            'subject_to_social' => $this->subject_to_social,
            'taxable' => $this->taxable,
            'is_compulsory' => $this->is_compulsory,
            'calc_type' => $this->calc_type,
            'default_amount' => PayItem::usesAmount($this->calc_type) && $this->default_amount !== '' ? $this->digits($this->default_amount) : null,
            'default_rate' => PayItem::usesRate($this->calc_type) && $this->default_rate !== '' ? (float) $this->default_rate : null,
            'ref_item_id' => $refId,
            'floor_amount' => $floor,
            'cap_amount' => $cap,
            'show_on_payslip' => $this->show_on_payslip,
            'sort' => (int) $this->sort,
            'is_active' => $this->is_active,
            'notes' => trim($this->notes) ?: null,
        ];

        if ($existing) {
            $old = $existing->only(array_keys($data));
            $existing->update($data);
            $this->audit('update', 'pay_items', $existing->id, $old, $data);
            $this->flash = 'Rubrique ' . $existing->code . ' mise à jour.';
            $this->editingId = $existing->id;

            // On reste sur la rubrique si c'est un barème, pour saisir les paliers.
            if ($existing->calc_type === 'scale') {
                return;
            }
        } else {
            $item = new PayItem($data + ['is_system' => false]);
            $item->company_id = $this->companyId();
            $item->save();
            $this->audit('create', 'pay_items', $item->id, null, $data);
            $this->flash = 'Rubrique ' . $item->code . ' créée.';

            if ($item->calc_type === 'scale') {
                $this->editingId = $item->id;
                $this->flash .= ' Ajoutez maintenant les paliers du barème.';

                return;
            }
        }

        $this->showForm = false;
    }

    public function toggleActive(int $id): void
    {
        $this->allow('update', 'pay_items');
        $item = PayItem::findOrFail($id);
        $item->update(['is_active' => ! $item->is_active]);
        $this->audit('update', 'pay_items', $item->id, ['is_active' => ! $item->is_active], ['is_active' => $item->is_active]);
    }

    public function deleteItem(int $id): void
    {
        $this->allow('delete', 'pay_items');
        $item = PayItem::findOrFail($id);

        if ($item->is_system) {
            $this->flash = 'Une rubrique système ne se supprime pas (vous pouvez la désactiver).';

            return;
        }

        $used = PayProfileItem::where('pay_item_id', $item->id)->exists()
            || EmployeePayItem::where('pay_item_id', $item->id)->exists()
            || PayItem::where('ref_item_id', $item->id)->exists();

        if ($used) {
            $this->flash = 'Cette rubrique est utilisée (modèle, prime attribuée ou autre rubrique) : désactivez-la plutôt que de la supprimer.';

            return;
        }

        $this->audit('delete', 'pay_items', $item->id, ['code' => $item->code, 'label' => $item->label], null);
        $item->delete();
        $this->flash = 'Rubrique supprimée.';
    }

    // ───── Barème d'une rubrique ─────

    public function addScaleRow(): void
    {
        $this->allow('update', 'pay_items');
        $item = PayItem::findOrFail((int) $this->editingId);

        $this->scaleRate = str_replace(',', '.', trim($this->scaleRate));
        $this->validate([
            'scaleYears' => 'required|integer|between:0,60',
            'scaleRate' => 'required|numeric|between:0,100',
        ], [], ['scaleYears' => "nombre d'années", 'scaleRate' => 'taux']);

        if (PayItemScaleRow::where('pay_item_id', $item->id)->where('from_years', (int) $this->scaleYears)->exists()) {
            $this->addError('scaleYears', 'Un palier existe déjà pour ce nombre d\'années.');

            return;
        }

        PayItemScaleRow::create(['pay_item_id' => $item->id, 'from_years' => (int) $this->scaleYears, 'rate' => (float) $this->scaleRate]);
        $this->audit('update', 'pay_items', $item->id, null, ['scale_from_years' => (int) $this->scaleYears, 'rate' => (float) $this->scaleRate]);
        $this->scaleYears = '';
        $this->scaleRate = '';
    }

    public function deleteScaleRow(int $rowId): void
    {
        $this->allow('update', 'pay_items');
        $row = PayItemScaleRow::whereHas('item')->findOrFail($rowId);
        $row->delete();
    }

    // ───── Rubriques de départ ─────

    public function loadStarter(): void
    {
        $this->allow('update', 'pay_items');

        $created = 0;

        foreach ($this->starterItems() as $row) {
            if (PayItem::where('code', $row['code'])->exists()) {
                continue;
            }

            $item = new PayItem($row + ['default_amount' => null, 'default_rate' => null, 'show_on_payslip' => true, 'is_active' => true]);
            $item->company_id = $this->companyId();
            $item->save();
            $created++;
        }

        $this->audit('create', 'pay_items', null, null, ['starter_items' => $created]);
        $this->flash = $created > 0
            ? $created . ' rubrique(s) de départ ajoutée(s). Aucun montant ni taux n\'est prérempli : renseignez-les selon votre convention et vos décisions.'
            : 'Les rubriques de départ existent déjà.';
    }

    /** @return array<int, array<string, mixed>> */
    protected function starterItems(): array
    {
        $sys = fn (string $code, string $label, string $type, string $key, int $sort, bool $net = false) => [
            'code' => $code, 'label' => $label, 'type' => $type, 'system_key' => $key, 'is_system' => true,
            'subject_to_social' => ! $net, 'taxable' => ! $net, 'is_compulsory' => true, 'calc_type' => 'system', 'sort' => $sort,
            'notes' => 'Rubrique fournie par le moteur de paie.',
        ];

        $std = fn (string $code, string $label, string $type, string $calc, int $sort, bool $social, bool $taxable, bool $compulsory, string $notes) => [
            'code' => $code, 'label' => $label, 'type' => $type, 'system_key' => null, 'is_system' => false,
            'subject_to_social' => $social, 'taxable' => $taxable, 'is_compulsory' => $compulsory, 'calc_type' => $calc, 'sort' => $sort,
            'notes' => $notes,
        ];

        $toConfirm = 'Qualification de départ : à confirmer par le comptable.';

        return [
            $sys('100001', 'Salaire de base', 'gain', 'base_salary', 10),
            $sys('100002', 'Sursalaire', 'gain', 'supplement', 20),
            $sys('100010', 'Heures supplémentaires', 'gain', 'overtime', 30),
            $sys('300001', 'Absence non rémunérée', 'gross_deduction', 'absence_deduction', 300),
            $sys('300002', 'Mise à pied', 'gross_deduction', 'suspension', 310),
            $sys('390001', 'Remboursement de prêt', 'deduction_individual', 'loan', 900, true),
            $sys('390002', "Retenue d'avance sur salaire", 'deduction_individual', 'advance', 910, true),

            $std('100200', 'Indemnité spéciale', 'gain', 'fixed', 100, true, false, true, 'Soumise aux cotisations et non imposable d\'après le bulletin de février 2026 ; montant à renseigner. ' . $toConfirm),
            $std('100300', "Prime d'ancienneté", 'gain', 'scale', 110, true, true, true, 'Barème à saisir selon la convention applicable. ' . $toConfirm),
            $std('100400', 'Indemnité de transport', 'gain', 'fixed', 120, true, true, false, $toConfirm),
            $std('100500', 'Indemnité de fonction', 'gain', 'fixed', 130, true, true, false, $toConfirm),
            $std('100600', 'Téléphone', 'benefit_in_kind', 'fixed', 140, true, true, false, 'Ajouté au brut puis retiré du net, comme sur le bulletin 2 de février 2026. ' . $toConfirm),
            $std('300100', 'Assurance complémentaire', 'deduction_internal', 'fixed', 500, false, false, false, 'Retenue interne décidée par l\'entreprise.'),
        ];
    }

    public function render()
    {
        $items = PayItem::with('scaleRows', 'refItem')
            ->when($this->filterType !== '', fn ($q) => $q->where('type', $this->filterType))
            ->orderBy('sort')->orderBy('code')->get();

        $desc = [];

        foreach ($items as $i) {
            $desc[$i->id] = $this->describe($i);
        }

        $groups = [];

        foreach (PayItem::TYPES as $key => $label) {
            $rows = $items->where('type', $key)->values();

            if ($rows->isNotEmpty()) {
                $groups[] = ['label' => $label, 'items' => $rows];
            }
        }

        $editing = $this->editingId ? PayItem::with('scaleRows')->find($this->editingId) : null;

        $simple = PayrollUiMode::isSimple($this->companyId());

        return view('livewire.payroll.pay-items', [
            'groups' => $groups,
            'desc' => $desc,
            'total' => PayItem::count(),
            'refOptions' => PayItem::where('is_active', true)->when($this->editingId, fn ($q) => $q->where('id', '!=', $this->editingId))->orderBy('code')->get(),
            'editing' => $editing,
            'locked' => (bool) $editing?->is_system,
            'types' => PayItem::TYPES,
            'calcTypes' => PayrollUiMode::calcTypes(PayItem::CALC_TYPES, $simple, $this->calc_type),
            'showLimits' => PayrollUiMode::showLimits($simple, $this->floor_amount !== '' || $this->cap_amount !== ''),
            'canEdit' => $this->allowed('update', 'pay_items'),
            'canDelete' => $this->allowed('delete', 'pay_items'),
        ])->layout($this->resolveLayout('company'), ['title' => 'Rubriques de paie']);
    }

    protected function describe(PayItem $i): string
    {
        $fmt = fn ($n) => number_format((int) $n, 0, ',', ' ');
        $rate = fn ($r) => rtrim(rtrim(number_format((float) $r, 3, ',', ''), '0'), ',') . ' %';

        $text = match ($i->calc_type) {
            'system' => 'Fourni par le moteur',
            'fixed' => $i->default_amount !== null ? 'Montant fixe : ' . $fmt($i->default_amount) : 'Montant fixe (à renseigner)',
            'qty_rate' => $i->default_amount !== null ? 'Quantité × ' . $fmt($i->default_amount) : 'Quantité × taux (à renseigner)',
            'percent_base' => ($i->default_rate !== null ? $rate($i->default_rate) : '% à renseigner') . ' du salaire de base',
            'percent_gross' => ($i->default_rate !== null ? $rate($i->default_rate) : '% à renseigner') . ' du brut',
            'percent_item' => ($i->default_rate !== null ? $rate($i->default_rate) : '% à renseigner') . ' de « ' . ($i->refItem?->label ?? '?') . ' »',
            'scale' => 'Barème : ' . $i->scaleRows->count() . ' palier(s)',
            default => '',
        };

        if ($i->floor_amount !== null) {
            $text .= ' · plancher ' . $fmt($i->floor_amount);
        }

        if ($i->cap_amount !== null) {
            $text .= ' · plafond ' . $fmt($i->cap_amount);
        }

        return $text;
    }
}
