<div class="space-y-6">
    <a href="{{ route('departments.index') }}" wire:navigate class="inline-flex items-center gap-1 text-sm text-ink-muted hover:text-ink">
        <x-heroicon-o-arrow-left class="size-4" /> Départements
    </a>

    <div class="flex flex-wrap items-start justify-between gap-4">
        <div>
            <div class="flex flex-wrap items-center gap-3">
                <h2 class="text-2xl font-bold">{{ $department->name }}</h2>
                <x-ui.badge tone="indigo">{{ $department->code }}</x-ui.badge>
                <x-ui.badge :tone="$department->is_active ? 'green' : 'gray'">{{ $department->is_active ? 'Actif' : 'Inactif' }}</x-ui.badge>
            </div>
            <p class="mt-1 text-sm text-ink-muted">
                Chef :
                @if ($head)
                    @if (Route::has('employees.show'))
                        <a href="{{ route('employees.show', $head->id) }}" wire:navigate class="font-medium text-brand-700 hover:underline dark:text-brand-300">{{ $head->matricule }} · {{ $head->full_name }}</a>
                    @else
                        <span class="font-medium">{{ $head->matricule }} · {{ $head->full_name }}</span>
                    @endif
                @else
                    aucun chef désigné
                @endif
            </p>
        </div>

        @canDo('update', 'departments')
            <div class="flex gap-2">
                <x-ui.button variant="secondary" :href="route('departments.edit', $department->id)" icon="pencil-square">Modifier</x-ui.button>
                <x-ui.button variant="secondary" wire:click="toggleActive">{{ $department->is_active ? 'Désactiver' : 'Activer' }}</x-ui.button>
            </div>
        @endcanDo
    </div>

    @if ($summary['non_working'])
        <p class="rounded-xl bg-amber-50 px-4 py-2 text-sm text-amber-800 dark:bg-amber-500/10 dark:text-amber-300">
            Aujourd'hui est un jour non ouvré (week-end ou jour férié).
        </p>
    @endif

    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <x-ui.stat-card label="Effectif total" :value="$summary['total']" hint="Employés actifs" icon="users" />
        <x-ui.stat-card label="Présents aujourd'hui" :value="$summary['present']" hint="Ni en congé ni en mission" icon="check-circle" />
        <x-ui.stat-card label="En congé" :value="$summary['leave']" hint="Congés approuvés" icon="sun" />
        <x-ui.stat-card label="En mission" :value="$summary['mission']" hint="Ordres de mission" icon="paper-airplane" />
    </div>

    <div class="rounded-2xl border border-line bg-surface shadow-sm">
        <div class="flex gap-1 border-b border-line px-3 pt-3" role="tablist">
            @foreach (['members' => 'Membres', 'positions' => 'Postes'] as $key => $label)
                <button type="button" role="tab" wire:click="$set('tab', '{{ $key }}')" aria-selected="{{ $tab === $key ? 'true' : 'false' }}"
                        @class(['rounded-t-lg px-4 py-2 text-sm font-medium', 'border-b-2 border-brand-600 text-brand-700 dark:text-brand-300' => $tab === $key, 'text-ink-muted hover:text-ink' => $tab !== $key])>
                    {{ $label }}
                </button>
            @endforeach
        </div>

        @if ($tab === 'members')
            <div class="flex flex-wrap items-center gap-2 border-b border-line p-3">
                <input type="search" wire:model.live.debounce.300ms="search" class="input w-64" placeholder="Code ou nom…" aria-label="Rechercher un membre">
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead class="bg-surface-muted text-xs text-ink-muted">
                        <tr>
                            <x-ui.sort-th column="matricule" :sort-by="$sortBy" :sort-dir="$sortDir">Code</x-ui.sort-th>
                            <x-ui.sort-th column="last_name" :sort-by="$sortBy" :sort-dir="$sortDir">Employé</x-ui.sort-th>
                            <th class="px-4 py-3 font-semibold uppercase tracking-wide">Poste</th>
                            <th class="px-4 py-3 font-semibold uppercase tracking-wide">Aujourd'hui</th>
                            <x-ui.sort-th column="hire_date" :sort-by="$sortBy" :sort-dir="$sortDir">Ancienneté</x-ui.sort-th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($members as $m)
                            <tr wire:key="member-{{ $m->id }}" class="border-t border-line">
                                <td class="px-4 py-3 font-semibold">
                                    @if (Route::has('employees.show'))
                                        <a href="{{ route('employees.show', $m->id) }}" wire:navigate class="text-brand-700 hover:underline dark:text-brand-300">{{ $m->matricule }}</a>
                                    @else
                                        {{ $m->matricule }}
                                    @endif
                                </td>
                                <td class="px-4 py-3">{{ $m->full_name }}@if ($m->id === $department->head_id) <x-ui.badge tone="indigo" class="ml-1">Chef</x-ui.badge>@endif</td>
                                <td class="px-4 py-3">{{ $m->position?->title }}</td>
                                <td class="px-4 py-3">
                                    @if ($m->status !== 'active')
                                        <x-ui.badge tone="gray">{{ $m->status === 'suspended' ? 'Suspendu' : 'Sorti' }}</x-ui.badge>
                                    @elseif (in_array($m->id, $statuses['leave'], true))
                                        <x-ui.badge tone="amber">En congé</x-ui.badge>
                                    @elseif (in_array($m->id, $statuses['mission'], true))
                                        <x-ui.badge tone="sky">En mission</x-ui.badge>
                                    @else
                                        <x-ui.badge tone="green">Présent</x-ui.badge>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-ink-muted">{{ $m->hire_date?->diffForHumans(now(), \Carbon\CarbonInterface::DIFF_ABSOLUTE) }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="5"><x-ui.empty-state title="Aucun membre" text="Aucun employé ne correspond à votre recherche (ou vous n'avez pas le droit de les voir)." icon="users" /></td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="flex flex-wrap items-center justify-between gap-3 border-t border-line px-4 py-3 text-sm">
                <span class="text-ink-muted">{{ $members->firstItem() ?? 0 }}–{{ $members->lastItem() ?? 0 }} sur {{ $members->total() }}</span>
                {{ $members->links('pagination.livewire') }}
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead class="bg-surface-muted text-xs uppercase tracking-wide text-ink-muted">
                        <tr><th class="px-4 py-3">Poste</th><th class="px-4 py-3">Grade</th><th class="px-4 py-3">Effectif</th></tr>
                    </thead>
                    <tbody>
                        @forelse ($positions as $p)
                            <tr wire:key="pos-{{ $p->id }}" class="border-t border-line">
                                <td class="px-4 py-3 font-medium">{{ $p->title }}</td>
                                <td class="px-4 py-3 text-ink-muted">{{ $p->grade ?: '—' }}</td>
                                <td class="px-4 py-3 tabular-nums">{{ $p->employees_count }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="3"><x-ui.empty-state title="Aucun poste" text="Ajoutez des postes depuis la modification du département." icon="briefcase" /></td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        @endif
    </div>
</div>
