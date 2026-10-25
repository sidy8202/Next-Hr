<div>
    @if ($done)
        <div class="rounded-2xl border border-line bg-surface p-6 text-center shadow-sm">
            <p class="text-lg font-semibold">Merci, votre candidature a bien été envoyée.</p>
            <p class="mt-2 text-sm text-ink-muted">Un email de confirmation vous a été adressé. Nous vous recontacterons si votre profil correspond au poste.</p>
        </div>
    @else
        @error('offer')
            <p class="mb-4 rounded-xl bg-rose-50 px-4 py-3 text-sm text-rose-800 dark:bg-rose-500/10 dark:text-rose-200">{{ $message }}</p>
        @enderror

        <form wire:submit="submit" class="space-y-6">
            {{-- Champ piège anti-robots : ne pas remplir --}}
            <div class="absolute -left-[9999px]" aria-hidden="true">
                <label>Ne pas remplir <input type="text" wire:model="website" tabindex="-1" autocomplete="off"></label>
            </div>

            <section class="rounded-2xl border border-line bg-surface p-5 shadow-sm">
                <h3 class="mb-4 text-sm font-semibold">Vos coordonnées</h3>
                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <label for="first_name" class="mb-1 block text-sm font-medium">Prénom</label>
                        <input id="first_name" type="text" wire:model="first_name" class="input" autocomplete="given-name">
                        @error('first_name')<p class="mt-1 text-xs text-rose-600">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label for="last_name" class="mb-1 block text-sm font-medium">Nom</label>
                        <input id="last_name" type="text" wire:model="last_name" class="input" autocomplete="family-name">
                        @error('last_name')<p class="mt-1 text-xs text-rose-600">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label for="email" class="mb-1 block text-sm font-medium">Email</label>
                        <input id="email" type="email" wire:model="email" class="input" autocomplete="email">
                        @error('email')<p class="mt-1 text-xs text-rose-600">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label for="phone" class="mb-1 block text-sm font-medium">Téléphone</label>
                        <input id="phone" type="text" wire:model="phone" class="input" autocomplete="tel">
                        @error('phone')<p class="mt-1 text-xs text-rose-600">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label for="city" class="mb-1 block text-sm font-medium">Ville</label>
                        <input id="city" type="text" wire:model="city" class="input">
                        @error('city')<p class="mt-1 text-xs text-rose-600">{{ $message }}</p>@enderror
                    </div>
                </div>
            </section>

            <section class="rounded-2xl border border-line bg-surface p-5 shadow-sm">
                <h3 class="mb-4 text-sm font-semibold">Votre parcours</h3>
                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <label for="education_level" class="mb-1 block text-sm font-medium">Dernier diplôme (niveau)</label>
                        <select id="education_level" wire:model="education_level" class="input">
                            <option value="">Choisir…</option>
                            @foreach (config('recruitment.education_levels') as $key => $l)<option value="{{ $key }}">{{ $l['label'] }}</option>@endforeach
                        </select>
                        @error('education_level')<p class="mt-1 text-xs text-rose-600">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label for="last_degree" class="mb-1 block text-sm font-medium">Intitulé du diplôme</label>
                        <input id="last_degree" type="text" wire:model="last_degree" class="input">
                        @error('last_degree')<p class="mt-1 text-xs text-rose-600">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label for="experience_years" class="mb-1 block text-sm font-medium">Années d’expérience</label>
                        <input id="experience_years" type="number" step="0.5" min="0" max="60" wire:model="experience_years" class="input">
                        @error('experience_years')<p class="mt-1 text-xs text-rose-600">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label for="current_job_title" class="mb-1 block text-sm font-medium">Poste actuel ou dernier poste</label>
                        <input id="current_job_title" type="text" wire:model="current_job_title" class="input">
                        @error('current_job_title')<p class="mt-1 text-xs text-rose-600">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label for="profile_category" class="mb-1 block text-sm font-medium">Domaine principal</label>
                        <select id="profile_category" wire:model="profile_category" class="input">
                            <option value="">Choisir…</option>
                            @foreach (config('recruitment.profile_categories') as $cat)<option value="{{ $cat }}">{{ $cat }}</option>@endforeach
                        </select>
                        @error('profile_category')<p class="mt-1 text-xs text-rose-600">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label for="skills" class="mb-1 block text-sm font-medium">Compétences (séparées par des virgules)</label>
                        <input id="skills" type="text" wire:model="skills" class="input" placeholder="ex. excel, réseaux, sql">
                        @error('skills')<p class="mt-1 text-xs text-rose-600">{{ $message }}</p>@enderror
                    </div>
                </div>
            </section>

            <section class="rounded-2xl border border-line bg-surface p-5 shadow-sm">
                <h3 class="mb-4 text-sm font-semibold">Vos documents</h3>
                <div class="space-y-4">
                    <div>
                        <label for="cv" class="mb-1 block text-sm font-medium">CV (PDF, DOC ou DOCX, {{ config('recruitment.cv.max_kb') / 1024 }} Mo maximum)</label>
                        <input id="cv" type="file" wire:model="cv" accept=".pdf,.doc,.docx" class="block w-full text-sm">
                        <p wire:loading wire:target="cv" class="mt-1 text-xs text-ink-muted">Envoi du fichier…</p>
                        @error('cv')<p class="mt-1 text-xs text-rose-600">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label for="letter" class="mb-1 block text-sm font-medium">Lettre de motivation (facultatif)</label>
                        <input id="letter" type="file" wire:model="letter" accept=".pdf,.doc,.docx" class="block w-full text-sm">
                        <p wire:loading wire:target="letter" class="mt-1 text-xs text-ink-muted">Envoi du fichier…</p>
                        @error('letter')<p class="mt-1 text-xs text-rose-600">{{ $message }}</p>@enderror
                    </div>
                </div>
            </section>

            <section class="rounded-2xl border border-line bg-surface p-5 shadow-sm">
                <h3 class="mb-4 text-sm font-semibold">Vos données personnelles</h3>
                <div class="space-y-3 text-sm">
                    <label class="flex items-start gap-2">
                        <input type="checkbox" wire:model="consent_process" class="mt-1">
                        <span>J’accepte que mes informations et mon CV soient utilisés pour étudier ma candidature à ce poste. <span class="text-ink-muted">(obligatoire)</span></span>
                    </label>
                    @error('consent_process')<p class="text-xs text-rose-600">{{ $message }}</p>@enderror

                    <label class="flex items-start gap-2">
                        <input type="checkbox" wire:model="consent_bank" class="mt-1">
                        <span>J’accepte que mon CV soit conservé {{ config('recruitment.retention_months') }} mois dans la banque de CV de l’entreprise pour d’autres opportunités. <span class="text-ink-muted">(facultatif)</span></span>
                    </label>
                    <p class="text-xs text-ink-muted">Vous pouvez demander la suppression de vos données à tout moment en contactant l’entreprise. Sans cette seconde case, vos données ne sont conservées que pour le processus de recrutement, puis supprimées.</p>
                </div>
            </section>

            <div class="flex justify-end">
                <button type="submit" wire:loading.attr="disabled" wire:target="submit,cv,letter" class="inline-flex items-center rounded-xl bg-indigo-600 px-5 py-2.5 text-sm font-semibold text-white hover:bg-indigo-500 disabled:opacity-60">Envoyer ma candidature</button>
            </div>
        </form>
    @endif
</div>
