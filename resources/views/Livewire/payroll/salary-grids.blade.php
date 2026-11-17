<div class="mx-auto w-full max-w-[1800px] space-y-6">
    <div class="flex flex-wrap items-start justify-between gap-3">
        <div>
            <h1 class="text-xl font-semibold text-ink">Grilles salariales</h1>
            <p class="mt-1 text-sm text-ink-muted">
                Définissez vos catégories et vos échelons, puis rattachez chaque poste à une catégorie. Les conventions collectives ne sont qu'un point de départ : la grille reste la vôtre.
            </p>
        </div>
        @if ($canEdit)
            <button type="button" wire:click="newGrid" class="rounded-lg bg-ink px-4 py-2 text-sm font-medium text-surface">Nouvelle grille</button>
        @endif
    </div>

    @if ($flash)
        <div class="rounded-lg border border-line bg-surface-muted px-4 py-3 text-sm text-ink">{{ $flash }}</div>
    @endif

    @if ($showGridForm)
        <form wire:submit="saveGrid" class="space-y-4 rounded-xl border border-line bg-surface p-5">
            <h2 class="text-base font-semibold text-ink">{{ $editingGridId ? 'Modifier la grille' : 'Nouvelle grille' }}</h2>
            <div class="grid gap-4 sm:grid-cols-3">
                <div class="sm:col-span-2">
                    <label class="mb-1 block text-sm font-medium text-ink" for="gridName">Nom</label>
                    <input id="gridName" type="text" wire:model="gridName" class="input w-full" placeholder="Ex. Grille du personnel 2026">
                    @error('gridName') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="mb-1 block text-sm font-medium text-ink" for="gridFrom">En vigueur depuis</label>
                    <input id="gridFrom" type="date" wire:model="gridFrom" class="input w-full">
                    @error('gridFrom') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                </div>
                <div class="sm:col-span-2">
                    <label class="mb-1 block text-sm font-medium text-ink" for="gridConvention">Convention de référence (facultatif)</label>
                    <input id="gridConvention" type="text" wire:model="gridConvention" class="input w-full">
                    @error('gridConvention') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                </div>
                <label class="flex items-center gap-2 self-end text-sm text-ink">
                    <input type="checkbox" wire:model="gridActive"> Grille active
                </label>
            </div>
            <div class="flex justify-end gap-2">
                <button type="button" wire:click="cancelGridForm" class="rounded-lg border border-line px-4 py-2 text-sm text-ink">Annuler</button>
                <button type="submit" class="rounded-lg bg-ink px-4 py-2 text-sm font-medium text-surface">Enregistrer</button>
            </div>
        </form>
    @endif

    <div class="flex flex-wrap gap-2">
        @forelse ($grids as $g)
            <button type="button" wire:click="selectGrid({{ $g->id }})"
                class="rounded-full border px-3 py-1.5 text-sm {{ $gridId === $g->id ? 'border-ink bg-ink text-surface' : 'border-line bg-surface text-ink' }}">
                {{ $g->name }}
                <span class="opacity-70">· {{ $g->grades_count }} cat.</span>
                @if (! $g->is_active) <span class="opacity-70">· inactive</span> @endif
            </button>
        @empty
            <p class="text-sm text-ink-muted">Aucune grille pour le moment. Créez la première avec « Nouvelle grille ».</p>
        @endforelse
    </div>

    @if ($selected)
        <div class="space-y-4 rounded-xl border border-line bg-surface p-5">
            <div class="flex flex-wrap items-center justify-between gap-2">
                <div>
                    <h2 class="text-base font-semibold text-ink">{{ $selected->name }}</h2>
                    <p class="text-xs text-ink-muted">
                        @if ($selected->convention) {{ $selected->convention }} · @endif
                        @if ($selected->effective_from) en vigueur depuis le {{ $selected->effective_from->format('d/m/Y') }} · @endif
                        {{ $selected->is_active ? 'active' : 'inactive' }}
                    </p>
                </div>
                <div class="flex gap-2">
                    @if ($canEdit)
                        <button type="button" wire:click="editGrid({{ $selected->id }})" class="rounded-lg border border-line px-3 py-1.5 text-sm text-ink">Modifier</button>
                    @endif
                    @if ($canDelete)
                        <button type="button" wire:click="deleteGrid({{ $selected->id }})" wire:confirm="Supprimer cette grille, ses catégories et ses échelons ?" class="rounded-lg border border-line px-3 py-1.5 text-sm text-red-500">Supprimer</button>
                    @endif
                </div>
            </div>

            @foreach ($selected->grades as $grade)
                <div class="rounded-lg border border-line bg-surface-muted p-4">
                    <div class="flex flex-wrap items-center gap-3">
                        <span class="rounded-md bg-ink px-2 py-0.5 text-sm font-semibold text-surface">{{ $grade->code }}</span>
                        <input type="text" wire:model="gradeLabels.{{ $grade->id }}" wire:change="saveGrade({{ $grade->id }})"
                            class="input min-w-0 flex-1" placeholder="Libellé de la catégorie (facultatif)" @disabled(! $canEdit)>
                        @if ($canDelete)
                            <button type="button" wire:click="deleteGrade({{ $grade->id }})" wire:confirm="Supprimer la catégorie {{ $grade->code }} et ses échelons ?" class="text-sm text-red-500">Supprimer</button>
                        @endif
                    </div>

                    <div class="mt-3 space-y-2">
                        @foreach ($grade->steps as $step)
                            <div class="flex flex-wrap items-center gap-2">
                                <input type="text" wire:model="stepLabels.{{ $step->id }}" wire:change="saveStep({{ $step->id }})" class="input w-40" @disabled(! $canEdit)>
                                <input type="text" inputmode="numeric" wire:model="stepAmounts.{{ $step->id }}" wire:change="saveStep({{ $step->id }})" class="input w-40 text-right" @disabled(! $canEdit)>
                                <span class="text-sm text-ink-muted">FCFA</span>
                                @if ($canDelete)
                                    <button type="button" wire:click="deleteStep({{ $step->id }})" wire:confirm="Supprimer cet échelon ?" class="text-sm text-red-500">Retirer</button>
                                @endif
                                @error('stepAmounts.' . $step->id) <span class="text-xs text-red-500">{{ $message }}</span> @enderror
                            </div>
                        @endforeach

                        @if ($canEdit)
                            <div class="flex flex-wrap items-center gap-2 pt-1">
                                <input type="text" wire:model="newSteps.{{ $grade->id }}.label" class="input w-40" placeholder="Nom de l'échelon">
                                <input type="text" inputmode="numeric" wire:model="newSteps.{{ $grade->id }}.amount" wire:keydown.enter="addStep({{ $grade->id }})" class="input w-40 text-right" placeholder="Montant">
                                <button type="button" wire:click="addStep({{ $grade->id }})" class="rounded-lg border border-line px-3 py-1.5 text-sm text-ink">Ajouter l'échelon</button>
                                @error('newSteps.' . $grade->id . '.amount') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
                            </div>
                        @endif
                    </div>
                </div>
            @endforeach

            @if ($canEdit)
                <div class="flex flex-wrap items-start gap-2 border-t border-line pt-4">
                    <div>
                        <input type="text" wire:model="newGradeCode" wire:keydown.enter="addGrade" class="input w-32" placeholder="Code (E, M2…)">
                        @error('newGradeCode') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                    </div>
                    <input type="text" wire:model="newGradeLabel" wire:keydown.enter="addGrade" class="input w-64" placeholder="Libellé (facultatif)">
                    <button type="button" wire:click="addGrade" class="rounded-lg bg-ink px-3 py-2 text-sm font-medium text-surface">Ajouter une catégorie</button>
                </div>
            @endif
        </div>
    @endif

    <div class="space-y-3 rounded-xl border border-line bg-surface p-5">
        <div>
            <h2 class="text-base font-semibold text-ink">Postes et catégories</h2>
            <p class="text-xs text-ink-muted">Le grade d'un poste sert de valeur par défaut quand vous fixez le salaire d'un employé.</p>
        </div>

        @forelse ($positions as $p)
            <div class="flex flex-wrap items-center justify-between gap-3 border-b border-line pb-2 last:border-0">
                <span class="text-sm text-ink">{{ $p->title }}</span>
                <select wire:change="assignPosition({{ $p->id }}, $event.target.value)" class="input w-64" @disabled(! $canEdit)>
                    <option value="">— Aucune catégorie —</option>
                    @foreach ($gradeOptions as $grid)
                        @if ($grid->grades->isNotEmpty())
                            <optgroup label="{{ $grid->name }}">
                                @foreach ($grid->grades as $gr)
                                    <option value="{{ $gr->id }}" @selected((int) $p->salary_grade_id === $gr->id)>{{ $gr->code }}{{ $gr->label ? ' · ' . $gr->label : '' }}</option>
                                @endforeach
                            </optgroup>
                        @endif
                    @endforeach
                </select>
            </div>
        @empty
            <p class="text-sm text-ink-muted">Aucun poste enregistré.</p>
        @endforelse
    </div>
</div>
