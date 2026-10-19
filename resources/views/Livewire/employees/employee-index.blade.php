<div>
    <div>
        <h2 class="text-2xl font-bold">Employés</h2>
        <p class="text-sm text-ink-muted">Le personnel de l'entreprise. Cliquez sur une ligne pour ouvrir la fiche.</p>
    </div>

    <div class="mt-6 rounded-2xl border border-line bg-surface shadow-sm">
        <div class="flex flex-wrap items-center gap-2 border-b border-line p-3">
            @canDo('create', 'employees')
                <x-ui.button :href="route('employees.create')" wire:navigate icon="plus">Nouveau</x-ui.button>
                <x-ui.button variant="secondary" :href="route('employees.batch')" wire:navigate icon="user-group">Nouvelles recrues</x-ui.button>
            @endcanDo
            @canDo('update', 'employees')
                <x-ui.button variant="secondary" wire:click="editSelected" :disabled="count($selected) !== 1" icon="pencil-square">Modifier</x-ui.button>
                <x-ui.button variant="secondary" wire:click="setStatus('suspended')" :disabled="! $selected">Suspendre</x-ui.button>
                <x-ui.button variant="secondary" wire:click="setStatus('active')" :disabled="! $selected">Réactiver</x-ui.button>
            @endcanDo
            @canDo('delete', 'employees')
                <x-ui.button variant="danger" wire:click="deleteSelected" wire:confirm="Retirer les employés sélectionnés de la liste ? Leurs comptes seront désactivés." :disabled="! $selected" icon="trash">Supprimer</x-ui.button>
            @endcanDo
            <x-ui.button variant="secondary" wire:click="export" icon="arrow-down-tray">Exporter</x-ui.button>

            <div class="flex-1"></div>

            <input type="search" wire:model.live.debounce.300ms="search" class="input w-56" placeholder="Code ou nom…" aria-label="Rechercher un employé">
            <select wire:model.live="filters.department" class="input w-48" aria-label="Filtrer par département">
                <option value="">Tous les départements</option>
                @foreach ($departments as $d)<option value="{{ $d->id }}">{{ $d->code }} · {{ $d->name }}</option>@endforeach
            </select>
            <select wire:model.live="filters.status" class="input w-40" aria-label="Filtrer par statut">
                <option value="">Tous les statuts</option>
                <option value="active">Actifs</option><option value="suspended">Suspendus</option><option value="terminated">Sortis</option>
            </select>
            <select wire:model.live="filters.expat" class="input w-40" aria-label="Filtrer par origine">
                <option value="">Tous</option><option value="1">Expatriés</option>
            </select>
        </div>

        @if ($selected)
            <div class="flex items-center gap-3 bg-brand-50 px-4 py-2 text-sm text-brand-800 dark:bg-brand-900/30 dark:text-brand-200">
                {{ count($selected) }} sélectionné(s)
                <button type="button" wire:click="clearSelection" class="font-medium underline">Tout désélectionner</button>
            </div>
        @endif

        @php
            $pageIds = $rows->pluck('id')->map(fn ($i) => (string) $i)->all();
            $allOnPage = $pageIds !== [] && ! array_diff($pageIds, $selected);
            $statusLabels = ['active' => ['Actif', 'green'], 'suspended' => ['Suspendu', 'amber'], 'terminated' => ['Sorti', 'gray']];
        @endphp

        <div class="overflow-x-auto" wire:loading.class="opacity-60" wire:target="search,filters,perPage,sort,gotoPage,nextPage,previousPage">
            <table class="w-full text-left text-sm">
                <thead class="bg-surface-muted text-xs text-ink-muted">
                    <tr>
                        <th class="w-10 px-4 py-3"><input type="checkbox" wire:click="togglePage({{ json_encode($rows->pluck('id')) }})" @checked($allOnPage) aria-label="Tout sélectionner sur cette page"></th>
                        <x-ui.sort-th column="matricule" :sort-by="$sortBy" :sort-dir="$sortDir">Code</x-ui.sort-th>
                        <x-ui.sort-th column="last_name" :sort-by="$sortBy" :sort-dir="$sortDir">Employé</x-ui.sort-th>
                        <th class="px-4 py-3 font-semibold uppercase tracking-wide">Département</th>
                        <th class="px-4 py-3 font-semibold uppercase tracking-wide">Poste</th>
                        <x-ui.sort-th column="hire_date" :sort-by="$sortBy" :sort-dir="$sortDir">Embauche</x-ui.sort-th>
                        <x-ui.sort-th column="status" :sort-by="$sortBy" :sort-dir="$sortDir">Statut</x-ui.sort-th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($rows as $row)
                        <tr wire:key="emp-{{ $row->id }}" x-data
                            @click="if (! $event.target.closest('input,a,button')) Livewire.navigate('{{ route('employees.show', $row->id) }}')"
                            @class(['cursor-pointer border-t border-line hover:bg-surface-muted/60', 'bg-brand-50/60 dark:bg-brand-900/20' => in_array((string) $row->id, $selected, true)])>
                            <td class="px-4 py-3"><input type="checkbox" wire:model.live="selected" value="{{ $row->id }}" aria-label="Sélectionner {{ $row->full_name }}"></td>
                            <td class="px-4 py-3 font-semibold"><a href="{{ route('employees.show', $row->id) }}" wire:navigate class="text-brand-700 hover:underline dark:text-brand-300">{{ $row->matricule }}</a></td>
                            <td class="px-4 py-3">{{ $row->full_name }}@if ($row->is_expatriate) <x-ui.badge tone="sky" class="ml-1">Expat</x-ui.badge>@endif</td>
                            <td class="px-4 py-3">{{ $row->department?->code }}</td>
                            <td class="px-4 py-3 text-ink-muted">{{ $row->position?->title }}</td>
                            <td class="px-4 py-3 text-ink-muted">{{ $row->hire_date?->format('d/m/Y') }}</td>
                            <td class="px-4 py-3"><x-ui.badge :tone="$statusLabels[$row->status][1] ?? 'gray'">{{ $statusLabels[$row->status][0] ?? $row->status }}</x-ui.badge></td>
                        </tr>
                    @empty
                        <tr><td colspan="7"><x-ui.empty-state title="Aucun employé" text="Ajoutez votre premier employé, ou plusieurs recrues d'un coup, avec les boutons ci-dessus." icon="users" /></td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="flex flex-wrap items-center justify-between gap-3 border-t border-line px-4 py-3 text-sm">
            <div class="flex items-center gap-2 text-ink-muted">
                <span>{{ $rows->firstItem() ?? 0 }}–{{ $rows->lastItem() ?? 0 }} sur {{ $rows->total() }}</span>
                <select wire:model.live="perPage" class="input w-20 py-1" aria-label="Lignes par page">
                    @foreach ([10, 25, 50, 100] as $n)<option value="{{ $n }}">{{ $n }}</option>@endforeach
                </select>
                <span>par page</span>
            </div>
            {{ $rows->links('pagination.livewire') }}
        </div>
    </div>

</div>
