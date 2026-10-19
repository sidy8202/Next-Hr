<div>
@if ($created)
    <div class="mx-auto max-w-4xl space-y-6" x-data="{
            rows: @js($credentials),
            copy() { navigator.clipboard.writeText(this.rows.map(r => `${r.matricule} | ${r.name} | ${r.login_email} | ${r.password}`).join('\n')) },
            csv() {
                const lines = ['Matricule;Nom;Identifiant;Mot de passe provisoire', ...this.rows.map(r => [r.matricule, r.name, r.login_email, r.password].join(';'))];
                const a = document.createElement('a');
                a.href = URL.createObjectURL(new Blob(['\ufeff' + lines.join('\n')], { type: 'text/csv;charset=utf-8' }));
                a.download = 'comptes-crees.csv'; a.click();
            } }">
        <div>
            <h2 class="text-2xl font-bold">{{ $createdCount }} employé(s) enregistré(s)</h2>
            <p class="text-sm text-ink-muted">Récapitulatif des {{ count($credentials) }} compte(s) créé(s), affiché <strong>une seule fois</strong>.</p>
        </div>
        <div class="rounded-xl bg-amber-50 p-4 text-sm text-amber-900 dark:bg-amber-500/10 dark:text-amber-200">
            Les mots de passe provisoires ne seront plus consultables. Chaque employé devra en choisir un nouveau à sa première connexion.
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
                                @if ($st === 'sent') <x-ui.badge tone="green">Envoyé</x-ui.badge>
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
            <x-ui.button :href="route('employees.index')" wire:navigate>Voir la liste des employés</x-ui.button>
        </div>
    </div>
