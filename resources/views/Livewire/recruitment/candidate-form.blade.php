<div class="space-y-6">
    <div>
        <h2 class="text-2xl font-bold">{{ $candidate ? 'Modifier le profil' : 'Nouveau profil' }}</h2>
        <p class="text-sm text-ink-muted">L’ancienneté est calculée à partir des années d’expérience. L’âge n’est pas collecté.</p>
    </div>

    <form wire:submit="save" class="space-y-6">
        <section class="rounded-2xl border border-line bg-surface p-5 shadow-sm">
            <h3 class="mb-4 text-sm font-semibold">Identité et contact</h3>
            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <label for="first_name" class="mb-1 block text-sm font-medium">Prénom</label>
                    <input id="first_name" type="text" wire:model="first_name" class="input">
                    @error('first_name')<p class="mt-1 text-xs text-rose-600">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label for="last_name" class="mb-1 block text-sm font-medium">Nom</label>
                    <input id="last_name" type="text" wire:model="last_name" class="input">
                    @error('last_name')<p class="mt-1 text-xs text-rose-600">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label for="email" class="mb-1 block text-sm font-medium">Email</label>
                    <input id="email" type="email" wire:model="email" class="input" autocomplete="off">
                    @error('email')<p class="mt-1 text-xs text-rose-600">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label for="phone" class="mb-1 block text-sm font-medium">Téléphone</label>
                    <input id="phone" type="text" wire:model="phone" class="input">
                    @error('phone')<p class="mt-1 text-xs text-rose-600">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label for="city" class="mb-1 block text-sm font-medium">Ville</label>
                    <input id="city" type="text" wire:model="city" class="input">
                    @error('city')<p class="mt-1 text-xs text-rose-600">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label for="source" class="mb-1 block text-sm font-medium">Origine de la candidature</label>
                    <select id="source" wire:model="source" class="input">
                        <option value="">Non précisée</option>
                        @foreach (config('recruitment.sources') as $key => $label)<option value="{{ $key }}">{{ $label }}</option>@endforeach
                    </select>
                    @error('source')<p class="mt-1 text-xs text-rose-600">{{ $message }}</p>@enderror
                </div>
            </div>
        </section>

        <section class="rounded-2xl border border-line bg-surface p-5 shadow-sm">
            <h3 class="mb-4 text-sm font-semibold">Formation et expérience</h3>
            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <label for="education_level" class="mb-1 block text-sm font-medium">Dernier diplôme (niveau)</label>
                    <select id="education_level" wire:model="education_level" class="input">
                        @foreach (config('recruitment.education_levels') as $key => $l)<option value="{{ $key }}">{{ $l['label'] }}</option>@endforeach
                    </select>
                    @error('education_level')<p class="mt-1 text-xs text-rose-600">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label for="last_degree" class="mb-1 block text-sm font-medium">Intitulé du diplôme</label>
                    <input id="last_degree" type="text" wire:model="last_degree" class="input" placeholder="ex. Licence en informatique">
                    @error('last_degree')<p class="mt-1 text-xs text-rose-600">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label for="degree_field" class="mb-1 block text-sm font-medium">Spécialité</label>
                    <input id="degree_field" type="text" wire:model="degree_field" class="input">
                    @error('degree_field')<p class="mt-1 text-xs text-rose-600">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label for="school" class="mb-1 block text-sm font-medium">École / université</label>
                    <input id="school" type="text" wire:model="school" class="input">
                    @error('school')<p class="mt-1 text-xs text-rose-600">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label for="graduation_year" class="mb-1 block text-sm font-medium">Année d’obtention</label>
                    <input id="graduation_year" type="number" wire:model="graduation_year" class="input" min="1950" max="{{ date('Y') + 1 }}">
                    @error('graduation_year')<p class="mt-1 text-xs text-rose-600">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label for="experience_years" class="mb-1 block text-sm font-medium">Années d’expérience</label>
                    <input id="experience_years" type="number" step="0.5" min="0" max="60" wire:model="experience_years" class="input">
                    @error('experience_years')<p class="mt-1 text-xs text-rose-600">{{ $message }}</p>@enderror
                </div>
                <div class="sm:col-span-2">
                    <label for="current_job_title" class="mb-1 block text-sm font-medium">Poste actuel ou dernier poste</label>
                    <input id="current_job_title" type="text" wire:model="current_job_title" class="input">
                    @error('current_job_title')<p class="mt-1 text-xs text-rose-600">{{ $message }}</p>@enderror
                </div>
            </div>
        </section>

        <section class="rounded-2xl border border-line bg-surface p-5 shadow-sm">
            <h3 class="mb-4 text-sm font-semibold">Profil</h3>
            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <label for="profile_category" class="mb-1 block text-sm font-medium">Domaine</label>
                    <select id="profile_category" wire:model="profile_category" class="input">
                        <option value="">Choisir…</option>
                        @foreach (config('recruitment.profile_categories') as $cat)<option value="{{ $cat }}">{{ $cat }}</option>@endforeach
                    </select>
                    @error('profile_category')<p class="mt-1 text-xs text-rose-600">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label for="skills" class="mb-1 block text-sm font-medium">Compétences (séparées par des virgules)</label>
                    <input id="skills" type="text" wire:model="skills" class="input" placeholder="excel, réseaux, sql">
                    @error('skills')<p class="mt-1 text-xs text-rose-600">{{ $message }}</p>@enderror
                </div>
                <div class="sm:col-span-2">
                    <label for="notes" class="mb-1 block text-sm font-medium">Notes internes</label>
                    <textarea id="notes" rows="3" wire:model="notes" class="input"></textarea>
                    @error('notes')<p class="mt-1 text-xs text-rose-600">{{ $message }}</p>@enderror
                </div>
            </div>
        </section>

        <section class="rounded-2xl border border-line bg-surface p-5 shadow-sm">
            <h3 class="mb-4 text-sm font-semibold">CV et consentement</h3>
            <div class="space-y-4">
                @if ($candidate?->cv_path)
                    <div class="flex flex-wrap items-center gap-3 text-sm">
                        <a href="{{ route('candidates.cv', $candidate) }}" class="rounded border border-line px-2 py-1 hover:bg-surface-muted">CV actuel : {{ $candidate->cv_original_name ?: 'télécharger' }}</a>
                        <label class="flex items-center gap-2"><input type="checkbox" wire:model="remove_cv"> Supprimer le CV</label>
                    </div>
                @endif
                <div>
                    <label for="cv" class="mb-1 block text-sm font-medium">{{ $candidate?->cv_path ? 'Remplacer le CV' : 'CV' }} (PDF, DOC, DOCX, {{ config('recruitment.cv.max_kb') / 1024 }} Mo max)</label>
                    <input id="cv" type="file" wire:model="cv" accept=".pdf,.doc,.docx" class="block w-full text-sm">
                    <p wire:loading wire:target="cv" class="mt-1 text-xs text-ink-muted">Envoi du fichier…</p>
                    @error('cv')<p class="mt-1 text-xs text-rose-600">{{ $message }}</p>@enderror
                </div>
                @if (! $candidate)
                    <div>
                        <label class="flex items-start gap-2 text-sm">
                            <input type="checkbox" wire:model="consent" class="mt-1">
                            <span>Le candidat a accepté que son CV et ses informations soient conservés dans la banque de CV pendant {{ config('recruitment.retention_months') }} mois.</span>
                        </label>
                        @error('consent')<p class="mt-1 text-xs text-rose-600">{{ $message }}</p>@enderror
                    </div>
                @endif
            </div>
        </section>

        <div class="flex justify-end gap-3">
            <a href="{{ route('candidates.index') }}" wire:navigate class="inline-flex items-center rounded-xl border border-line px-4 py-2 text-sm hover:bg-surface-muted">Annuler</a>
            <button type="submit" wire:loading.attr="disabled" wire:target="save,cv" class="inline-flex items-center rounded-xl bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-500 disabled:opacity-60">Enregistrer le profil</button>
        </div>
    </form>
</div>
