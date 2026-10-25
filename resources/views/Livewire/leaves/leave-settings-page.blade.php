@php($canEdit = auth()->user()->canDo('update', 'leave_settings'))
@php($dayNames = [1 => 'Lundi', 2 => 'Mardi', 3 => 'Mercredi', 4 => 'Jeudi', 5 => 'Vendredi', 6 => 'Samedi', 7 => 'Dimanche'])

<div class="space-y-6">
    <div>
        <h2 class="text-2xl font-bold">Paramètres des congés</h2>
        <p class="text-sm text-ink-muted">Règles propres à votre entreprise : acquisition, tolérance de solde négatif, semaine de travail, jours fériés et types de congé.</p>
    </div>

    <form wire:submit="saveSettings" class="space-y-4 rounded-2xl border border-line bg-surface p-5 shadow-sm">
        <h3 class="text-sm font-semibold">Règles générales</h3>
        <div class="grid gap-4 sm:grid-cols-3">
            <div>
                <label class="mb-1 block text-sm font-medium">Jours acquis par mois (par défaut)</label>
                <input type="number" step="0.25" min="0" max="10" wire:model="default_monthly_days" class="input" @disabled(! $canEdit)>
                <p class="mt-1 text-xs text-ink-muted">Modifiable pour chaque employé (page Soldes). S’applique aux mois à venir.</p>
                @error('default_monthly_days')<p class="mt-1 text-xs text-rose-600">{{ $message }}</p>@enderror
            </div>
            <div>
                <label class="mb-1 block text-sm font-medium">Solde négatif toléré (jours)</label>
                <input type="number" step="0.5" min="0" max="30" wire:model="negative_tolerance" class="input" @disabled(! $canEdit)>
                <p class="mt-1 text-xs text-ink-muted">Au-delà du solde, jusqu’à cette limite, par exception motivée.</p>
                @error('negative_tolerance')<p class="mt-1 text-xs text-rose-600">{{ $message }}</p>@enderror
            </div>
            <div>
                <label class="mb-1 block text-sm font-medium">Validité d’un jour de récupération (jours)</label>
                <input type="number" min="1" max="730" wire:model="recovery_validity_days" class="input" @disabled(! $canEdit)>
                @error('recovery_validity_days')<p class="mt-1 text-xs text-rose-600">{{ $message }}</p>@enderror
            </div>
        </div>

        <div>
            <p class="mb-2 text-sm font-medium">Jours ouvrés de la semaine</p>
            <div class="flex flex-wrap gap-4 text-sm">
                @foreach ($dayNames as $n => $name)
                    <label class="flex items-center gap-2"><input type="checkbox" wire:model="workdays" value="{{ $n }}" @disabled(! $canEdit)> {{ $name }}</label>
                @endforeach
            </div>
            @error('workdays')<p class="mt-1 text-xs text-rose-600">{{ $message }}</p>@enderror
        </div>

        <div class="grid gap-4 border-t border-line pt-4 sm:grid-cols-2">
            <div>
                <label class="mb-1 block text-sm font-medium">Décompte d’un congé</label>
                <select wire:model="count_mode" class="input" @disabled(! $canEdit)>
                    @foreach (\App\Models\LeaveSetting::COUNT_MODES as $key => $label)<option value="{{ $key }}">{{ $label }}</option>@endforeach
                </select>
                <p class="mt-1 text-xs text-ink-muted">Au Mali, d’après le guide consulté (art. L.151), les congés annuels se comptent en jours calendaires, jours non ouvrables compris.</p>
                @error('count_mode')<p class="mt-1 text-xs text-rose-600">{{ $message }}</p>@enderror
            </div>
            <div>
                <label class="mb-1 block text-sm font-medium">Acquisition des jours</label>
                <select wire:model.live="accrual_mode" class="input" @disabled(! $canEdit)>
                    @foreach (\App\Models\LeaveSetting::ACCRUAL_MODES as $key => $label)<option value="{{ $key }}">{{ $label }}</option>@endforeach
                </select>
                @if ($accrual_mode === 'per_worked_days')
                    <div class="mt-2 flex items-center gap-2 text-sm">
                        La cadence ci-dessus est acquise pour chaque <input type="number" min="1" max="365" wire:model="accrual_per_days" class="input w-20 py-1" @disabled(! $canEdit)> jours travaillés
                    </div>
                    @error('accrual_per_days')<p class="mt-1 text-xs text-rose-600">{{ $message }}</p>@enderror
                @endif
                <p class="mt-1 text-xs text-ink-muted">Changer de mode n’efface pas les jours déjà crédités.</p>
            </div>
            <div class="sm:col-span-2">
                <label class="mb-1 block text-sm font-medium">Majorations d’ancienneté (années de service : jours en plus par an)</label>
                <input type="text" wire:model="bonuses_text" class="input" placeholder="15:2, 20:4, 25:6" @disabled(! $canEdit)>
                <p class="mt-1 text-xs text-ink-muted">Valeurs du guide consulté (+2 j après 15 ans, +4 après 20, +6 après 25) : à confirmer avec votre juriste ou votre convention. Vide = aucune majoration.</p>
                @error('bonuses_text')<p class="mt-1 text-xs text-rose-600">{{ $message }}</p>@enderror
            </div>
        </div>

        <label class="flex items-center gap-2 text-sm"><input type="checkbox" wire:model="deduct_unjustified" @disabled(! $canEdit)> Retirer du solde de congé les absences non justifiées</label>

        @if ($canEdit)
            <div class="flex justify-end"><button type="submit" class="rounded-xl bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-500">Enregistrer</button></div>
        @endif
    </form>

    <section class="space-y-3 rounded-2xl border border-line bg-surface p-5 shadow-sm">
        <h3 class="text-sm font-semibold">Plannings de travail</h3>
        <p class="text-xs text-ink-muted">Semaine standard par défaut. Créez une rotation (ex. 14 jours de travail puis 14 jours de repos) pour les sites miniers ou postés, puis affectez-la aux employés dans la page Soldes : un congé ne décompte alors que les jours de travail prévus, et travailler un jour de repos devient compensable.</p>
        @error('sched_name')<p class="text-xs text-rose-600">{{ $message }}</p>@enderror
        <ul class="divide-y divide-line rounded-xl border border-line text-sm">
            @forelse ($this->schedules as $sc)
                <li wire:key="sc-{{ $sc->id }}" class="flex items-center justify-between px-3 py-2">
                    <span>{{ $sc->label() }}@if ($sc->type === 'rotation') <span class="text-xs text-ink-muted">· cycle commençant le {{ $sc->anchor_date?->format('d/m/Y') }}</span>@endif</span>
                    @if ($canEdit)<button type="button" wire:click="deleteSchedule({{ $sc->id }})" wire:confirm="Supprimer ce planning ?" class="rounded border border-line px-2 py-1 text-xs hover:bg-surface-muted">Supprimer</button>@endif
                </li>
            @empty
                <li class="px-3 py-4 text-center text-ink-muted">Aucun planning : tous les employés suivent la semaine standard ci-dessus.</li>
            @endforelse
        </ul>
        @if ($canEdit)
            <form wire:submit="addSchedule" class="grid gap-3 sm:grid-cols-6">
                <div class="sm:col-span-2">
                    <label class="mb-1 block text-xs font-medium">Nom</label>
                    <input type="text" wire:model="sched_name" class="input py-1" placeholder="ex. Rotation A">
                </div>
                <div>
                    <label class="mb-1 block text-xs font-medium">Type</label>
                    <select wire:model.live="sched_type" class="input py-1"><option value="rotation">Rotation</option><option value="standard">Semaine standard</option></select>
                </div>
                @if ($sched_type === 'rotation')
                    <div><label class="mb-1 block text-xs font-medium">Jours de travail</label><input type="number" min="1" wire:model="sched_on" class="input py-1">@error('sched_on')<p class="mt-1 text-xs text-rose-600">{{ $message }}</p>@enderror</div>
                    <div><label class="mb-1 block text-xs font-medium">Jours de repos</label><input type="number" min="1" wire:model="sched_off" class="input py-1">@error('sched_off')<p class="mt-1 text-xs text-rose-600">{{ $message }}</p>@enderror</div>
                    <div><label class="mb-1 block text-xs font-medium">1er jour de travail</label><input type="date" wire:model="sched_anchor" class="input py-1">@error('sched_anchor')<p class="mt-1 text-xs text-rose-600">{{ $message }}</p>@enderror</div>
                    <label class="flex items-center gap-2 text-xs sm:col-span-6"><input type="checkbox" wire:model="sched_grants_leave"> Ouvre droit au congé annuel (sur site, le repos remplace en général le congé annuel : laissez décoché ; indiquez les semaines en jours, ex. 4 semaines = 28 jours)</label>
                @else
                    <div class="flex flex-wrap items-end gap-2 text-xs sm:col-span-3">
                        @foreach ($dayNames as $n => $name)<label class="flex items-center gap-1"><input type="checkbox" wire:model="sched_workdays" value="{{ $n }}"> {{ substr($name, 0, 3) }}</label>@endforeach
                    </div>
                @endif
                <div class="flex items-end"><button type="submit" class="rounded-lg border border-line px-3 py-1.5 text-sm hover:bg-surface-muted">Ajouter</button></div>
            </form>
        @endif
    </section>

    <section class="space-y-3 rounded-2xl border border-line bg-surface p-5 shadow-sm">
        <div class="flex flex-wrap items-center justify-between gap-2">
            <h3 class="text-sm font-semibold">Jours fériés</h3>
            @if ($canEdit)<button type="button" wire:click="addMaliHolidays" class="rounded-lg border border-line px-3 py-1 text-xs hover:bg-surface-muted">Ajouter les jours fériés fixes du Mali ({{ now()->year }} et {{ now()->year + 1 }})</button>@endif
        </div>
        @if ($canEdit)
            <form wire:submit="addHoliday" class="flex flex-wrap items-end gap-3">
                <div>
                    <label class="mb-1 block text-xs font-medium">Date</label>
                    <input type="date" wire:model="holiday_date" class="input py-1">
                    @error('holiday_date')<p class="mt-1 text-xs text-rose-600">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="mb-1 block text-xs font-medium">Nom</label>
                    <input type="text" wire:model="holiday_name" class="input py-1" placeholder="ex. Fête de l’Indépendance">
                    @error('holiday_name')<p class="mt-1 text-xs text-rose-600">{{ $message }}</p>@enderror
                </div>
                <button type="submit" class="rounded-lg border border-line px-3 py-1.5 text-sm hover:bg-surface-muted">Ajouter</button>
            </form>
        @endif
        <ul class="divide-y divide-line rounded-xl border border-line text-sm">
            @forelse ($this->holidays as $h)
                <li wire:key="h-{{ $h->id }}" class="flex items-center justify-between px-3 py-2">
                    <span>{{ $h->date->translatedFormat('l d/m/Y') }} — {{ $h->name }}</span>
                    @if ($canEdit)<button type="button" wire:click="deleteHoliday({{ $h->id }})" wire:confirm="Supprimer ce jour férié ?" class="rounded border border-line px-2 py-1 text-xs hover:bg-surface-muted">Supprimer</button>@endif
                </li>
            @empty
                <li class="px-3 py-4 text-center text-ink-muted">Aucun jour férié enregistré. Ajoutez ceux de votre calendrier : ils ne comptent pas dans les congés.</li>
            @endforelse
        </ul>
    </section>

    <section class="space-y-3 rounded-2xl border border-line bg-surface p-5 shadow-sm">
        <h3 class="text-sm font-semibold">Types de congé</h3>
        <div class="overflow-x-auto rounded-xl border border-line">
            <table class="w-full text-left text-sm">
                <thead class="bg-surface-muted text-xs text-ink-muted"><tr><th class="px-3 py-2">Type</th><th class="px-3 py-2">Effet</th><th class="px-3 py-2">Durée max.</th><th class="px-3 py-2">Justificatif obligatoire</th><th class="px-3 py-2">Actif</th></tr></thead>
                <tbody class="divide-y divide-line">
                    @foreach ($this->types as $t)
                        <tr wire:key="t-{{ $t->id }}">
                            <td class="px-3 py-2">{{ $t->name }}</td>
                            <td class="px-3 py-2 text-xs">{{ $t->poolLabel() }}</td>
                            <td class="px-3 py-2">{{ $t->max_days ? rtrim(rtrim(number_format($t->max_days, 1, ',', ''), '0'), ',').' j' : '—' }}</td>
                            <td class="px-3 py-2"><input type="checkbox" @checked($t->requires_document) @if ($canEdit) wire:click="toggleType({{ $t->id }}, 'requires_document')" @else disabled @endif></td>
                            <td class="px-3 py-2"><input type="checkbox" @checked($t->active) @if ($canEdit) wire:click="toggleType({{ $t->id }}, 'active')" @else disabled @endif></td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        @if ($canEdit)
            <form wire:submit="addType" class="grid gap-3 sm:grid-cols-5">
                <div class="sm:col-span-2">
                    <label class="mb-1 block text-xs font-medium">Nouveau type</label>
                    <input type="text" wire:model="type_name" class="input py-1" placeholder="ex. Congé d’étude">
                    @error('type_name')<p class="mt-1 text-xs text-rose-600">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="mb-1 block text-xs font-medium">Effet</label>
                    <select wire:model="type_pool" class="input py-1"><option value="annual">Retire du solde de congé</option><option value="recovery">Retire des jours de récupération</option><option value="none">Ne retire aucun jour</option></select>
                </div>
                <div>
                    <label class="mb-1 block text-xs font-medium">Durée max. (jours)</label>
                    <input type="number" step="0.5" min="0.5" wire:model="type_max_days" class="input py-1">
                </div>
                <div class="flex items-end gap-3">
                    <label class="flex items-center gap-2 text-xs"><input type="checkbox" wire:model="type_requires_document"> Justificatif</label>
                    <button type="submit" class="rounded-lg border border-line px-3 py-1.5 text-sm hover:bg-surface-muted">Ajouter</button>
                </div>
            </form>
        @endif
    </section>
</div>
