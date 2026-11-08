<?php

namespace App\Livewire\Payroll;

use App\Livewire\Payroll\Concerns\PayrollGuards;
use App\Models\EmployeePayProfile;
use App\Models\PayItem;
use App\Models\PayProfile;
use App\Models\PayProfileItem;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

class PayProfiles extends Component
{
    use PayrollGuards;

    public ?int $profileId = null;
    public bool $showForm = false;
    public ?int $editingId = null;
    public string $name = '';
    public string $convention = '';
    public string $description = '';
    public bool $is_active = true;

    public $addItemId = '';
    public string $addAmount = '';
    public string $addRate = '';

    /** @var array<int, string> valeurs imposées par ligne du modèle (vide = valeur par défaut de la rubrique) */
    public array $rowAmounts = [];
    /** @var array<int, string> */
    public array $rowRates = [];

    public string $flash = '';

    public function mount(): void
    {
        $this->allow('read', 'pay_profiles');

        $first = PayProfile::orderByDesc('is_active')->orderBy('name')->first();

        if ($first) {
            $this->selectProfile($first->id);
        }
    }

    public function selectProfile(int $id): void
    {
        $profile = PayProfile::findOrFail($id);
        $this->profileId = $profile->id;
        $this->showForm = false;
        $this->addItemId = '';
        $this->addAmount = '';
        $this->addRate = '';
        $this->loadRows();
    }

    protected function loadRows(): void
    {
        $this->rowAmounts = [];
        $this->rowRates = [];

        if (! $this->profileId) {
            return;
        }

        foreach (PayProfileItem::where('pay_profile_id', $this->profileId)->get() as $row) {
            $this->rowAmounts[$row->id] = $row->amount !== null ? (string) $row->amount : '';
            $this->rowRates[$row->id] = $row->rate !== null ? rtrim(rtrim(number_format((float) $row->rate, 3, '.', ''), '0'), '.') : '';
        }
    }

    // ───── Modèle ─────

    public function newProfile(): void
    {
        $this->allow('update', 'pay_profiles');
        $this->resetErrorBag();
        $this->editingId = null;
        $this->name = '';
        $this->convention = '';
        $this->description = '';
        $this->is_active = true;
        $this->showForm = true;
    }

    public function editProfile(int $id): void
    {
        $this->allow('update', 'pay_profiles');
        $this->resetErrorBag();
        $p = PayProfile::findOrFail($id);
        $this->editingId = $p->id;
        $this->name = $p->name;
        $this->convention = (string) $p->convention;
        $this->description = (string) $p->description;
        $this->is_active = $p->is_active;
        $this->showForm = true;
    }

    public function cancelForm(): void
    {
        $this->showForm = false;
        $this->resetErrorBag();
    }

    public function saveProfile(): void
    {
        $this->allow('update', 'pay_profiles');

        $this->validate([
            'name' => 'required|string|max:120',
            'convention' => 'nullable|string|max:150',
            'description' => 'nullable|string|max:1000',
        ], [], ['name' => 'nom du modèle']);

        $data = [
            'name' => trim($this->name),
            'convention' => trim($this->convention) ?: null,
            'description' => trim($this->description) ?: null,
            'is_active' => $this->is_active,
        ];

        if ($this->editingId) {
            $p = PayProfile::findOrFail($this->editingId);
            $old = $p->only(array_keys($data));
            $p->update($data);
            $this->audit('update', 'pay_profiles', $p->id, $old, $data);
        } else {
            $p = new PayProfile($data);
            $p->company_id = $this->companyId();
            $p->save();
            $this->audit('create', 'pay_profiles', $p->id, null, $data);
        }

        $this->flash = 'Modèle enregistré.';
        $this->selectProfile($p->id);
    }

    public function duplicateProfile(int $id): void
    {
        $this->allow('update', 'pay_profiles');
        $source = PayProfile::with('items')->findOrFail($id);

        $copy = DB::transaction(function () use ($source) {
            $copy = new PayProfile([
                'name' => mb_substr($source->name . ' (copie)', 0, 120),
                'convention' => $source->convention,
                'description' => $source->description,
                'is_active' => true,
            ]);
            $copy->company_id = $this->companyId();
            $copy->save();

            foreach ($source->items as $row) {
                PayProfileItem::create(['pay_profile_id' => $copy->id, 'pay_item_id' => $row->pay_item_id, 'amount' => $row->amount, 'rate' => $row->rate]);
            }

            return $copy;
        });

        $this->audit('create', 'pay_profiles', $copy->id, null, ['duplicated_from' => $source->id]);
        $this->flash = 'Modèle dupliqué : renommez-le et ajustez ses rubriques.';
        $this->selectProfile($copy->id);
    }

