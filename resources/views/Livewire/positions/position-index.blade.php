<div>
    <div>
        <h2 class="text-2xl font-bold">Postes</h2>
        <p class="text-sm text-ink-muted">Les intitulés de poste de chaque département, avec leur grade et leur fourchette salariale.</p>
    </div>

    <div class="mt-6 rounded-2xl border border-line bg-surface shadow-sm">
        <div class="flex flex-wrap items-center gap-2 border-b border-line p-3">
            @canDo('create', 'positions')
                <x-ui.button :href="route('positions.create')" wire:navigate icon="plus">Nouveau</x-ui.button>
            @endcanDo
            @canDo('update', 'positions')
                <x-ui.button variant="secondary" wire:click="edit" :disabled="count($selected) !== 1" icon="pencil-square">Modifier</x-ui.button>
            @endcanDo
            @canDo('delete', 'positions')
                <x-ui.button variant="danger" wire:click="deleteSelected" wire:confirm="Supprimer les postes sélectionnés ?" :disabled="! $selected" icon="trash">Supprimer</x-ui.button>
            @endcanDo

            <div class="flex-1"></div>

            <input type="search" wire:model.live.debounce.300ms="search" class="input w-56" placeholder="Intitulé…" aria-label="Rechercher un poste">
            <select wire:model.live="filters.department" class="input w-52" aria-label="Filtrer par département">
                <option value="">Tous les départements</option>
                @foreach ($departments as $d)
                    <option value="{{ $d->id }}">{{ $d->code }} · {{ $d->name }}</option>
                @endforeach
            </select>
        </div>

        @php
            $pageIds = $rows->pluck('id')->map(fn ($i) => (string) $i)->all();
            $allOnPage = $pageIds !== [] && ! array_diff($pageIds, $selected);
        @endphp

        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead class="bg-surface-muted text-xs text-ink-muted">
                    <tr>
                        <th class="w-10 px-4 py-3"><input type="checkbox" wire:click="togglePage({{ json_encode($rows->pluck('id')) }})" @checked($allOnPage) aria-label="Tout sélectionner sur cette page"></th>
                        <x-ui.sort-th column="title" :sort-by="$sortBy" :sort-dir="$sortDir">Poste</x-ui.sort-th>
                        <th class="px-4 py-3 font-semibold uppercase tracking-wide">Département</th>
                        <x-ui.sort-th column="grade" :sort-by="$sortBy" :sort-dir="$sortDir">Grade</x-ui.sort-th>
                        <th class="px-4 py-3 font-semibold uppercase tracking-wide">Fourchette salariale</th>
                        <x-ui.sort-th column="employees_count" :sort-by="$sortBy" :sort-dir="$sortDir">Effectif</x-ui.sort-th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($rows as $row)
                        <tr wire:key="pos-{{ $row->id }}" @class(['border-t border-line hover:bg-surface-muted/60', 'bg-brand-50/60 dark:bg-brand-900/20' => in_array((string) $row->id, $selected, true)])>
                            <td class="px-4 py-3"><input type="checkbox" wire:model.live="selected" value="{{ $row->id }}" aria-label="Sélectionner {{ $row->title }}"></td>
                            <td class="px-4 py-3 font-medium">{{ $row->title }}</td>
                            <td class="px-4 py-3">{{ $row->department?->code }} <span class="text-ink-muted">· {{ $row->department?->name }}</span></td>
                            <td class="px-4 py-3 text-ink-muted">{{ $row->grade ?: '—' }}</td>
                            <td class="px-4 py-3 tabular-nums text-ink-muted">
                                @if ($row->salary_min || $row->salary_max)
                                    {{ number_format((int) $row->salary_min, 0, ',', ' ') }} – {{ number_format((int) $row->salary_max, 0, ',', ' ') }}
                                @else — @endif
                            </td>
                            <td class="px-4 py-3 tabular-nums">{{ $row->employees_count }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="6"><x-ui.empty-state title="Aucun poste" text="Créez un poste avec « Nouveau », ou ajoutez-en depuis un département." icon="briefcase" /></td></tr>
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
