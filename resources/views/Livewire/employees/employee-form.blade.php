<div>
@if ($created)
    {{-- ===== Récapitulatif après création avec accès (affiché une seule fois) ===== --}}
    <div class="mx-auto max-w-3xl space-y-6" x-data="{
            rows: @js($credentials),
            copy() { navigator.clipboard.writeText(this.rows.map(r => `${r.matricule} | ${r.name} | ${r.login_email} | ${r.password}`).join('\n')) },
            csv() {
                const lines = ['Matricule;Nom;Identifiant;Mot de passe provisoire', ...this.rows.map(r => [r.matricule, r.name, r.login_email, r.password].join(';'))];
                const a = document.createElement('a');
                a.href = URL.createObjectURL(new Blob(['\ufeff' + lines.join('\n')], { type: 'text/csv;charset=utf-8' }));
                a.download = 'comptes-crees.csv'; a.click();
            } }">
        <div>
            <h2 class="text-2xl font-bold">Employé enregistré</h2>
            <p class="text-sm text-ink-muted">Le compte a été créé. Voici le récapitulatif, affiché <strong>une seule fois</strong>.</p>
        </div>

        <div class="rounded-xl bg-amber-50 p-4 text-sm text-amber-900 dark:bg-amber-500/10 dark:text-amber-200">
            Le mot de passe provisoire n'est plus consultable après cette page. L'employé devra en choisir un nouveau à sa première connexion.
        </div>

        <div class="overflow-x-auto rounded-2xl border border-line bg-surface">
            <table class="w-full text-left text-sm">
                <thead class="bg-surface-muted text-xs uppercase tracking-wide text-ink-muted">
                    <tr><th class="px-4 py-3">Code</th><th class="px-4 py-3">Employé</th><th class="px-4 py-3">Identifiant</th><th class="px-4 py-3">Mot de passe</th><th class="px-4 py-3">Email</th></tr>
                </thead>
                <tbody>
                    @foreach ($credentials as $c)
                        @php($st = $mailStatus[$c['matricule']] ?? 'none')
                        <tr class="border-t border-line">
                            <td class="px-4 py-3 font-semibold">{{ $c['matricule'] }}</td>
                            <td class="px-4 py-3">{{ $c['name'] }}</td>
                            <td class="px-4 py-3">{{ $c['login_email'] }}</td>
                            <td class="px-4 py-3 font-mono">{{ $c['password'] }}</td>
                            <td class="px-4 py-3">
                                @if ($st === 'sent') <x-ui.badge tone="green">Envoyé à {{ count($c['send_to']) }} adresse(s)</x-ui.badge>
                                @elseif ($st === 'failed') <x-ui.badge tone="red">Échec de l'envoi</x-ui.badge>
                                @else <x-ui.badge tone="gray">Non envoyé</x-ui.badge> @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="flex flex-wrap gap-2">
            <x-ui.button variant="secondary" type="button" @click="copy()" icon="clipboard-document">Copier</x-ui.button>
            <x-ui.button variant="secondary" type="button" @click="csv()" icon="arrow-down-tray">Télécharger en CSV</x-ui.button>
            <div class="flex-1"></div>
            <x-ui.button variant="secondary" :href="route('employees.create')" wire:navigate>Ajouter un autre employé</x-ui.button>
            <x-ui.button :href="route('employees.show', $createdId)" wire:navigate>Voir la fiche</x-ui.button>
        </div>
    </div>
