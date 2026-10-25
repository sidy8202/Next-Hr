<div class="space-y-6">
    <a href="{{ route('employees.index') }}" wire:navigate class="inline-flex items-center gap-1 text-sm text-ink-muted hover:text-ink">
        <x-heroicon-o-arrow-left class="size-4" /> Employés
    </a>

    @php
        $statusLabels = ['active' => ['Actif', 'green'], 'suspended' => ['Suspendu', 'amber'], 'terminated' => ['Sorti', 'gray']];
        $today = $employee->status !== 'active'
            ? $statusLabels[$employee->status]
            : (in_array($employee->id, $status['leave'], true) ? ['En congé', 'amber'] : (in_array($employee->id, $status['mission'], true) ? ['En mission', 'sky'] : ['Présent', 'green']));
        $marital = ['single' => 'Célibataire', 'married' => 'Marié(e)', 'divorced' => 'Divorcé(e)', 'widowed' => 'Veuf(ve)'];
    @endphp

    <div class="flex flex-wrap items-start justify-between gap-4">
        <div class="flex items-center gap-4">
            <span class="flex size-14 items-center justify-center rounded-2xl bg-brand-600 text-lg font-semibold text-white">{{ Str::upper(Str::substr($employee->first_name, 0, 1).Str::substr($employee->last_name, 0, 1)) }}</span>
            <div>
                <div class="flex flex-wrap items-center gap-2">
                    <h2 class="text-2xl font-bold">{{ $employee->full_name }}</h2>
                    <x-ui.badge tone="indigo">{{ $employee->matricule }}</x-ui.badge>
                    <x-ui.badge :tone="$today[1]">{{ $today[0] }}</x-ui.badge>
                    @if ($employee->is_expatriate)<x-ui.badge tone="sky">Expatrié</x-ui.badge>@endif
                </div>
                <p class="text-sm text-ink-muted">{{ $employee->position?->title }} · {{ $employee->department?->name }}</p>
            </div>
        </div>
        @canDo('update', 'employees')
            <x-ui.button variant="secondary" :href="route('employees.edit', $employee->id)" wire:navigate icon="pencil-square">Modifier</x-ui.button>
        @endcanDo
    </div>

    <div class="rounded-2xl border border-line bg-surface shadow-sm">
        <div class="flex gap-1 border-b border-line px-3 pt-3" role="tablist">
            @foreach (['info' => 'Informations', 'contracts' => 'Contrats', 'documents' => 'Documents', 'account' => 'Compte'] as $key => $label)
                <button type="button" role="tab" wire:click="$set('tab', '{{ $key }}')" aria-selected="{{ $tab === $key ? 'true' : 'false' }}"
                        @class(['rounded-t-lg px-4 py-2 text-sm font-medium', 'border-b-2 border-brand-600 text-brand-700 dark:text-brand-300' => $tab === $key, 'text-ink-muted hover:text-ink' => $tab !== $key])>{{ $label }}</button>
            @endforeach
        </div>

        <div class="p-5">
            @if ($tab === 'info')
                <dl class="grid gap-x-8 gap-y-4 text-sm sm:grid-cols-2 lg:grid-cols-3">
                    @foreach ([
                        'Date d\'embauche' => $employee->hire_date?->format('d/m/Y'),
                        'Ancienneté' => $employee->hire_date?->diffForHumans(now(), \Carbon\CarbonInterface::DIFF_ABSOLUTE),
                        'Manager' => $manager ? $manager->matricule.' · '.$manager->full_name : null,
                        'Sexe' => ['M' => 'Homme', 'F' => 'Femme'][$employee->gender] ?? null,
                        'Date de naissance' => $employee->birth_date?->format('d/m/Y'),
                        'Lieu de naissance' => $employee->birth_place,
                        'Nationalité' => $employee->nationality,
                        'Situation matrimoniale' => $marital[$employee->marital_status] ?? null,
                        'Conjoint qui travaille' => $employee->marital_status === 'married' ? ($employee->spouse_works ? 'Oui' : 'Non') : null,
                        'Enfants à charge' => (string) $employee->children_count,
                        'Email professionnel' => $employee->work_email,
                        'Email personnel' => $employee->personal_email,
                        'Téléphone' => $employee->phone,
                        'Adresse' => $employee->address,
                        'Contact d\'urgence' => trim($employee->emergency_contact_name.' '.$employee->emergency_contact_phone),
                        'Permis de travail' => $employee->is_expatriate ? trim($employee->work_permit_number.' '.($employee->work_permit_expires_at ? '(expire le '.$employee->work_permit_expires_at->format('d/m/Y').')' : '')) : null,
                    ] as $label => $value)
                        <div><dt class="text-ink-muted">{{ $label }}</dt><dd class="mt-0.5 font-medium">{{ $value !== null && $value !== '' ? $value : '—' }}</dd></div>
                    @endforeach
                </dl>
            @elseif ($tab === 'contracts')
                @if (! $canSeeContracts)
                    <x-ui.empty-state title="Accès restreint" text="Vous n'avez pas le droit de consulter les contrats." icon="lock-closed" />
                @else
                    <div class="overflow-x-auto">
                        <table class="w-full text-left text-sm">
                            <thead class="text-xs uppercase tracking-wide text-ink-muted"><tr><th class="py-2 pr-4">Type</th><th class="py-2 pr-4">Début</th><th class="py-2 pr-4">Fin</th><th class="py-2 pr-4">Renouvellements</th><th class="py-2 pr-4">Salaire de base</th><th class="py-2">Statut</th></tr></thead>
                            <tbody>
                                @forelse ($contracts as $c)
                                    <tr wire:key="contract-{{ $c->id }}" class="border-t border-line">
                                        <td class="py-3 pr-4 font-medium">{{ $c->type }}</td>
                                        <td class="py-3 pr-4">{{ $c->start_date?->format('d/m/Y') }}</td>
                                        <td class="py-3 pr-4">{{ $c->end_date?->format('d/m/Y') ?? '—' }}</td>
                                        <td class="py-3 pr-4">{{ $c->renewal_number }}</td>
                                        <td class="py-3 pr-4 tabular-nums">{{ number_format((int) $c->base_salary, 0, ',', ' ') }} {{ $c->currency }}</td>
                                        <td class="py-3"><x-ui.badge :tone="$c->status === 'active' ? 'green' : 'gray'">{{ $c->status === 'active' ? 'En cours' : ($c->status === 'renewed' ? 'Renouvelé' : 'Terminé') }}</x-ui.badge></td>
                                    </tr>
                                @empty
                                    <tr><td colspan="6"><x-ui.empty-state title="Aucun contrat" icon="document-text" /></td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                @endif
            @elseif ($tab === 'documents')
                <ul class="divide-y divide-line rounded-xl border border-line">
                    @forelse ($documents as $doc)
                        @php
                            $ext = strtolower(pathinfo($doc->file_path, PATHINFO_EXTENSION));
                            $isViewable = in_array($ext, ['pdf', 'jpg', 'jpeg', 'png', 'webp', 'gif']);
                        @endphp
                        <li wire:key="doc-{{ $doc->id }}" class="flex flex-wrap items-center justify-between gap-3 px-4 py-3 text-sm">
                            <div class="flex flex-col">
                                <span class="font-medium text-ink">{{ $doc->title }}</span>
                                <span class="text-xs text-ink-muted">{{ config("documents.types.{$doc->type}.label", $doc->type) }}</span>
                            </div>
                            
                            <div class="flex items-center gap-4">
                                <span class="text-xs text-ink-muted">{{ $doc->created_at?->format('d/m/Y') }}</span>
                                <div class="flex items-center gap-2">
                                    <x-document-actions
                                    :view="route('employees.documents.view', [$employee, $doc])"
                                    :download="route('employees.documents.download', [$employee, $doc])"
                                    :ext="pathinfo($doc->file_path, PATHINFO_EXTENSION)"
                                    :name="$doc->title ?: $doc->type" />
                                </div>
                            </div>
                        </li>
                    @empty
                        <li><x-ui.empty-state title="Aucun document" text="Ajoutez des pièces depuis « Modifier ». Seuls les documents que vous avez le droit de voir s'affichent." icon="document-text" /></li>
                    @endforelse
                </ul>
            @else
                @if ($tempPassword)
                    <div x-data="{ pwd: @js($tempPassword) }" class="mb-4 space-y-2 rounded-xl bg-amber-50 p-4 text-sm text-amber-900 dark:bg-amber-500/10 dark:text-amber-200">
                        <p><strong>Mot de passe provisoire (affiché une seule fois) :</strong></p>
                        <p class="font-mono text-base" x-text="pwd"></p>
                        @if ($mailResult === 'sent')<p>Un email avec ces identifiants a été envoyé.</p>@elseif ($mailResult === 'failed')<p class="text-rose-700">L'envoi de l'email a échoué : transmettez le mot de passe à la main.</p>@endif
                        <div class="flex gap-2">
                            <x-ui.button size="sm" variant="secondary" type="button" @click="navigator.clipboard.writeText(pwd)">Copier</x-ui.button>
                            <x-ui.button size="sm" variant="secondary" wire:click="dismissPassword">J'ai noté le mot de passe</x-ui.button>
                        </div>
                    </div>
                @endif

                @if ($account)
                    <dl class="grid gap-4 text-sm sm:grid-cols-2">
                        <div><dt class="text-ink-muted">Identifiant de connexion</dt><dd class="mt-0.5 font-medium">{{ $account->email }}</dd></div>
                        <div><dt class="text-ink-muted">État</dt><dd class="mt-0.5"><x-ui.badge :tone="$account->is_active ? 'green' : 'gray'">{{ $account->is_active ? 'Actif' : 'Désactivé' }}</x-ui.badge></dd></div>
                        <div><dt class="text-ink-muted">Première connexion</dt><dd class="mt-0.5 font-medium">{{ $account->first_login_at ? 'Effectuée' : 'Pas encore' }}</dd></div>
                    </dl>
                    <p class="mt-4 text-sm text-ink-muted">Droits et mot de passe : menu Administration → Utilisateurs.</p>
                @elseif (! $tempPassword)
                    @canDo('create', 'users')
                        <form wire:submit="createAccount" class="max-w-md space-y-3">
                            <p class="text-sm text-ink-muted">Cet employé n'a pas encore d'accès à l'application.</p>
                            <x-ui.field label="Adresse de connexion" name="accountEmail" required>
                                <input id="accountEmail" type="email" wire:model="accountEmail" class="input">
                            </x-ui.field>
                            <label class="flex items-center gap-2 text-sm"><input type="checkbox" wire:model="sendByEmail"> Envoyer les identifiants par email à cette adresse</label>
                            <x-ui.button type="submit" icon="key" wire:loading.attr="disabled" wire:target="createAccount">Créer l'accès</x-ui.button>
                        </form>
                    @else
                        <x-ui.empty-state title="Aucun compte" text="Cet employé n'a pas d'accès à l'application." icon="user" />
                    @endcanDo
                @endif
            @endif
        </div>
    </div>
</div>