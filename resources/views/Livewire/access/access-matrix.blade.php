<div class="space-y-6">
    <div>
        <h2 class="text-2xl font-bold">Droits d'accès</h2>
        <p class="text-sm text-ink-muted">Choisissez un utilisateur, cochez ce qu'il peut faire sur chaque ressource, puis enregistrez. Tout est à zéro par défaut.</p>
    </div>

    {{-- Choix de l'utilisateur --}}
    <div class="rounded-2xl border border-line bg-surface p-5 shadow-sm">
        @if ($this->target)
            <div class="flex flex-wrap items-center justify-between gap-3">
                <div>
                    <p class="font-semibold">{{ $this->target->name }}</p>
                    <p class="text-sm text-ink-muted">
                        {{ $this->target->email }}
                        @if ($this->target->employee) · {{ $this->target->employee->matricule }} @endif
                        @if ($this->target->is_company_owner) <x-ui.badge tone="indigo" class="ml-1">Propriétaire</x-ui.badge>@endif
                    </p>
                </div>
                <x-ui.button variant="secondary" wire:click="clearUser">Changer d'utilisateur</x-ui.button>
            </div>
        @else
            <div class="grid gap-3 sm:grid-cols-3">
                <div>
                    <label for="dept" class="mb-1 block text-sm font-medium">Département</label>
                    <select id="dept" wire:model.live="departmentId" class="input">
                        <option value="">Tous les départements</option>
                        @foreach ($this->departments as $d)<option value="{{ $d->id }}">{{ $d->name }}</option>@endforeach
                    </select>
                </div>
                <div>
                    <label for="user-search" class="mb-1 block text-sm font-medium">Filtrer (nom, email, code)</label>
                    <input id="user-search" type="search" wire:model.live.debounce.300ms="userSearch" class="input" placeholder="Taper pour réduire la liste…" autocomplete="off">
                </div>
                <div>
                    <label for="pick" class="mb-1 block text-sm font-medium">Utilisateur</label>
                    <select id="pick" wire:model.live="pickUserId" class="input">
                        <option value="">Choisir… ({{ $this->userResults->count() }})</option>
                        @foreach ($this->userResults as $u)
                            <option value="{{ $u->id }}">{{ $u->name }}@if($u->employee) — {{ $u->employee->matricule }}@endif</option>
                        @endforeach
                    </select>
                </div>
            </div>
        @endif
    </div>

    @if ($this->target)
        @if ($this->locked)
            <p class="rounded-xl bg-amber-50 px-4 py-3 text-sm text-amber-900 dark:bg-amber-500/10 dark:text-amber-200">{{ $this->locked }}</p>
        @endif

        @error('matrix')
            <p class="rounded-xl bg-rose-50 px-4 py-3 text-sm text-rose-800 dark:bg-rose-500/10 dark:text-rose-200">{{ $message }}</p>
        @enderror

        @if (! $this->locked)
            <div class="flex flex-wrap items-end gap-3 rounded-2xl border border-line bg-surface p-4 shadow-sm">
                <div>
                    <label for="tpl" class="mb-1 block text-sm font-medium">Appliquer un modèle</label>
                    <select id="tpl" wire:model.live="template" class="input w-64">
                        <option value="">Choisir…</option>
                        @foreach ($templates as $key => $t)<option value="{{ $key }}">{{ $t['label'] }}</option>@endforeach
                    </select>
                </div>
                <x-ui.button variant="secondary" wire:click="applyTemplate" :disabled="! $template">Pré-cocher</x-ui.button>
                <p class="text-xs text-ink-muted">Le modèle remplace la matrice affichée ; rien n'est enregistré avant « Enregistrer ».</p>
            </div>
        @endif

        <fieldset @disabled($this->locked) class="space-y-4">
            @foreach ($modules as $module => $resources)
                <div wire:key="module-{{ $module }}" class="overflow-hidden rounded-2xl border border-line bg-surface shadow-sm">
                    <div class="flex items-center justify-between bg-surface-muted px-4 py-2">
                        <h3 class="text-sm font-semibold">{{ $module }}</h3>
                        <div class="flex gap-1 text-xs">
                            @foreach (['create' => 'C', 'read' => 'R', 'update' => 'U', 'delete' => 'D'] as $action => $letter)
                                <button type="button" wire:click="toggleColumn('{{ $module }}', '{{ $action }}')" class="rounded border border-line px-2 py-0.5 hover:bg-surface" title="Tout cocher / décocher la colonne">{{ $letter }}</button>
                            @endforeach
                        </div>
                    </div>
                    <table class="w-full text-left text-sm">
                        <thead class="text-xs uppercase tracking-wide text-ink-muted">
                            <tr>
                                <th class="px-4 py-2">Ressource</th>
                                <th class="w-20 px-2 py-2 text-center">Créer</th>
                                <th class="w-20 px-2 py-2 text-center">Lire</th>
                                <th class="w-20 px-2 py-2 text-center">Modifier</th>
                                <th class="w-24 px-2 py-2 text-center">Supprimer</th>
                                <th class="w-16 px-2 py-2 text-center">Tout</th>
                                <th class="w-44 px-4 py-2">Portée</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-line">
                            @foreach ($resources as $key => $meta)
                                <tr wire:key="res-{{ $key }}">
                                    <td class="px-4 py-2 font-medium">{{ $meta['label'] }}</td>
                                    @foreach (['create', 'read', 'update', 'delete'] as $action)
                                        <td class="px-2 py-2 text-center">
                                            <input type="checkbox" wire:model.live="matrix.{{ $key }}.{{ $action }}" aria-label="{{ $meta['label'] }} : {{ $action }}">
                                        </td>
                                    @endforeach
                                    <td class="px-2 py-2 text-center">
                                        <button type="button" wire:click="toggleRow('{{ $key }}')" class="rounded border border-line p-1 hover:bg-surface-muted"
                                                title="Tout cocher / décocher cette ligne" aria-label="Tout cocher la ligne {{ $meta['label'] }}">
                                            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                <path d="M4 12l5 5L20 6"/>
                                                <path d="M4 6l5 5" opacity=".5"/>
                                            </svg>
                                        </button>
                                    </td>
                                    <td class="px-4 py-2">
                                        @if (in_array($key, $employeeScoped, true))
                                            <select wire:model="matrix.{{ $key }}.scope" class="input py-1" aria-label="Portée {{ $meta['label'] }}">
                                                @foreach ($scopeLabels as $value => $label)
                                                    <option value="{{ $value }}">{{ $label }}</option>
                                                @endforeach
                                            </select>
                                        @else
                                            <span class="text-ink-muted">{{ $scopeLabels['all'] }}</span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endforeach
        </fieldset>

        @if (! $this->locked)
            <div class="sticky bottom-0 -mx-4 flex items-center justify-end gap-3 border-t border-line bg-surface-muted/90 px-4 py-3 backdrop-blur sm:-mx-6 sm:px-6">
                <x-ui.button variant="secondary" wire:click="resetMatrix" wire:confirm="Tout décocher pour toutes les ressources ?">Tout décocher</x-ui.button>
                <x-ui.button wire:click="save" wire:loading.attr="disabled" wire:target="save" icon="check">Enregistrer les droits</x-ui.button>
            </div>
        @endif
    @endif
</div>