@else
<form wire:submit="save" class="space-y-6">
    <div>
        <a href="{{ $employeeId ? route('employees.show', $employeeId) : route('employees.index') }}" wire:navigate class="inline-flex items-center gap-1 text-sm text-ink-muted hover:text-ink">
            <x-heroicon-o-arrow-left class="size-4" /> {{ $employeeId ? 'Retour à la fiche' : 'Employés' }}
        </a>
        <h2 class="mt-2 text-2xl font-bold">{{ $employeeId ? 'Modifier l’employé' : 'Nouvel employé' }}</h2>
        @unless ($employeeId)
            <p class="text-sm text-ink-muted">Pour plusieurs recrues à la fois, utilisez plutôt « Nouvelles recrues ».</p>
        @endunless
    </div>

    <x-ui.error-summary />

    @if ($duplicates)
        <div class="space-y-2 rounded-xl bg-amber-50 p-4 text-sm text-amber-900 dark:bg-amber-500/10 dark:text-amber-200">
            <p class="font-semibold">Doublon possible :</p>
            <ul class="list-disc pl-5">@foreach ($duplicates as $d)<li>{{ $d }}</li>@endforeach</ul>
            <div class="flex gap-2 pt-1">
                <x-ui.button size="sm" variant="secondary" wire:click="$set('duplicates', [])">Corriger</x-ui.button>
                <x-ui.button size="sm" wire:click="saveAnyway">Créer quand même</x-ui.button>
            </div>
        </div>
    @endif

    <div class="grid gap-6 lg:grid-cols-3">
        {{-- ============ Colonne principale ============ --}}
        <div class="space-y-6 lg:col-span-2">
            <x-ui.section title="1. Identité">
                <div class="grid gap-4 sm:grid-cols-2">
                    <x-ui.field label="Prénom" name="form.first_name" required><input id="form.first_name" type="text" wire:model="form.first_name" class="input" autofocus></x-ui.field>
                    <x-ui.field label="Nom" name="form.last_name" required><input id="form.last_name" type="text" wire:model="form.last_name" class="input"></x-ui.field>
                    <x-ui.field label="Sexe" name="form.gender">
                        <select id="form.gender" wire:model="form.gender" class="input"><option value="">—</option><option value="M">Homme</option><option value="F">Femme</option></select>
                    </x-ui.field>
                    <x-ui.field label="Date de naissance" name="form.birth_date"><input id="form.birth_date" type="date" wire:model="form.birth_date" class="input"></x-ui.field>
                    <x-ui.field label="Lieu de naissance" name="form.birth_place"><input id="form.birth_place" type="text" wire:model="form.birth_place" class="input"></x-ui.field>
                    <x-ui.field label="Nationalité" name="form.nationality"><input id="form.nationality" type="text" wire:model="form.nationality" class="input"></x-ui.field>
                    <x-ui.field label="N° pièce d'identité" name="form.national_id"><input id="form.national_id" type="text" wire:model="form.national_id" class="input"></x-ui.field>
                    <label class="flex items-center gap-2 pt-7 text-sm"><input type="checkbox" wire:model.live="form.is_expatriate"> Employé expatrié</label>
                    @if ($form['is_expatriate'] ?? false)
                        <x-ui.field label="N° permis de travail" name="form.work_permit_number"><input id="form.work_permit_number" type="text" wire:model="form.work_permit_number" class="input"></x-ui.field>
                        <x-ui.field label="Expiration du permis" name="form.work_permit_expires_at"><input id="form.work_permit_expires_at" type="date" wire:model="form.work_permit_expires_at" class="input"></x-ui.field>
                    @endif
                </div>
            </x-ui.section>

            <x-ui.section title="2. Contact" description="Les deux adresses email servent aussi à envoyer les identifiants de connexion.">
                <div class="grid gap-4 sm:grid-cols-2">
                    <x-ui.field label="Email professionnel" name="form.work_email"><input id="form.work_email" type="email" wire:model="form.work_email" class="input"></x-ui.field>
                    <x-ui.field label="Email personnel" name="form.personal_email"><input id="form.personal_email" type="email" wire:model="form.personal_email" class="input"></x-ui.field>
                    <x-ui.field label="Téléphone" name="form.phone"><input id="form.phone" type="text" wire:model="form.phone" class="input"></x-ui.field>
                    <x-ui.field label="Adresse" name="form.address"><input id="form.address" type="text" wire:model="form.address" class="input"></x-ui.field>
                    <x-ui.field label="Contact d'urgence" name="form.emergency_contact_name"><input id="form.emergency_contact_name" type="text" wire:model="form.emergency_contact_name" class="input"></x-ui.field>
                    <x-ui.field label="Téléphone d'urgence" name="form.emergency_contact_phone"><input id="form.emergency_contact_phone" type="text" wire:model="form.emergency_contact_phone" class="input"></x-ui.field>
                </div>
            </x-ui.section>

            <x-ui.section :title="$employeeId ? '3. Emploi' : '3. Emploi et contrat'">
                <div class="grid gap-4 sm:grid-cols-2">
                    <x-ui.field label="Département" name="form.department_id" required>
                        <select id="form.department_id" wire:model.live="form.department_id" class="input">
                            <option value="">Choisir…</option>
                            @foreach ($this->departments as $d)<option value="{{ $d->id }}">{{ $d->code }} · {{ $d->name }}</option>@endforeach
                        </select>
                    </x-ui.field>
                    <x-ui.field label="Poste" name="form.position_id" required>
                        <select id="form.position_id" wire:model="form.position_id" class="input">
                            <option value="">{{ ($form['department_id'] ?? null) ? 'Choisir…' : 'Choisissez d’abord un département' }}</option>
                            @foreach ($this->positions as $p)<option value="{{ $p->id }}">{{ $p->title }}</option>@endforeach
                        </select>
                    </x-ui.field>
                    <x-ui.field label="Date d'embauche" name="form.hire_date" required><input id="form.hire_date" type="date" wire:model="form.hire_date" class="input"></x-ui.field>

                    @if ($employeeId)
                        <div>
                            <p class="mb-1 text-sm font-medium">Manager (supérieur direct)</p>
                            <livewire:employee-lookup wire:model="form.manager_id" target="manager" :allow-create="false" wire:key="lookup-manager" />
                            @error('form.manager_id')<p class="mt-1 text-sm text-rose-600">{{ $message }}</p>@enderror
                        </div>
                    @else
                        <x-ui.field label="Type de contrat" name="form.contract_type" required>
                            <select id="form.contract_type" wire:model.live="form.contract_type" class="input"><option value="CDI">CDI</option><option value="CDD">CDD</option><option value="STAGE">Stage</option></select>
                        </x-ui.field>
                        @if (($form['contract_type'] ?? '') !== 'CDI')
                            <x-ui.field label="Durée (mois)" name="form.duration_months" required><input id="form.duration_months" type="number" min="1" max="60" wire:model="form.duration_months" class="input"></x-ui.field>
                        @endif
                        <x-ui.field label="Salaire de base" name="form.base_salary" required><input id="form.base_salary" type="number" min="0" wire:model="form.base_salary" class="input"></x-ui.field>

                        <div>
                            <label for="leave_days_per_month" class="mb-1 block text-sm font-medium">Jours de congé acquis par mois</label>
                            <input id="leave_days_per_month" type="number" step="0.25" min="0" max="10" wire:model="form.leave_days_per_month" class="input" placeholder="2,5">
                            <p class="mt-1 text-xs text-ink-muted">Cumulé à partir de la date d’embauche. Vide = valeur par défaut de l’entreprise (2,5).</p>
                            @error('form.leave_days_per_month')<p class="mt-1 text-xs text-rose-600">{{ $message }}</p>@enderror
                        </div>
                        
                    @endif
                </div>
                @unless ($employeeId)
                    <p class="text-xs text-ink-muted">Le manager est automatiquement le chef du département choisi (modifiable ensuite).</p>
                @endunless
            </x-ui.section>

            <x-ui.section title="4. Pièces jointes" description="PDF, JPG ou PNG, 5 Mo maximum par fichier. Facultatif.">
                @if ($existingDocs->isNotEmpty())
                    <ul class="divide-y divide-line rounded-xl border border-line">
                        @foreach ($existingDocs as $doc)
                            <li wire:key="doc-existing-{{ $doc->id }}" class="flex items-center justify-between gap-3 px-3 py-2 text-sm">
                                <a href="{{ route('employees.documents.download', [$employeeId, $doc->id]) }}" class="font-medium text-brand-700 hover:underline dark:text-brand-300">{{ $doc->title }}</a>
                                <span class="text-ink-muted">{{ config("documents.types.{$doc->type}.label", $doc->type) }}</span>
                                <button type="button" wire:click="deleteDocument({{ $doc->id }})" wire:confirm="Supprimer cette pièce ?" class="text-rose-600 hover:underline">Supprimer</button>
                            </li>
                        @endforeach
                    </ul>
                @endif

                @if ($this->uploadableTypes)
                    @foreach ($docs as $i => $doc)
                        <div wire:key="doc-row-{{ $i }}" class="space-y-1">
                            <div class="grid gap-2 sm:grid-cols-12">
                                <select wire:model="docs.{{ $i }}.type" class="input sm:col-span-4" aria-label="Type de pièce {{ $i + 1 }}">
                                    <option value="">Type de pièce…</option>
                                    @foreach ($this->uploadableTypes as $key => $label)<option value="{{ $key }}">{{ $label }}</option>@endforeach
                                </select>
                                <input type="text" wire:model="docs.{{ $i }}.title" class="input sm:col-span-3" placeholder="Libellé (facultatif)" aria-label="Libellé {{ $i + 1 }}">
                                <input type="file" wire:model="docs.{{ $i }}.file" accept=".pdf,.jpg,.jpeg,.png" aria-label="Fichier {{ $i + 1 }}"
                                       class="block w-full text-sm sm:col-span-4 file:mr-3 file:rounded-lg file:border-0 file:bg-brand-50 file:px-3 file:py-2 file:text-sm file:font-medium file:text-brand-700">
                                <button type="button" wire:click="removeDoc({{ $i }})" class="text-left text-sm text-rose-600 hover:underline sm:col-span-1">Retirer</button>
                            </div>
                            <div wire:loading wire:target="docs.{{ $i }}.file" class="text-xs text-ink-muted">Téléversement…</div>
                        </div>
                    @endforeach
                    <x-ui.button variant="secondary" wire:click="addDoc" icon="plus" :disabled="count($docs) >= 10">Ajouter une pièce jointe</x-ui.button>
                @else
                    <p class="text-sm text-ink-muted">Vous n'avez pas le droit d'ajouter de pièces jointes.</p>
                @endif
            </x-ui.section>
        </div>

        {{-- ============ Colonne latérale ============ --}}
        <div class="space-y-6">
            <x-ui.section title="5. Famille" description="Utilisée pour les déductions de paie.">
                <x-ui.field label="Situation matrimoniale" name="form.marital_status">
                    <select id="form.marital_status" wire:model.live="form.marital_status" class="input">
                        <option value="">—</option><option value="single">Célibataire</option><option value="married">Marié(e)</option><option value="divorced">Divorcé(e)</option><option value="widowed">Veuf(ve)</option>
                    </select>
                </x-ui.field>
                @if (($form['marital_status'] ?? '') === 'married')
                    <label class="flex items-center gap-2 text-sm"><input type="checkbox" wire:model="form.spouse_works"> Le conjoint travaille</label>
                @endif
                <x-ui.field label="Enfants à charge" name="form.children_count"><input id="form.children_count" type="number" min="0" max="30" wire:model="form.children_count" class="input"></x-ui.field>
            </x-ui.section>

            @unless ($employeeId)
                <x-ui.section title="6. Accès à l'application" description="Un mot de passe provisoire est généré. Facultatif.">
                    <label class="flex items-center gap-2 text-sm"><input type="checkbox" wire:model.live="form.create_account"> Créer un compte pour cet employé</label>
                    @if ($form['create_account'] ?? false)
                        <x-ui.field label="Adresse de connexion" name="form.login_email_source">
                            <select id="form.login_email_source" wire:model="form.login_email_source" class="input">
                                <option value="work">Email professionnel{{ ($form['work_email'] ?? '') ? ' : '.$form['work_email'] : '' }}</option>
                                <option value="personal">Email personnel{{ ($form['personal_email'] ?? '') ? ' : '.$form['personal_email'] : '' }}</option>
                            </select>
                        </x-ui.field>
                        <div>
                            <p class="mb-1 text-sm font-medium">Envoyer les identifiants à</p>
                            <label class="flex items-center gap-2 text-sm"><input type="checkbox" wire:model="form.send_work" @disabled(! ($form['work_email'] ?? ''))> l'email professionnel</label>
                            <label class="mt-1 flex items-center gap-2 text-sm"><input type="checkbox" wire:model="form.send_personal" @disabled(! ($form['personal_email'] ?? ''))> l'email personnel</label>
                            <p class="mt-1 text-xs text-ink-muted">Au maximum 2 adresses. Sans case cochée, les identifiants s'affichent seulement à l'écran.</p>
                        </div>
                    @endif
                </x-ui.section>
            @endunless
        </div>
    </div>

    <x-ui.form-bar :cancel="$employeeId ? route('employees.show', $employeeId) : route('employees.index')" target="save,saveAnyway" />
</form>
@endif
</div>
