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
            <div class="grid grid-cols-2 gap-3">
                <x-ui.field label="Minimum" name="form.salary_min"><input id="form.salary_min" type="number" min="0" wire:model="form.salary_min" class="input"></x-ui.field>
                <x-ui.field label="Maximum" name="form.salary_max"><input id="form.salary_max" type="number" min="0" wire:model="form.salary_max" class="input"></x-ui.field>
            </div>
        </x-ui.section>
    </div>

    <x-ui.form-bar :cancel="route('positions.index')" />
</form>
