<?php

namespace App\Livewire\Payroll;

use App\Livewire\Payroll\Concerns\PayrollGuards;
use App\Models\PayslipTemplate;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

/** Réglages des modèles de bulletin. Le rendu PDF viendra au lot 14b. */
class PayslipTemplates extends Component
{
    use PayrollGuards;

    public bool $showForm = false;
    public ?int $editingId = null;
    public string $name = '';
    public string $convention = '';
    public bool $show_employer_part = false;
    public bool $show_item_codes = true;
    public bool $group_lines = true;
    public string $footer_text = '';
    public bool $is_default = false;
    public string $flash = '';

    public function mount(): void
    {
        $this->allow('read', 'payroll_settings');
    }

    public function newTemplate(): void
    {
        $this->allow('update', 'payroll_settings');
        $this->resetErrorBag();
        $this->editingId = null;
        $this->name = '';
        $this->convention = '';
        $this->show_employer_part = false;
        $this->show_item_codes = true;
        $this->group_lines = true;
        $this->footer_text = '';
        $this->is_default = ! PayslipTemplate::exists();
        $this->showForm = true;
    }

    public function editTemplate(int $id): void
    {
        $this->allow('update', 'payroll_settings');
        $this->resetErrorBag();
        $t = PayslipTemplate::findOrFail($id);
        $this->editingId = $t->id;
        $this->name = $t->name;
        $this->convention = (string) $t->convention;
        $this->show_employer_part = $t->show_employer_part;
        $this->show_item_codes = $t->show_item_codes;
        $this->group_lines = $t->group_lines;
        $this->footer_text = (string) $t->footer_text;
        $this->is_default = $t->is_default;
        $this->showForm = true;
    }

    public function cancel(): void
    {
        $this->showForm = false;
        $this->resetErrorBag();
    }

    public function save(): void
    {
        $this->allow('update', 'payroll_settings');

        $this->validate([
            'name' => 'required|string|max:120',
            'convention' => 'nullable|string|max:150',
            'footer_text' => 'nullable|string|max:1000',
        ], [], ['name' => 'nom du modèle', 'footer_text' => 'mentions de pied de page']);

        $data = [
            'name' => trim($this->name),
            'convention' => trim($this->convention) ?: null,
            'show_employer_part' => $this->show_employer_part,
            'show_item_codes' => $this->show_item_codes,
            'group_lines' => $this->group_lines,
            'footer_text' => trim($this->footer_text) ?: null,
            'is_default' => $this->is_default,
        ];

        $template = DB::transaction(function () use ($data) {
            if ($data['is_default']) {
                PayslipTemplate::where('is_default', true)->when($this->editingId, fn ($q) => $q->where('id', '!=', $this->editingId))->update(['is_default' => false]);
            }

            if ($this->editingId) {
                $t = PayslipTemplate::findOrFail($this->editingId);
                $t->update($data);

                return $t;
            }

            $t = new PayslipTemplate($data);
            $t->company_id = $this->companyId();
            $t->save();

            return $t;
        });

        $this->audit($this->editingId ? 'update' : 'create', 'payroll_settings', $template->id, null, $data + ['resource' => 'payslip_template']);
        $this->showForm = false;
        $this->flash = 'Modèle de bulletin enregistré.';
    }

    public function makeDefault(int $id): void
    {
        $this->allow('update', 'payroll_settings');
        $t = PayslipTemplate::findOrFail($id);

        DB::transaction(function () use ($t) {
            PayslipTemplate::where('is_default', true)->update(['is_default' => false]);
            $t->update(['is_default' => true]);
        });

        $this->audit('update', 'payroll_settings', $t->id, null, ['default_payslip_template' => $t->name]);
        $this->flash = 'Modèle par défaut : « ' . $t->name . ' ».';
    }

    public function deleteTemplate(int $id): void
    {
        $this->allow('update', 'payroll_settings');
        $t = PayslipTemplate::findOrFail($id);

        if ($t->is_default && PayslipTemplate::count() > 1) {
            $this->flash = 'Choisissez d\'abord un autre modèle par défaut avant de supprimer celui-ci.';

            return;
        }

        $this->audit('delete', 'payroll_settings', $t->id, ['payslip_template' => $t->name], null);
        $t->delete();
        $this->flash = 'Modèle de bulletin supprimé.';
    }

    public function render()
    {
        return view('livewire.payroll.payslip-templates', [
            'templates' => PayslipTemplate::orderByDesc('is_default')->orderBy('name')->get(),
            'canEdit' => $this->allowed('update', 'payroll_settings'),
        ])->layout($this->resolveLayout('company'), ['title' => 'Modèles de bulletin']);
    }
}
