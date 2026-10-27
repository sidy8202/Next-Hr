@php($canEdit = auth()->user()->canDo('update', 'leave_settings'))

<div class="space-y-6">
    <div>
        <h2 class="text-2xl font-bold">Types de permission et règles</h2>
        <p class="text-sm text-ink-muted">Pour chaque type : jours accordés sans retenue (par demande), règle pour l’excédent, plafonds et message de la fiche. Exemple : « Permission personnelle », 2 jours gratuits, au-delà retiré du congé annuel.</p>
    </div>

    <div class="overflow-x-auto rounded-2xl border border-line bg-surface shadow-sm">
        <table class="w-full text-left text-sm">
            <thead class="bg-surface-muted text-xs text-ink-muted"><tr><th class="px-4 py-2">Type</th><th class="px-4 py-2">Quota gratuit</th><th class="px-4 py-2">Au-delà</th><th class="px-4 py-2">Limites</th><th class="px-4 py-2">Actif</th><th class="px-4 py-2"></th></tr></thead>
            <tbody class="divide-y divide-line">
                @foreach ($this->types as $t)
                    <tr wire:key="pt-{{ $t->id }}">
                        <td class="px-4 py-3">{{ $t->name }}<span class="block text-xs text-ink-muted">{{ $t->kind === 'hours' ? 'En heures' : 'En jours' }}@if ($t->requires_document) · justificatif obligatoire @endif</span></td>
                        <td class="px-4 py-3">{{ $t->summary() }}</td>
                        <td class="px-4 py-3 text-xs">{{ \App\Models\PermissionType::POLICIES[$t->excess_policy] }}</td>
                        <td class="px-4 py-3 text-xs">@if ($t->max_days) max {{ $t->max_days }} j/demande @endif @if ($t->yearly_cap_days) · plafond {{ $t->yearly_cap_days }} j/an @endif</td>
                        <td class="px-4 py-3"><input type="checkbox" @checked($t->active) @if ($canEdit) wire:click="toggleActive({{ $t->id }})" @else disabled @endif></td>
                        <td class="px-4 py-3 text-right">@if ($canEdit)<button type="button" wire:click="edit({{ $t->id }})" class="rounded border border-line px-2 py-1 text-xs hover:bg-surface-muted">Modifier</button>@endif</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    @if ($canEdit)
        <form wire:submit="save" class="space-y-4 rounded-2xl border border-line bg-surface p-5 shadow-sm">
            <h3 class="text-sm font-semibold">{{ $editing ? 'Modifier le type' : 'Nouveau type de permission' }}</h3>
            <div class="grid gap-4 sm:grid-cols-4">
                <div class="sm:col-span-2"><label class="mb-1 block text-xs font-medium">Nom</label><input type="text" wire:model="name" class="input py-1">@error('name')<p class="mt-1 text-xs text-rose-600">{{ $message }}</p>@enderror</div>
                <div><label class="mb-1 block text-xs font-medium">Mesure</label><select wire:model.live="kind" class="input py-1"><option value="days">Jours</option><option value="hours">Heures</option></select></div>
                <div><label class="mb-1 block text-xs font-medium">Au-delà du quota</label>
                    <select wire:model="excess_policy" class="input py-1">@foreach (\App\Models\PermissionType::POLICIES as $k => $l)<option value="{{ $k }}">{{ $l }}</option>@endforeach</select></div>
                @if ($kind === 'hours')
                    <div><label class="mb-1 block text-xs font-medium">Heures gratuites par mois</label><input type="number" step="0.5" min="0" wire:model="hours_free_per_month" class="input py-1"></div>
                @else
                    <div><label class="mb-1 block text-xs font-medium">Jours gratuits par demande</label><input type="number" step="0.5" min="0" wire:model="free_days" class="input py-1">@error('free_days')<p class="mt-1 text-xs text-rose-600">{{ $message }}</p>@enderror</div>
                    <div><label class="mb-1 block text-xs font-medium">Durée max. par demande (j)</label><input type="number" step="0.5" min="0.5" wire:model="max_days" class="input py-1"></div>
                    <div><label class="mb-1 block text-xs font-medium">Plafond annuel (j)</label><input type="number" step="0.5" min="0.5" wire:model="yearly_cap_days" class="input py-1"></div>
                @endif
                <label class="flex items-center gap-2 pt-5 text-xs"><input type="checkbox" wire:model="requires_document"> Justificatif obligatoire</label>
                <div class="sm:col-span-4"><label class="mb-1 block text-xs font-medium">Message de la fiche de permission (vide = message par défaut)</label><input type="text" wire:model="sheet_message" class="input py-1"></div>
            </div>
            <div class="flex justify-end gap-2">
                @if ($editing)<button type="button" wire:click="cancelEdit" class="rounded-lg border border-line px-3 py-1.5 text-sm hover:bg-surface-muted">Annuler</button>@endif
                <button type="submit" class="rounded-lg bg-indigo-600 px-3 py-1.5 text-sm font-semibold text-white hover:bg-indigo-500">Enregistrer</button>
            </div>
        </form>
    @endif
</div>