    public function deleteProfile(int $id): void
    {
        $this->allow('delete', 'pay_profiles');
        $p = PayProfile::findOrFail($id);

        if (EmployeePayProfile::where('pay_profile_id', $p->id)->exists()) {
            $this->flash = 'Ce modèle a déjà été affecté à des employés : désactivez-le plutôt que de le supprimer (l\'historique est conservé).';

            return;
        }

        $this->audit('delete', 'pay_profiles', $p->id, ['name' => $p->name], null);
        $p->delete();
        $this->profileId = null;
        $this->rowAmounts = [];
        $this->rowRates = [];
        $this->flash = 'Modèle supprimé.';

        $next = PayProfile::orderByDesc('is_active')->orderBy('name')->first();

        if ($next) {
            $this->selectProfile($next->id);
        }
    }

    // ───── Composition ─────

    public function addItem(): void
    {
        $this->allow('update', 'pay_profiles');
        $profile = PayProfile::findOrFail((int) $this->profileId);

        if ($this->addItemId === '' || $this->addItemId === null) {
            $this->addError('addItemId', 'Choisissez une rubrique.');

            return;
        }

        $item = PayItem::where('is_system', false)->findOrFail((int) $this->addItemId);

        if (PayProfileItem::where('pay_profile_id', $profile->id)->where('pay_item_id', $item->id)->exists()) {
            $this->addError('addItemId', 'Cette rubrique est déjà dans le modèle.');

            return;
        }

        [$amount, $rate, $error] = $this->readValue($item, $this->addAmount, $this->addRate);

        if ($error) {
            $this->addError('addAmount', $error);

            return;
        }

        PayProfileItem::create(['pay_profile_id' => $profile->id, 'pay_item_id' => $item->id, 'amount' => $amount, 'rate' => $rate]);
        $this->audit('update', 'pay_profiles', $profile->id, null, ['item_added' => $item->code]);

        $this->addItemId = '';
        $this->addAmount = '';
        $this->addRate = '';
        $this->resetErrorBag();
        $this->loadRows();
    }

    public function saveRow(int $rowId): void
    {
        $this->allow('update', 'pay_profiles');
        $row = PayProfileItem::whereHas('profile')->with('item')->findOrFail($rowId);

        [$amount, $rate, $error] = $this->readValue($row->item, $this->rowAmounts[$rowId] ?? '', $this->rowRates[$rowId] ?? '');

        if ($error) {
            $this->addError("rowAmounts.$rowId", $error);
            $this->loadRows();

            return;
        }

        $old = ['amount' => $row->amount, 'rate' => $row->rate];
        $row->update(['amount' => $amount, 'rate' => $rate]);
        $this->audit('update', 'pay_profiles', $row->pay_profile_id, $old + ['item' => $row->item->code], ['amount' => $amount, 'rate' => $rate]);
        $this->resetErrorBag();
    }

    public function removeRow(int $rowId): void
    {
        $this->allow('update', 'pay_profiles');
        $row = PayProfileItem::whereHas('profile')->with('item')->findOrFail($rowId);
        $this->audit('update', 'pay_profiles', $row->pay_profile_id, ['item_removed' => $row->item->code], null);
        $row->delete();
        $this->loadRows();
    }

    /** @return array{0: ?int, 1: ?float, 2: ?string} montant, taux, message d'erreur */
    protected function readValue(PayItem $item, string $amountText, string $rateText): array
    {
        $amount = null;
        $rate = null;

        if (PayItem::usesAmount($item->calc_type) && trim($amountText) !== '') {
            $amount = $this->digits($amountText);
        }

        if (PayItem::usesRate($item->calc_type) && trim($rateText) !== '') {
            $text = str_replace(',', '.', trim($rateText));

            if (! is_numeric($text) || (float) $text < 0 || (float) $text > 100) {
                return [null, null, 'Le taux doit être un nombre entre 0 et 100.'];
            }

            $rate = (float) $text;
        }

        return [$amount, $rate, null];
    }

    public function render()
    {
        $profiles = PayProfile::withCount('items')->orderByDesc('is_active')->orderBy('name')->get();

        $assigned = EmployeePayProfile::inForceOn(now()->toDateString())->selectRaw('pay_profile_id, COUNT(*) as n')->groupBy('pay_profile_id')->pluck('n', 'pay_profile_id');

        $selected = $this->profileId ? PayProfile::with('items.item.scaleRows')->find($this->profileId) : null;

        $usedIds = $selected ? $selected->items->pluck('pay_item_id')->all() : [];

        return view('livewire.payroll.pay-profiles', [
            'profiles' => $profiles,
            'assigned' => $assigned,
            'selected' => $selected,
            'available' => PayItem::where('is_system', false)->where('is_active', true)->whereNotIn('id', $usedIds)->orderBy('code')->get(),
            'canEdit' => $this->allowed('update', 'pay_profiles'),
            'canDelete' => $this->allowed('delete', 'pay_profiles'),
        ])->layout($this->resolveLayout('company'), ['title' => 'Modèles de traitement']);
    }
}
