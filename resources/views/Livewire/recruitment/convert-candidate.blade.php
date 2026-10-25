<div class="space-y-6">
    <div>
        <h2 class="text-2xl font-bold">Créer la fiche employé</h2>
        <p class="text-sm text-ink-muted">
            {{ $application->candidate?->full_name }} · offre « {{ $application->jobOffer?->title }} ».
            Le matricule, le contrat et le compte d’accès sont créés comme pour une création d’employé classique.
        </p>
    </div>

    @error('convert')
        <p class="rounded-xl bg-rose-50 px-4 py-3 text-sm text-rose-800 dark:bg-rose-500/10 dark:text-rose-200">{{ $message }}</p>
    @enderror

    @if ($result)
        <div class="space-y-3 rounded-2xl border border-line bg-surface p-5 shadow-sm">
            <p class="font-semibold">Fiche créée : {{ $result['name'] }} (matricule {{ $result['matricule'] }})</p>
            @if ($result['login'])
                @if ($result['mail'] === 'sent')
                    <p class="text-sm">Les identifiants ont été envoyés par email. Identifiant de connexion : {{ $result['login'] }}.</p>
                @else
                    <p class="rounded-xl bg-amber-50 px-4 py-3 text-sm text-amber-900 dark:bg-amber-500/10 dark:text-amber-200">
                        {{ $result['mail'] === 'failed' ? 'L’envoi de l’email a échoué.' : 'Aucune adresse email pour envoyer les identifiants.' }}
                        Communiquez ces identifiants à l’employé : <strong>{{ $result['login'] }}</strong> / mot de passe provisoire
                        <strong class="font-mono">{{ $result['password'] }}</strong> (affiché une seule fois, à changer à la première connexion).
                    </p>
                @endif
            @endif
            <a href="{{ route('job-offers.show', $application->jobOffer) }}" wire:navigate class="inline-flex items-center rounded-xl border border-line px-4 py-2 text-sm hover:bg-surface-muted">Retour à l’offre</a>
        </div>
    @elseif ($blocked)
        <p class="rounded-xl bg-amber-50 px-4 py-3 text-sm text-amber-900 dark:bg-amber-500/10 dark:text-amber-200">{{ $blocked }}</p>
        <a href="{{ route('job-offers.show', $application->jobOffer) }}" wire:navigate class="inline-flex items-center rounded-xl border border-line px-4 py-2 text-sm hover:bg-surface-muted">Retour à l’offre</a>
    @else
        <form wire:submit="save" class="space-y-6">
            <section class="rounded-2xl border border-line bg-surface p-5 shadow-sm">
                <h3 class="mb-4 text-sm font-semibold">Identité</h3>
                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <label class="mb-1 block text-sm font-medium">Genre</label>
                        <select wire:model="gender" class="input">
                            <option value="">Choisir…</option>
                            <option value="M">Homme</option>
                            <option value="F">Femme</option>
                        </select>
                        @error('gender')<p class="mt-1 text-xs text-rose-600">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-medium">Date de naissance</label>
                        <input type="date" wire:model="birth_date" class="input">
                        @error('birth_date')<p class="mt-1 text-xs text-rose-600">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-medium">Situation matrimoniale</label>
                        <select wire:model="marital_status" class="input">
                            <option value="single">Célibataire</option>
                            <option value="married">Marié(e)</option>
                            <option value="divorced">Divorcé(e)</option>
                            <option value="widowed">Veuf / veuve</option>
                        </select>
                        @error('marital_status')<p class="mt-1 text-xs text-rose-600">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-medium">Nationalité</label>
                        <input type="text" wire:model="nationality" class="input">
                        @error('nationality')<p class="mt-1 text-xs text-rose-600">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-medium">N° de pièce d’identité</label>
                        <input type="text" wire:model="national_id" class="input">
                        @error('national_id')<p class="mt-1 text-xs text-rose-600">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-medium">Téléphone</label>
                        <input type="text" wire:model="phone" class="input">
                        @error('phone')<p class="mt-1 text-xs text-rose-600">{{ $message }}</p>@enderror
                    </div>
                    <div class="sm:col-span-2">
                        <label class="mb-1 block text-sm font-medium">Email professionnel (facultatif)</label>
                        <input type="email" wire:model="work_email" class="input">
                        @error('work_email')<p class="mt-1 text-xs text-rose-600">{{ $message }}</p>@enderror
                    </div>
                </div>
            </section>

            <section class="rounded-2xl border border-line bg-surface p-5 shadow-sm">
                <h3 class="mb-4 text-sm font-semibold">Poste et contrat</h3>
                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <label class="mb-1 block text-sm font-medium">Département</label>
                        <select wire:model.live="department_id" class="input">
                            <option value="">Choisir…</option>
                            @foreach ($this->departments as $d)<option value="{{ $d->id }}">{{ $d->name }}</option>@endforeach
                        </select>
                        @error('department_id')<p class="mt-1 text-xs text-rose-600">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-medium">Poste</label>
                        <select wire:model="position_id" class="input" @disabled(! $department_id)>
                            <option value="">Choisir…</option>
                            @foreach ($this->positions as $p)<option value="{{ $p->id }}">{{ $p->name ?? $p->title ?? '#'.$p->id }}</option>@endforeach
                        </select>
                        @error('position_id')<p class="mt-1 text-xs text-rose-600">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-medium">Date d’embauche</label>
                        <input type="date" wire:model="hire_date" class="input">
                        @error('hire_date')<p class="mt-1 text-xs text-rose-600">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-medium">Type de contrat</label>
                        <select wire:model.live="contract_type" class="input">
                            <option value="CDI">CDI</option><option value="CDD">CDD</option><option value="STAGE">Stage</option>
                        </select>
                        @error('contract_type')<p class="mt-1 text-xs text-rose-600">{{ $message }}</p>@enderror
                    </div>
                    @if ($contract_type !== 'CDI')
                        <div>
                            <label class="mb-1 block text-sm font-medium">Durée (mois)</label>
                            <input type="number" min="1" wire:model="duration_months" class="input">
                            @error('duration_months')<p class="mt-1 text-xs text-rose-600">{{ $message }}</p>@enderror
                        </div>
                    @endif
                    <div>
                        <label class="mb-1 block text-sm font-medium">Salaire de base</label>
                        <input type="number" min="0" wire:model="base_salary" class="input">
                        @error('base_salary')<p class="mt-1 text-xs text-rose-600">{{ $message }}</p>@enderror
                    </div>
                </div>
            </section>

            <section class="rounded-2xl border border-line bg-surface p-5 shadow-sm">
                <h3 class="mb-4 text-sm font-semibold">Accès à l’application</h3>
                <label class="mb-3 flex items-center gap-2 text-sm"><input type="checkbox" wire:model.live="create_account"> Créer un compte de connexion et envoyer les identifiants par email</label>
                @if ($create_account)
                    <div class="max-w-md">
                        <label class="mb-1 block text-sm font-medium">Email de connexion</label>
                        <input type="email" wire:model="login_email" class="input">
                        @error('login_email')<p class="mt-1 text-xs text-rose-600">{{ $message }}</p>@enderror
                    </div>
                @endif
            </section>

            <div class="flex justify-end gap-3">
                <a href="{{ route('job-offers.show', $application->jobOffer) }}" wire:navigate class="inline-flex items-center rounded-xl border border-line px-4 py-2 text-sm hover:bg-surface-muted">Annuler</a>
                <button type="submit" wire:loading.attr="disabled" wire:target="save" class="inline-flex items-center rounded-xl bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-500 disabled:opacity-60">Créer la fiche employé</button>
            </div>
        </form>
    @endif
</div>
