<div class="space-y-6">
    <div>
        <h2 class="text-2xl font-bold">{{ $missionId ? 'Modifier la mission' : 'Nouvel ordre de mission' }}</h2>
        <p class="text-sm text-ink-muted">Renseignez la mission, puis ajoutez les participants. Le perdiem n’est saisi que pour les employés de votre département ; pour les autres, il est défini par la RH.</p>
    </div>

    @error('mission')<p class="rounded-xl bg-rose-50 px-4 py-3 text-sm text-rose-800 dark:bg-rose-500/10 dark:text-rose-200">{{ $message }}</p>@enderror

    <form wire:submit="submit" class="space-y-6">
        <section class="grid gap-4 rounded-2xl border border-line bg-surface p-5 shadow-sm sm:grid-cols-2">
            <h3 class="text-sm font-semibold sm:col-span-2">1. La mission</h3>
            <div class="sm:col-span-2">
                <label class="mb-1 block text-sm font-medium">Objet de la mission</label>
                <input type="text" wire:model="purpose" class="input">
                @error('purpose')<p class="mt-1 text-xs text-rose-600">{{ $message }}</p>@enderror
            </div>
            <div>
                <label class="mb-1 block text-sm font-medium">Destination (ville, zone)</label>
                <input type="text" wire:model="destination_zone" class="input" placeholder="Ségou, zone industrielle">
                @error('destination_zone')<p class="mt-1 text-xs text-rose-600">{{ $message }}</p>@enderror
            </div>
            <div>
                <label class="mb-1 block text-sm font-medium">Hôtel ou quartier (facultatif)</label>
                <input type="text" wire:model="lodging" class="input">
            </div>
            <div>
                <label class="mb-1 block text-sm font-medium">Date de départ</label>
                <input type="date" wire:model.live="start_date" class="input">
                @error('start_date')<p class="mt-1 text-xs text-rose-600">{{ $message }}</p>@enderror
            </div>
            <div>
                <label class="mb-1 block text-sm font-medium">Date de retour</label>
                <input type="date" wire:model.live="end_date" class="input">
                @error('end_date')<p class="mt-1 text-xs text-rose-600">{{ $message }}</p>@enderror
                @if ($this->days)<p class="mt-1 text-xs text-ink-muted">{{ $this->days }} jour(s) indemnisable(s) (la RH peut ajuster).</p>@endif
            </div>
            <div class="sm:col-span-2">
                <label class="mb-1 block text-sm font-medium">Programme / activités (facultatif)</label>
                <textarea rows="3" wire:model="program" class="input"></textarea>
            </div>
        </section>

        <section class="space-y-4 rounded-2xl border border-line bg-surface p-5 shadow-sm">
            <h3 class="text-sm font-semibold">2. Les participants</h3>

            <div class="grid gap-4 lg:grid-cols-2">
                <div>
                    <label class="mb-1 block text-sm font-medium">Rechercher un employé (matricule ou nom, 3 caractères minimum)</label>
                    <input type="text" wire:model.live.debounce.300ms="search" class="input" placeholder="Ex. chauffeur d’un autre département">
                    @if ($this->results->isNotEmpty())
                        <ul class="mt-2 divide-y divide-line rounded-xl border border-line text-sm">
                            @foreach ($this->results as $r)
                                <li class="flex items-center justify-between gap-2 px-3 py-2" wire:key="res-{{ $r['employee_id'] }}">
                                    <span>{{ $r['name'] }} <span class="text-xs text-ink-muted">· {{ $r['matricule'] }} · {{ $r['position'] }} · {{ $r['department'] }}</span></span>
                                    <button type="button" wire:click="addParticipant({{ $r['employee_id'] }})" class="rounded border border-line px-2 py-1 text-xs hover:bg-surface-muted">Ajouter</button>
                                </li>
                            @endforeach
                        </ul>
                    @elseif (mb_strlen(trim($search)) >= 3)
                        <p class="mt-2 text-xs text-ink-muted">Aucun employé trouvé.</p>
                    @endif
                    <p class="mt-1 text-xs text-ink-muted">Seuls le nom, le matricule, le poste et le département sont affichés.</p>
                </div>
                <div>
                    <label class="mb-1 block text-sm font-medium">Mon département</label>
                    <select class="input" wire:change="addParticipant($event.target.value)">
                        <option value="">Ajouter un membre de mon équipe…</option>
                        @foreach ($this->myTeam as $r)<option value="{{ $r['employee_id'] }}">{{ $r['name'] }} — {{ $r['matricule'] }}</option>@endforeach
                    </select>
                </div>
            </div>

            @error('participants')<p class="text-xs text-rose-600">{{ $message }}</p>@enderror

            <div class="overflow-x-auto rounded-xl border border-line">
                <table class="w-full text-left text-sm">
                    <thead class="bg-surface-muted text-xs text-ink-muted">
                        <tr><th class="px-3 py-2">Participant</th><th class="px-3 py-2">Rôle</th><th class="px-3 py-2">Perdiem / jour (FCFA)</th><th class="px-3 py-2">Paiement</th><th class="px-3 py-2"></th></tr>
                    </thead>
                    <tbody class="divide-y divide-line">
                        @forelse ($participants as $i => $p)
                            @php($own = (int) $p['department_id'] === (int) $this->ownDepartmentId)
                            <tr wire:key="part-{{ $p['employee_id'] }}">
                                <td class="px-3 py-2">{{ $p['name'] }}<span class="block text-xs text-ink-muted">{{ $p['matricule'] }} · {{ $p['position'] }} · {{ $p['department'] }}</span></td>
                                <td class="px-3 py-2">
                                    <select wire:model="participants.{{ $i }}.role" class="input py-1">
                                        @foreach (\App\Enums\MissionRole::cases() as $role)<option value="{{ $role->value }}">{{ $role->label() }}</option>@endforeach
                                    </select>
                                </td>
                                <td class="px-3 py-2">
                                    @if ($own)
                                        <input type="number" min="0" wire:model.live.debounce.300ms="participants.{{ $i }}.daily_rate" class="input py-1" placeholder="Montant manuel">
                                        @if ($this->days && (int) $p['daily_rate'] > 0)<span class="block text-xs text-ink-muted">Total estimé : {{ number_format((int) $p['daily_rate'] * $this->days, 0, ',', ' ') }} FCFA</span>@else<span class="block text-xs text-ink-muted">Vide = défini par la RH</span>@endif
                                        @error("participants.$i.daily_rate")<p class="text-xs text-rose-600">{{ $message }}</p>@enderror
                                    @else
                                        <span class="text-xs italic text-ink-muted">Défini par la RH</span>
                                    @endif
                                </td>
                                <td class="px-3 py-2">
                                    <select wire:model="participants.{{ $i }}.payment_mode" class="input py-1">
                                        @foreach (config('missions.payment_modes') as $key => $label)<option value="{{ $key }}">{{ $label }}</option>@endforeach
                                    </select>
                                </td>
                                <td class="px-3 py-2 text-right"><button type="button" wire:click="removeParticipant({{ $i }})" class="text-xs text-rose-600 hover:underline">Retirer</button></td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="px-3 py-8 text-center text-ink-muted">Aucun participant pour le moment.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>

        <div class="flex flex-wrap justify-end gap-3">
            <a href="{{ route('missions.index') }}" wire:navigate class="inline-flex items-center rounded-xl border border-line px-4 py-2 text-sm hover:bg-surface-muted">Annuler</a>
            <button type="button" wire:click="saveDraft" wire:loading.attr="disabled" class="rounded-xl border border-line px-4 py-2 text-sm hover:bg-surface-muted disabled:opacity-60">Enregistrer le brouillon</button>
            <button type="submit" wire:loading.attr="disabled" wire:confirm="Envoyer cette mission à la RH pour vérification ?" class="rounded-xl bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-500 disabled:opacity-60">Envoyer à la RH</button>
        </div>
    </form>
</div>
