<div>
    <div>
        <h2 class="text-2xl font-bold">Départements</h2>
        <p class="text-sm text-ink-muted">Organisez vos équipes et leurs responsables.</p>
    </div>

    <div class="mt-6 rounded-2xl border border-line bg-surface shadow-sm">
        {{-- Boîte à outils --}}
        <div class="flex flex-wrap items-center gap-2 border-b border-line p-3">
            @canDo('create', 'departments')
                <x-ui.button :href="route('departments.create')" wire:navigate icon="plus">Nouveau</x-ui.button>
            @endcanDo
            @canDo('update', 'departments')
                <x-ui.button variant="secondary" wire:click="edit" :disabled="count($selected) !== 1" icon="pencil-square">Modifier</x-ui.button>
                <x-ui.button variant="secondary" wire:click="setActive(true)" :disabled="! $selected">Activer</x-ui.button>
                <x-ui.button variant="secondary" wire:click="setActive(false)" :disabled="! $selected">Désactiver</x-ui.button>
            @endcanDo
            @canDo('delete', 'departments')
                <x-ui.button variant="danger" wire:click="deleteSelected" wire:confirm="Supprimer définitivement les départements sélectionnés ? Cette action est irréversible." :disabled="! $selected" icon="trash">Supprimer</x-ui.button>
            @endcanDo
            <x-ui.button variant="secondary" wire:click="export" icon="arrow-down-tray">Exporter</x-ui.button>

            <div class="flex-1"></div>

            <input type="search" wire:model.live.debounce.300ms="search" class="input w-56" placeholder="Code ou nom…" aria-label="Rechercher un département">
            <select wire:model.live="filters.status" class="input w-40" aria-label="Filtrer par statut">
                <option value="">Tous les statuts</option>
                <option value="active">Actifs</option>
                <option value="inactive">Inactifs</option>
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
        @endphp

        <div class="overflow-x-auto" wire:loading.class="opacity-60" wire:target="search,filters,perPage,sort,gotoPage,nextPage,previousPage">
            <table class="w-full text-left text-sm">
                <thead class="bg-surface-muted text-xs text-ink-muted">
                    <tr>
                        <th class="w-10 px-4 py-3">
                            <input type="checkbox" wire:click="togglePage({{ json_encode($rows->pluck('id')) }})" @checked($allOnPage) aria-label="Tout sélectionner sur cette page">
                        </th>
                        <x-ui.sort-th column="code" :sort-by="$sortBy" :sort-dir="$sortDir">Code</x-ui.sort-th>
                        <x-ui.sort-th column="name" :sort-by="$sortBy" :sort-dir="$sortDir">Département</x-ui.sort-th>
                        <th class="px-4 py-3 font-semibold uppercase tracking-wide">Chef</th>
                        <x-ui.sort-th column="employees_count" :sort-by="$sortBy" :sort-dir="$sortDir">Effectif</x-ui.sort-th>
                        <x-ui.sort-th column="is_active" :sort-by="$sortBy" :sort-dir="$sortDir">Statut</x-ui.sort-th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($rows as $row)
                        <tr wire:key="dept-{{ $row->id }}" x-data
                            @click="if (! $event.target.closest('input,a,button')) Livewire.navigate('{{ route('departments.show', $row->id) }}')"
                            @class(['cursor-pointer border-t border-line hover:bg-surface-muted/60', 'bg-brand-50/60 dark:bg-brand-900/20' => in_array((string) $row->id, $selected, true)])>
                            <td class="px-4 py-3">
                                <input type="checkbox" wire:model.live="selected" value="{{ $row->id }}" aria-label="Sélectionner {{ $row->name }}">
                            </td>
                            <td class="px-4 py-3 font-semibold">
                                <a href="{{ route('departments.show', $row->id) }}" wire:navigate class="text-brand-700 hover:underline dark:text-brand-300">{{ $row->code }}</a>
                            </td>
                            <td class="px-4 py-3">{{ $row->name }}</td>
                            <td class="px-4 py-3">
                                @if ($row->head)
                                    <span class="font-medium">{{ $row->head->matricule }}</span> · {{ $row->head->full_name }}
                                @else
                                    <span class="text-ink-muted">Aucun chef</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 tabular-nums">{{ $row->employees_count }}</td>
                            <td class="px-4 py-3">
                                <x-ui.badge :tone="$row->is_active ? 'green' : 'gray'">{{ $row->is_active ? 'Actif' : 'Inactif' }}</x-ui.badge>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6">
                                <x-ui.empty-state title="Aucun département" text="Créez votre premier département avec le bouton « Nouveau » ou modifiez votre recherche." icon="building-office" />
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="flex flex-wrap items-center justify-between gap-3 border-t border-line px-4 py-3 text-sm">
            <div class="flex items-center gap-2 text-ink-muted">
                <span>{{ $rows->firstItem() ?? 0 }}–{{ $rows->lastItem() ?? 0 }} sur {{ $rows->total() }}</span>
                <select wire:model.live="perPage" class="input w-20 py-1" aria-label="Lignes par page">
                    @foreach ([10, 25, 50, 100] as $n)
                        <option value="{{ $n }}">{{ $n }}</option>
                    @endforeach
                </select>
                <span>par page</span>
            </div>
            {{ $rows->links('pagination.livewire') }}
        </div>
    </div>

</div>
