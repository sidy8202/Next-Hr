<form wire:submit="save" class="space-y-6">
    <div>
        <a href="{{ $departmentId ? route('departments.show', $departmentId) : route('departments.index') }}" wire:navigate class="inline-flex items-center gap-1 text-sm text-ink-muted hover:text-ink">
            <x-heroicon-o-arrow-left class="size-4" /> {{ $departmentId ? 'Retour à la fiche' : 'Départements' }}
        </a>
        <h2 class="mt-2 text-2xl font-bold">{{ $departmentId ? 'Modifier le département' : 'Nouveau département' }}</h2>
        <p class="text-sm text-ink-muted">Seuls le nom et le code sont obligatoires. Le chef, les membres et les postes sont facultatifs.</p>
    </div>

    <x-ui.error-summary />

    <div class="grid gap-6 lg:grid-cols-2">
        {{-- Colonne gauche --}}
        <div class="space-y-6">
            <x-ui.section title="1. Informations">
                <x-ui.field label="Nom" name="form.name" required>
                    <input id="form.name" type="text" wire:model="form.name" class="input" maxlength="120" autofocus>
                </x-ui.field>
                <div class="grid grid-cols-2 gap-3">
                    <x-ui.field label="Code" name="form.code" required hint="Court et unique (ex. FIN).">
                        <input id="form.code" type="text" wire:model="form.code" class="input uppercase" maxlength="20">
                    </x-ui.field>
                    <label class="mt-7 flex items-center gap-2 text-sm"><input type="checkbox" wire:model="form.is_active"> Département actif</label>
                </div>
            </x-ui.section>

            <x-ui.section title="2. Chef de département" description="Facultatif. Choisissez-le par son code.">
                <livewire:employee-lookup wire:model="form.head_id" target="head" wire:key="lookup-head" />
                @error('form.head_id')<p class="text-sm text-rose-600">{{ $message }}</p>@enderror
            </x-ui.section>
        </div>

        {{-- Colonne droite --}}
        <div class="space-y-6">
            <x-ui.section title="3. Membres à rattacher" :description="$departmentId ? 'Facultatif. Ce département compte déjà '.$memberCount.' employé(s).' : 'Facultatif. Employés existants à placer dans ce département.'">
                @foreach ($memberEmployees as $member)
                    <div wire:key="member-{{ $member->id }}" class="flex items-center justify-between gap-2 rounded-lg bg-surface-muted px-3 py-2 text-sm">
                        <span>
                            <span class="font-semibold">{{ $member->matricule }}</span> · {{ $member->full_name }}
                            @if ($member->department_id !== $departmentId)
                                <span class="ml-1 text-xs text-amber-600">sera déplacé depuis {{ $member->department?->code }}</span>
                            @endif
                        </span>
                        <button type="button" wire:click="removeMember({{ $member->id }})" class="text-ink-muted hover:text-ink" aria-label="Retirer {{ $member->full_name }}">
                            <x-heroicon-o-x-mark class="size-4" />
                        </button>
                    </div>
                @endforeach
                <livewire:employee-lookup wire:model="newMemberId" target="member" placeholder="Ajouter un employé par code ou nom…" wire:key="lookup-member" />
                @error('form.member_ids.*')<p class="text-sm text-rose-600">{{ $message }}</p>@enderror
            </x-ui.section>

            <x-ui.section title="4. Postes" :description="$departmentId ? 'Les postes sont ajoutés et retirés immédiatement.' : 'Facultatif. Ils seront créés avec le département.'">
                <div class="flex flex-wrap gap-2">
                    @foreach ($existingPositions as $p)
                        <span wire:key="pos-{{ $p->id }}" class="inline-flex items-center gap-1 rounded-full bg-surface-muted px-3 py-1 text-xs font-medium">
                            {{ $p->title }} <span class="text-ink-muted">({{ $p->employees_count }})</span>
                            <button type="button" wire:click="deletePosition({{ $p->id }})" wire:confirm="Supprimer le poste « {{ $p->title }} » ?" aria-label="Supprimer {{ $p->title }}" class="text-ink-muted hover:text-rose-600">&times;</button>
                        </span>
                    @endforeach
                    @foreach ($form['positions'] as $i => $title)
                        <span wire:key="staged-{{ $i }}" class="inline-flex items-center gap-1 rounded-full bg-brand-100 px-3 py-1 text-xs font-medium text-brand-800 dark:bg-brand-500/20 dark:text-brand-200">
                            {{ $title }} <span class="opacity-70">(en attente)</span>
                            <button type="button" wire:click="removeStagedPosition({{ $i }})" aria-label="Retirer {{ $title }}">&times;</button>
                        </span>
                    @endforeach
                    @if ($existingPositions->isEmpty() && ! $form['positions'])
                        <p class="text-sm text-ink-muted">Aucun poste pour le moment.</p>
                    @endif
                </div>
                <div class="flex gap-2">
                    <input type="text" wire:model="newPosition" wire:keydown.enter.prevent="addPosition" class="input" placeholder="Intitulé du poste, puis Entrée" maxlength="120" aria-label="Nouveau poste">
                    <x-ui.button variant="secondary" wire:click="addPosition" icon="plus">Ajouter</x-ui.button>
                </div>
                @error('newPosition')<p class="text-sm text-rose-600">{{ $message }}</p>@enderror
            </x-ui.section>
        </div>
    </div>

    <x-ui.form-bar :cancel="$departmentId ? route('departments.show', $departmentId) : route('departments.index')" />
</form>
