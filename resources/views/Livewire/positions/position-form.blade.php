<form wire:submit="save" class="space-y-6">
    <div>
        <a href="{{ route('positions.index') }}" wire:navigate class="inline-flex items-center gap-1 text-sm text-ink-muted hover:text-ink">
            <x-heroicon-o-arrow-left class="size-4" /> Postes
        </a>
        <h2 class="mt-2 text-2xl font-bold">{{ $positionId ? 'Modifier le poste' : 'Nouveau poste' }}</h2>
    </div>

    <x-ui.error-summary />

    <div class="grid gap-6 lg:grid-cols-2">
        <x-ui.section title="1. Poste">
            <x-ui.field label="Département" name="form.department_id" required>
                <select id="form.department_id" wire:model.live="form.department_id" class="input">
                    <option value="">Choisir…</option>
                    @foreach ($departments as $d)<option value="{{ $d->id }}">{{ $d->code }} · {{ $d->name }}</option>@endforeach
                </select>
            </x-ui.field>
            <x-ui.field label="Intitulé du poste" name="form.title" required>
                <input id="form.title" type="text" wire:model="form.title" class="input" maxlength="120">
            </x-ui.field>
            <x-ui.field label="Grade" name="form.grade">
                <input id="form.grade" type="text" wire:model="form.grade" class="input" maxlength="30" placeholder="Cadre, Agent de maîtrise…">
            </x-ui.field>
        </x-ui.section>

        <x-ui.section title="2. Fourchette salariale" description="Facultatif. Sert aux comparaisons et aux statistiques.">
            <x-ui.field label="Catégorie salariale" name="form.salary_grade_id">
                <select id="form.salary_grade_id" wire:model.live="form.salary_grade_id" class="input" @disabled(! $this->canSetGrade)>
                    <option value="">Aucune : fourchette libre</option>
                    @foreach ($this->grades as $g)<option value="{{ $g->id }}">{{ $g->grid?->name }} · {{ $g->code }} {{ $g->label }}</option>@endforeach
                </select>
                @unless ($this->canSetGrade)
                    <p class="mt-1 text-xs text-ink-muted">Seuls ceux qui peuvent modifier les grilles salariales changent la catégorie d’un poste.</p>
                @endunless
            </x-ui.field>

            @if (($form['salary_grade_id'] ?? '') !== '')
                <div class="rounded-xl bg-surface-muted px-4 py-3 text-sm">
                    @if ($this->gradeRange)
                        <p class="font-medium">Base conventionnelle : {{ number_format($this->gradeRange['min'], 0, ',', ' ') }} à {{ number_format($this->gradeRange['max'], 0, ',', ' ') }} FCFA</p>
                        <p class="text-xs text-ink-muted">Du plus petit au plus grand échelon de la catégorie. Elle se met à jour quand la grille change.</p>
                    @else
                        <p class="font-medium">Cette catégorie n’a pas encore d’échelon.</p>
                        <p class="text-xs text-ink-muted">La fourchette restera vide jusqu’à l’ajout d’échelons dans la grille.</p>
                    @endif
                </div>
            @else
                <div class="grid grid-cols-2 gap-3">
                    <x-ui.field label="Minimum" name="form.salary_min"><input id="form.salary_min" type="number" min="0" wire:model="form.salary_min" class="input"></x-ui.field>
                    <x-ui.field label="Maximum" name="form.salary_max"><input id="form.salary_max" type="number" min="0" wire:model="form.salary_max" class="input"></x-ui.field>
                </div>
            @endif
        </x-ui.section>
    </div>

    <x-ui.form-bar :cancel="route('positions.index')" />
</form>
