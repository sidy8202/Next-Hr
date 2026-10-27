@php
    $fmt = fn ($n) => (rtrim(rtrim(number_format((float) $n, 2, ',', ' '), '0'), ',') ?: '0');
    $stateStyles = ['active' => 'bg-emerald-100 text-emerald-800', 'upcoming' => 'bg-sky-100 text-sky-800', 'past' => 'bg-slate-100 text-slate-700'];
    $stateLabels = ['active' => 'En cours', 'upcoming' => 'À venir', 'past' => 'Terminée'];
    $service = app(\App\Services\AssignmentService::class);
@endphp

<div class="space-y-6">
    <div class="flex flex-wrap items-center justify-between gap-3">
        <div>
            <h2 class="text-2xl font-bold">Affectations et intérims</h2>
            <p class="text-sm text-ink-muted">Un employé garde son poste principal et peut occuper, pendant une période, un autre poste : intérim, détachement ou cumul, dans le même département ou un autre, à Bamako ou dans une autre base.</p>
        </div>
        @if ($this->canManage)
            <button type="button" wire:click="$toggle('showForm')" class="inline-flex items-center rounded-xl bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-500">{{ $showForm ? 'Fermer' : 'Nouvelle affectation' }}</button>
        @endif
    </div>

    @error('assignment')<p class="rounded-xl bg-rose-50 px-4 py-3 text-sm text-rose-800 dark:bg-rose-500/10 dark:text-rose-200">{{ $message }}</p>@enderror

    @if ($this->canManage && $showForm)
        <form wire:submit="save" class="space-y-4 rounded-2xl border border-line bg-surface p-5 shadow-sm">
            <div class="grid gap-4 sm:grid-cols-3">
                <div class="sm:col-span-2">
                    <label class="mb-1 block text-sm font-medium">Employé</label>
                    <select wire:model="employee_id" class="input"><option value="">Choisir…</option>@foreach ($this->employees as $e)<option value="{{ $e->id }}">{{ $e->last_name }} {{ $e->first_name }} — {{ $e->matricule }}</option>@endforeach</select>
                    @error('employee_id')<p class="mt-1 text-xs text-rose-600">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="mb-1 block text-sm font-medium">Nature</label>
                    <select wire:model="type" class="input">@foreach (\App\Models\Assignment::TYPES as $k => $l)<option value="{{ $k }}">{{ $l }}</option>@endforeach</select>
                </div>
                <div>
                    <label class="mb-1 block text-sm font-medium">Département du poste</label>
                    <select wire:model.live="department_id" class="input"><option value="">Choisir…</option>@foreach ($this->departments as $d)<option value="{{ $d->id }}">{{ $d->name }}</option>@endforeach</select>
                    @error('department_id')<p class="mt-1 text-xs text-rose-600">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="mb-1 block text-sm font-medium">Poste</label>
                    <select wire:model="position_id" class="input" @disabled(! $department_id)><option value="">Choisir…</option>@foreach ($this->positions as $p)<option value="{{ $p->id }}">{{ $p->title ?? $p->name }}</option>@endforeach</select>
                    @error('position_id')<p class="mt-1 text-xs text-rose-600">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="mb-1 block text-sm font-medium">Base de travail</label>
                    <select wire:model="work_site_id" class="input"><option value="">Non précisée</option>@foreach ($this->sites as $s)<option value="{{ $s->id }}">{{ $s->name }}</option>@endforeach</select>
                </div>
                <div>
                    <label class="mb-1 block text-sm font-medium">Début</label>
                    <input type="date" wire:model="start_date" class="input">@error('start_date')<p class="mt-1 text-xs text-rose-600">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="mb-1 block text-sm font-medium">Fin (vide = sans limite)</label>
                    <input type="date" wire:model="end_date" class="input">@error('end_date')<p class="mt-1 text-xs text-rose-600">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="mb-1 block text-sm font-medium">Supérieur pour cette affectation</label>
                    <select wire:model="reports_to_employee_id" class="input"><option value="">Aucun</option>@foreach ($this->employees as $e)<option value="{{ $e->id }}">{{ $e->last_name }} {{ $e->first_name }}</option>@endforeach</select>
                </div>
                <div>
                    <label class="mb-1 block text-sm font-medium">Part du temps / du coût (%) <span class="text-xs font-normal text-ink-muted">— informatif : répartit plus tard le coût entre les postes ; le total reste ≤ 100 %</span></label>
                    <input type="number" min="1" max="100" wire:model="allocation_percent" class="input" placeholder="ex. 40">
                </div>
                <div>
                    <label class="mb-1 block text-sm font-medium">Prime liée à l’affectation</label>
                    <input type="number" min="0" step="0.01" wire:model="allowance_amount" class="input" placeholder="ex. 50000">
                </div>
                <div>
                    <label class="mb-1 block text-sm font-medium">Mode de la prime</label>
                    <select wire:model="allowance_basis" class="input"><option value="monthly">Mensuelle, proratisée au jour</option><option value="total">Montant total réparti sur la durée</option></select>
                </div>
                <div class="sm:col-span-3"><label class="mb-1 block text-sm font-medium">Motif</label><input type="text" wire:model="reason" class="input" placeholder="ex. Remplacement du chef de département pendant son congé"></div>
                <label class="flex items-start gap-2 text-sm sm:col-span-3"><input type="checkbox" wire:model="acts_as_head" class="mt-1"> <span>Fait fonction de chef de ce département : il valide à sa place les demandes de congé, de permission et de jours travaillés de l’équipe.</span></label>
                <label class="flex items-start gap-2 text-sm sm:col-span-3"><input type="checkbox" wire:model="manager_validates" class="mt-1"> <span>Le supérieur de cette affectation peut aussi valider les demandes de l’employé (en plus de son chef habituel).</span></label>
            </div>
            <div class="flex justify-end"><button type="submit" class="rounded-xl bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-500">Enregistrer l’affectation</button></div>
        </form>
    @endif

    <div class="flex flex-wrap items-end gap-3">
        <div>
            <label class="mb-1 block text-sm font-medium">Afficher</label>
            <select wire:model.live="state" class="input w-48"><option value="active">En cours</option><option value="upcoming">À venir</option><option value="past">Terminées</option><option value="all">Toutes</option></select>
        </div>
        <div><label class="mb-1 block text-sm font-medium">Employé</label><input type="search" wire:model.live.debounce.300ms="search" class="input" placeholder="Nom ou matricule"></div>
    </div>

    <div class="overflow-x-auto rounded-2xl border border-line bg-surface shadow-sm">
        <table class="w-full text-left text-sm">
            <thead class="bg-surface-muted text-xs text-ink-muted"><tr><th class="px-4 py-2">Employé</th><th class="px-4 py-2">Affectation</th><th class="px-4 py-2">Période</th><th class="px-4 py-2">Prime ce mois</th><th class="px-4 py-2">Statut</th><th class="px-4 py-2"></th></tr></thead>
            <tbody class="divide-y divide-line">
                @forelse ($this->assignments as $a)
                    @php($line = collect($service->allowanceLines($a->employee, now()))->first(fn ($l) => $l['assignment']->id === $a->id))
                    <tr wire:key="as-{{ $a->id }}">
                        <td class="px-4 py-3">{{ $a->employee?->full_name }}<span class="block text-xs text-ink-muted">{{ $a->employee?->matricule }}</span></td>
                        <td class="px-4 py-3">
                            <span class="font-medium">{{ $a->position?->title ?? $a->position?->name }}</span> — {{ $a->department?->name }}
                            <span class="block text-xs text-ink-muted">{{ $a->typeLabel() }}@if ($a->site) · base {{ $a->site->name }}@endif @if ($a->allocation_percent) · {{ $a->allocation_percent }} % du temps (poste principal : {{ $service->costSplit($a->employee)['primary'] }} %) @endif @if ($a->reportsTo) · sous {{ $a->reportsTo->first_name }} {{ $a->reportsTo->last_name }}@endif @if ($a->acts_as_head) · fait fonction de chef @endif</span>
                            @if ($a->reason)<span class="block text-xs text-ink-muted">{{ $a->reason }}</span>@endif
                        </td>
                        <td class="px-4 py-3">{{ $a->start_date->format('d/m/Y') }} → {{ $a->end_date?->format('d/m/Y') ?? 'sans limite' }}</td>
                        <td class="px-4 py-3">@if ($a->allowance_amount) {{ $fmt($a->allowance_amount) }} ({{ $a->allowance_basis === 'total' ? 'total' : 'mensuelle' }})@if ($line)<span class="block text-xs text-ink-muted">ce mois : {{ $fmt($line['amount']) }} ({{ $line['days'] }} j)</span>@endif @else — @endif</td>
                        <td class="px-4 py-3"><span class="rounded-full px-2 py-0.5 text-xs {{ $stateStyles[$a->state()] ?? '' }}">{{ $stateLabels[$a->state()] }}</span></td>
                        <td class="px-4 py-3 text-right">
                            @if ($this->canManage)
                                <div class="flex flex-wrap justify-end gap-2 text-xs">
                                    @if ($a->state() !== 'past')<button type="button" wire:click="end({{ $a->id }})" wire:confirm="Mettre fin à cette affectation aujourd’hui ?" class="rounded border border-line px-2 py-1 hover:bg-surface-muted">Mettre fin</button>@endif
                                    <button type="button" wire:click="delete({{ $a->id }})" wire:confirm="Supprimer cette affectation ?" class="rounded border border-rose-300 px-2 py-1 text-rose-700 hover:bg-rose-50">Supprimer</button>
                                </div>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="px-4 py-10 text-center text-ink-muted">Aucune affectation pour ces critères.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{ $this->assignments->links() }}

    @if ($this->canManage)
        <section class="space-y-3 rounded-2xl border border-line bg-surface p-5 shadow-sm">
            <h3 class="text-sm font-semibold">Bases de travail</h3>
            <div class="flex flex-wrap gap-2 text-sm">
                @forelse ($this->sites as $s)
                    <span wire:key="site-{{ $s->id }}" class="inline-flex items-center gap-2 rounded-full bg-surface-muted px-3 py-1">{{ $s->name }}@if ($s->city) <span class="text-xs text-ink-muted">({{ $s->city }})</span>@endif
                        <button type="button" wire:click="deleteSite({{ $s->id }})" wire:confirm="Supprimer cette base ?" class="text-xs text-rose-600" aria-label="Supprimer">×</button></span>
                @empty
                    <span class="text-ink-muted">Aucune base : ajoutez Bamako, Sikasso, Ségou…</span>
                @endforelse
            </div>
            <form wire:submit="addSite" class="flex flex-wrap items-end gap-3">
                <div><label class="mb-1 block text-xs font-medium">Nom</label><input type="text" wire:model="site_name" class="input py-1" placeholder="ex. Base de Sikasso">@error('site_name')<p class="mt-1 text-xs text-rose-600">{{ $message }}</p>@enderror</div>
                <div><label class="mb-1 block text-xs font-medium">Ville</label><input type="text" wire:model="site_city" class="input py-1"></div>
                <button type="submit" class="rounded-lg border border-line px-3 py-1.5 text-sm hover:bg-surface-muted">Ajouter la base</button>
            </form>
            <form wire:submit="setBase" class="flex flex-wrap items-end gap-3 border-t border-line pt-3">
                <div><label class="mb-1 block text-xs font-medium">Base principale d’un employé</label>
                    <select wire:model="base_employee_id" class="input py-1"><option value="">Employé…</option>@foreach ($this->employees as $e)<option value="{{ $e->id }}">{{ $e->last_name }} {{ $e->first_name }}</option>@endforeach</select></div>
                <div><select wire:model="base_site_id" class="input py-1"><option value="">Sans base</option>@foreach ($this->sites as $s)<option value="{{ $s->id }}">{{ $s->name }}</option>@endforeach</select></div>
                <button type="submit" class="rounded-lg border border-line px-3 py-1.5 text-sm hover:bg-surface-muted">Enregistrer</button>
            </form>
        </section>
    @endif
</div>
