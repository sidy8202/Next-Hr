<div class="space-y-6">
    <div>
        <h2 class="text-2xl font-bold">{{ $jobOffer ? 'Modifier l’offre' : 'Nouvelle offre d’emploi' }}</h2>
        <p class="text-sm text-ink-muted">Le domaine, le niveau d’études et l’expérience minimale servent à proposer automatiquement les profils de la banque de CV qui correspondent.</p>
    </div>

    <form wire:submit="save" class="space-y-6">
        <section class="rounded-2xl border border-line bg-surface p-5 shadow-sm">
            <h3 class="mb-4 text-sm font-semibold">Le poste</h3>
            <div class="grid gap-4 sm:grid-cols-2">
                <div class="sm:col-span-2">
                    <label for="title" class="mb-1 block text-sm font-medium">Intitulé de l’offre</label>
                    <input id="title" type="text" wire:model="title" class="input">
                    @error('title')<p class="mt-1 text-xs text-rose-600">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label for="department_id" class="mb-1 block text-sm font-medium">Département</label>
                    <select id="department_id" wire:model.live="department_id" class="input">
                        <option value="">Non précisé</option>
                        @foreach ($this->departments as $d)<option value="{{ $d->id }}">{{ $d->name }}</option>@endforeach
                    </select>
                    @error('department_id')<p class="mt-1 text-xs text-rose-600">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label for="position_id" class="mb-1 block text-sm font-medium">Poste du département</label>
                    <select id="position_id" wire:model="position_id" class="input" @disabled(! $department_id)>
                        <option value="">Non précisé</option>
                        @foreach ($this->positions as $p)<option value="{{ $p->id }}">{{ $p->name ?? $p->title ?? '#'.$p->id }}</option>@endforeach
                    </select>
                    @error('position_id')<p class="mt-1 text-xs text-rose-600">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label for="contract_type" class="mb-1 block text-sm font-medium">Type de contrat</label>
                    <select id="contract_type" wire:model="contract_type" class="input">
                        @foreach (config('recruitment.contract_types') as $key => $label)<option value="{{ $key }}">{{ $label }}</option>@endforeach
                    </select>
                    @error('contract_type')<p class="mt-1 text-xs text-rose-600">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label for="vacancies" class="mb-1 block text-sm font-medium">Nombre de postes</label>
                    <input id="vacancies" type="number" min="1" wire:model="vacancies" class="input">
                    @error('vacancies')<p class="mt-1 text-xs text-rose-600">{{ $message }}</p>@enderror
                </div> 
                                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">

                    {{-- Date limite : 50 % --}}
                    <div class="min-w-0">
                        <label for="closes_at" class="mb-1 block text-sm font-medium">
                            Date limite de candidature
                        </label>

                        <input
                            id="closes_at"
                            type="date"
                            wire:model="closes_at"
                            class="input w-full"
                        >

                        @error('closes_at')
                            <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Options de publication : 50 % --}}
                    <div class="grid grid-cols-3 items-center gap-3 min-w-0">

                        <div class="flex min-w-0 items-center gap-2">
                            <input
                                id="is_internal"
                                type="checkbox"
                                wire:model.live="is_internal"
                                class="shrink-0 rounded border-line text-brand-600 focus:ring-brand-500"
                            >
                            <label for="is_internal"
                                class="text-sm font-medium whitespace-nowrap cursor-pointer">
                                Publication interne
                            </label>
                        </div>

                        <div class="flex min-w-0 items-center gap-2">
                            <input
                                id="is_external"
                                type="checkbox"
                                wire:model.live="is_external"
                                class="shrink-0 rounded border-line text-brand-600 focus:ring-brand-500"
                            >
                            <label for="is_external"
                                class="text-sm font-medium whitespace-nowrap cursor-pointer">
                                Publication externe
                            </label>
                        </div>

                        <div class="flex min-w-0 items-center gap-2">
                            <input
                                id="is_both"
                                type="checkbox"
                                wire:click="toggleBoth"
                                class="shrink-0 rounded border-line text-brand-600 focus:ring-brand-500"
                            >
                            <label for="is_both"
                                class="text-sm font-medium whitespace-nowrap cursor-pointer">
                                Les deux
                            </label>
                        </div>

                    </div>
                </div>
            </div>
        </section>

        <section class="rounded-2xl border border-line bg-surface p-5 shadow-sm">
            <h3 class="mb-4 text-sm font-semibold">Profil recherché</h3>
            <div class="grid gap-4 sm:grid-cols-3">
                <div>
                    <label for="profile_category" class="mb-1 block text-sm font-medium">Domaine</label>
                    <select id="profile_category" wire:model="profile_category" class="input">
                        <option value="">Tous domaines</option>
                        @foreach (config('recruitment.profile_categories') as $cat)<option value="{{ $cat }}">{{ $cat }}</option>@endforeach
                    </select>
                    @error('profile_category')<p class="mt-1 text-xs text-rose-600">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label for="min_education_level" class="mb-1 block text-sm font-medium">Niveau d’études minimum</label>
                    <select id="min_education_level" wire:model="min_education_level" class="input">
                        <option value="">Aucun</option>
                        @foreach (config('recruitment.education_levels') as $key => $l)<option value="{{ $key }}">{{ $l['label'] }}</option>@endforeach
                    </select>
                    @error('min_education_level')<p class="mt-1 text-xs text-rose-600">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label for="min_experience_years" class="mb-1 block text-sm font-medium">Expérience minimale (années)</label>
                    <input id="min_experience_years" type="number" step="0.5" min="0" wire:model="min_experience_years" class="input">
                    @error('min_experience_years')<p class="mt-1 text-xs text-rose-600">{{ $message }}</p>@enderror
                </div>
                <div class="sm:col-span-3">
                    <label for="description" class="mb-1 block text-sm font-medium">Description du poste</label>
                    <textarea id="description" rows="4" wire:model="description" class="input"></textarea>
                    @error('description')<p class="mt-1 text-xs text-rose-600">{{ $message }}</p>@enderror
                </div>
                <div class="sm:col-span-3">
                    <label for="requirements" class="mb-1 block text-sm font-medium">Exigences et compétences</label>
                    <textarea id="requirements" rows="4" wire:model="requirements" class="input"></textarea>
                    @error('requirements')<p class="mt-1 text-xs text-rose-600">{{ $message }}</p>@enderror
                </div>
            </div>
        </section>

        <div class="flex justify-end gap-3">
            <a href="{{ route('job-offers.index') }}" wire:navigate class="inline-flex items-center rounded-xl border border-line px-4 py-2 text-sm hover:bg-surface-muted">Annuler</a>
            <button type="submit" wire:loading.attr="disabled" wire:target="save" class="inline-flex items-center rounded-xl bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-500 disabled:opacity-60">Enregistrer l’offre</button>
        </div>
    </form>
</div>