@else
<form wire:submit="save" class="space-y-6">
    <div>
        <a href="{{ route('employees.index') }}" wire:navigate class="inline-flex items-center gap-1 text-sm text-ink-muted hover:text-ink">
            <x-heroicon-o-arrow-left class="size-4" /> Employés
        </a>
        <h2 class="mt-2 text-2xl font-bold">Nouvelles recrues</h2>
        <p class="text-sm text-ink-muted">Chaque ligne a sa propre date de début. Cochez « même date pour tous » si elles commencent ensemble.</p>
    </div>

    <x-ui.error-summary />
    @error('save')<div class="rounded-xl bg-rose-50 p-4 text-sm text-rose-800 dark:bg-rose-500/10 dark:text-rose-200">{{ $message }}</div>@enderror

    @if ($duplicates)
        <div class="space-y-2 rounded-xl bg-amber-50 p-4 text-sm text-amber-900 dark:bg-amber-500/10 dark:text-amber-200">
            <p class="font-semibold">Doublons possibles :</p>
            <ul class="list-disc pl-5">@foreach ($duplicates as $d)<li>{{ $d }}</li>@endforeach</ul>
            <div class="flex gap-2 pt-1">
                <x-ui.button size="sm" variant="secondary" wire:click="$set('duplicates', [])">Corriger</x-ui.button>
                <x-ui.button size="sm" wire:click="saveAnyway">Créer quand même</x-ui.button>
            </div>
        </div>
    @endif

    <div class="grid gap-6 lg:grid-cols-3">
        <div class="lg:col-span-1">
            <x-ui.section title="1. Valeurs communes" description="Valeurs par défaut des lignes.">
                <x-ui.field label="Département" name="batch.department_id" required>
                    <select id="batch.department_id" wire:model.live="batch.department_id" class="input">
                        <option value="">Choisir…</option>
                        @foreach ($this->departments as $d)<option value="{{ $d->id }}">{{ $d->code }} · {{ $d->name }}</option>@endforeach
                    </select>
                </x-ui.field>
                <label class="flex items-center gap-2 text-sm"><input type="checkbox" wire:model.live="batch.same_date"> Même date de début pour tous</label>
                <x-ui.field label="{{ ! empty($batch['same_date']) ? 'Date de début (tous)' : 'Date par défaut' }}" name="batch.hire_date" required>
                    <input id="batch.hire_date" type="date" wire:model.live="batch.hire_date" class="input">
                </x-ui.field>
                <div class="grid grid-cols-2 gap-3">
                    <x-ui.field label="Contrat" name="batch.contract_type">
                        <select id="batch.contract_type" wire:model="batch.contract_type" class="input"><option value="CDI">CDI</option><option value="CDD">CDD</option><option value="STAGE">Stage</option></select>
                    </x-ui.field>
                    <x-ui.field label="Durée (mois)" name="batch.duration_months">
                        <input id="batch.duration_months" type="number" min="1" max="60" wire:model="batch.duration_months" class="input">
                    </x-ui.field>
                </div>
                <x-ui.button variant="secondary" size="sm" wire:click="applyContractToAll">Appliquer le contrat à toutes les lignes</x-ui.button>
            </x-ui.section>
        </div>

        <div class="space-y-4 lg:col-span-2">
            <x-ui.section title="2. Les recrues ({{ count($rows) }})" description="Jusqu'à 50 lignes. Les nouvelles lignes reprennent les valeurs communes.">
                @foreach ($rows as $i => $row)
                    <div wire:key="row-{{ $i }}" class="space-y-3 rounded-xl border border-line bg-surface-muted/50 p-4">
                        <div class="flex items-center justify-between">
                            <p class="text-sm font-semibold">Recrue {{ $i + 1 }}</p>
                            <button type="button" wire:click="removeRow({{ $i }})" class="text-sm text-rose-600 hover:underline">Retirer</button>
                        </div>
                        <div class="grid gap-3 sm:grid-cols-4">
                            <div><input type="text" wire:model="rows.{{ $i }}.first_name" class="input" placeholder="Prénom" aria-label="Prénom recrue {{ $i + 1 }}"></div>
                            <div><input type="text" wire:model="rows.{{ $i }}.last_name" class="input" placeholder="Nom" aria-label="Nom recrue {{ $i + 1 }}"></div>
                            <select wire:model="rows.{{ $i }}.gender" class="input" aria-label="Sexe recrue {{ $i + 1 }}"><option value="">Sexe</option><option value="M">Homme</option><option value="F">Femme</option></select>
                            <input type="date" wire:model="rows.{{ $i }}.birth_date" class="input" aria-label="Naissance recrue {{ $i + 1 }}" title="Date de naissance">
                        </div>
                        <div class="grid gap-3 sm:grid-cols-4">
                            <select wire:model="rows.{{ $i }}.position_id" class="input sm:col-span-2" aria-label="Poste recrue {{ $i + 1 }}">
                                <option value="">Poste…</option>
                                @foreach ($this->positions as $p)<option value="{{ $p->id }}">{{ $p->title }}</option>@endforeach
                            </select>
                            <input type="number" min="0" wire:model="rows.{{ $i }}.base_salary" class="input sm:col-span-2" placeholder="Salaire de base" aria-label="Salaire recrue {{ $i + 1 }}">
                        </div>
                        <div class="grid gap-3 sm:grid-cols-4">
                            <input type="date" wire:model="rows.{{ $i }}.hire_date" @disabled(! empty($batch['same_date'])) class="input" aria-label="Date de début recrue {{ $i + 1 }}" title="Date de début">
                            <select wire:model.live="rows.{{ $i }}.contract_type" class="input" aria-label="Contrat recrue {{ $i + 1 }}"><option value="CDI">CDI</option><option value="CDD">CDD</option><option value="STAGE">Stage</option></select>
                            @if (($row['contract_type'] ?? '') !== 'CDI')
                                <input type="number" min="1" max="60" wire:model="rows.{{ $i }}.duration_months" class="input" placeholder="Durée (mois)" aria-label="Durée recrue {{ $i + 1 }}">
                            @endif
                            <label class="flex items-center gap-2 text-sm"><input type="checkbox" wire:model.live="rows.{{ $i }}.create_account"> Créer un accès</label>
                        </div>
                        @if (! empty($row['create_account']))
                            <input type="email" wire:model="rows.{{ $i }}.email" class="input" placeholder="Email : identifiant de connexion et destinataire des accès" aria-label="Email recrue {{ $i + 1 }}">
                        @endif
                        @foreach (['first_name', 'last_name', 'birth_date', 'position_id', 'hire_date', 'contract_type', 'duration_months', 'base_salary', 'email'] as $field)
                            @error("rows.$i.$field")<p class="text-xs text-rose-600">{{ $message }}</p>@enderror
                        @endforeach
                    </div>
                @endforeach
                <x-ui.button variant="secondary" wire:click="addRow" icon="plus" :disabled="count($rows) >= 50">Ajouter une ligne</x-ui.button>
            </x-ui.section>
        </div>
    </div>

    <x-ui.form-bar :cancel="route('employees.index')" target="save,saveAnyway" />
</form>
@endif
</div>
