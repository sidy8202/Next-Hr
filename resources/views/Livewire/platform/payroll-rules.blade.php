<div class="mx-auto w-full max-w-[1800px] space-y-6">
    <div class="flex flex-wrap items-start justify-between gap-3">
        <div>
            <h1 class="text-xl font-semibold text-ink">Règles de paie</h1>
            <p class="mt-1 text-sm text-ink-muted">
                Taux et barème légaux utilisés par le moteur de paie, avec versions datées. Une règle doit être validée par un comptable avant d'être utilisée en production ; une règle validée ne se modifie plus : on crée une nouvelle version.
            </p>
        </div>
        <button type="button" wire:click="newCode" class="rounded-lg bg-ink px-4 py-2 text-sm font-medium text-surface">Nouvelle règle</button>
    </div>

    @if ($flash)
        <div class="rounded-lg border border-line bg-surface-muted px-4 py-3 text-sm text-ink">{{ $flash }}</div>
    @endif

    @if ($pending > 0)
        <div class="rounded-lg border border-amber-500/40 bg-amber-500/10 px-4 py-3 text-sm text-amber-700">
            {{ $pending }} version(s) de règle restent à valider.
        </div>
    @endif

    @if ($formMode)
        <form wire:submit="save" class="space-y-4 rounded-xl border border-line bg-surface p-5">
            <h2 class="text-base font-semibold text-ink">
                @if ($formMode === 'new_code') Nouvelle règle
                @elseif ($formMode === 'new_version') Nouvelle version de {{ $code }}
                @else Modifier {{ $code }} (non validée)
                @endif
            </h2>

            <div class="grid gap-4 sm:grid-cols-3">
                <div>
                    <label class="mb-1 block text-sm font-medium text-ink" for="code">Code</label>
                    <input id="code" type="text" wire:model="code" class="input w-full uppercase" placeholder="INPS_SAL" @disabled($formMode !== 'new_code')>
                    @error('code') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                </div>
                <div class="sm:col-span-2">
                    <label class="mb-1 block text-sm font-medium text-ink" for="label">Libellé</label>
                    <input id="label" type="text" wire:model="label" class="input w-full">
                    @error('label') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="mb-1 block text-sm font-medium text-ink" for="party">Partie</label>
                    <select id="party" wire:model="party" class="input w-full">
                        <option value="employee">Salarié</option>
                        <option value="employer">Employeur</option>
                    </select>
                </div>
                <div>
                    <label class="mb-1 block text-sm font-medium text-ink" for="kind">Type</label>
                    <select id="kind" wire:model.live="kind" class="input w-full">
                        <option value="rate">Taux (%)</option>
                        <option value="its">Barème ITS</option>
                    </select>
                </div>
                <div>
                    <label class="mb-1 block text-sm font-medium text-ink" for="base">Assiette</label>
                    <select id="base" wire:model="base" class="input w-full">
                        <option value="cotisable">Cotisable</option>
                        <option value="brut">Brut</option>
                        <option value="imposable">Imposable</option>
                    </select>
                </div>

                @if ($kind === 'rate')
                    <div>
                        <label class="mb-1 block text-sm font-medium text-ink" for="rate">Taux (%)</label>
                        <input id="rate" type="text" inputmode="decimal" wire:model="rate" class="input w-full text-right">
                        @error('rate') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-medium text-ink" for="ceiling">Plafond mensuel (FCFA, facultatif)</label>
                        <input id="ceiling" type="text" inputmode="numeric" wire:model="ceiling_monthly" class="input w-full text-right">
                        @error('ceiling_monthly') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                    </div>
                    <label class="flex items-center gap-2 self-end text-sm text-ink">
                        <input type="checkbox" wire:model="deductible_from_its"> Déductible de la base de l'ITS
                    </label>
                @endif

                <div>
                    <label class="mb-1 block text-sm font-medium text-ink" for="effective_from">Date d'effet</label>
                    <input id="effective_from" type="date" wire:model="effective_from" class="input w-full">
                    @error('effective_from') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="mb-1 block text-sm font-medium text-ink" for="sort">Ordre d'affichage</label>
                    <input id="sort" type="number" min="0" max="999" wire:model="sort" class="input w-full">
                    @error('sort') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                </div>
                <div class="sm:col-span-3">
                    <label class="mb-1 block text-sm font-medium text-ink" for="source">Source</label>
                    <input id="source" type="text" wire:model="source" class="input w-full" placeholder="Texte de loi, article, bulletin de référence…">
                    @error('source') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                </div>

                @if ($kind === 'its')
                    <div class="sm:col-span-3">
                        <label class="mb-1 block text-sm font-medium text-ink" for="paramsJson">Paramètres du barème (JSON)</label>
                        <textarea id="paramsJson" rows="10" wire:model="paramsJson" class="input w-full font-mono text-xs"></textarea>
                        <p class="mt-1 text-xs text-ink-muted">Clés attendues : « brackets » (tranches [début, fin ou null, taux %]), « family » (réductions pour charges de famille) et « rebate_points ».</p>
                        @error('paramsJson') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                    </div>
                @endif

                <div class="sm:col-span-3">
                    <label class="mb-1 block text-sm font-medium text-ink" for="notes">Notes</label>
                    <textarea id="notes" rows="2" wire:model="notes" class="input w-full"></textarea>
                    @error('notes') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                </div>
            </div>

            <div class="flex justify-end gap-2">
                <button type="button" wire:click="cancel" class="rounded-lg border border-line px-4 py-2 text-sm text-ink">Annuler</button>
                <button type="submit" class="rounded-lg bg-ink px-4 py-2 text-sm font-medium text-surface">Enregistrer</button>
            </div>
        </form>
    @endif

    <div class="space-y-4">
        @forelse ($groups as $code => $versions)
            <div class="overflow-hidden rounded-xl border border-line bg-surface">
                <div class="flex flex-wrap items-center justify-between gap-2 bg-surface-muted px-4 py-3">
                    <div>
                        <span class="rounded-md bg-ink px-2 py-0.5 text-xs font-semibold text-surface">{{ $code }}</span>
                        <span class="ml-2 text-sm font-medium text-ink">{{ $versions->first()->label }}</span>
                        <span class="ml-2 text-xs text-ink-muted">{{ $versions->first()->party === 'employer' ? 'employeur' : 'salarié' }}</span>
                    </div>
                    <button type="button" wire:click="newVersion({{ $versions->first()->id }})" class="rounded-lg border border-line px-3 py-1.5 text-sm text-ink">Nouvelle version</button>
                </div>

                <div class="overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead class="text-left text-xs uppercase tracking-wide text-ink-muted">
                            <tr>
                                <th class="px-4 py-2">Période</th>
                                <th class="px-4 py-2">Valeur</th>
                                <th class="px-4 py-2">Statut</th>
                                <th class="px-4 py-2">Source</th>
                                <th class="px-4 py-2"></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-line">
                            @foreach ($versions as $v)
                                <tr wire:key="rule-{{ $v->id }}">
                                    <td class="px-4 py-2 text-ink whitespace-nowrap">
                                        {{ $v->effective_from->format('d/m/Y') }} → {{ $v->effective_until ? $v->effective_until->format('d/m/Y') : 'en cours' }}
                                    </td>
                                    <td class="px-4 py-2 text-ink">
                                        @if ($v->kind === 'its')
                                            Barème · {{ count($v->params['brackets'] ?? []) }} tranches
                                        @else
                                            {{ rtrim(rtrim(number_format((float) $v->rate, 3, ',', ' '), '0'), ',') }} %
                                            <span class="text-xs text-ink-muted">sur {{ $v->base }}{{ $v->ceiling_monthly ? ', plafond ' . number_format($v->ceiling_monthly, 0, ',', ' ') : '' }}{{ $v->deductible_from_its ? ', déductible ITS' : '' }}</span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-2">
                                        @if ($v->status === 'validated')
                                            <span class="rounded-full bg-emerald-500/15 px-2 py-0.5 text-xs text-emerald-700">Validée</span>
                                            <span class="block text-xs text-ink-muted">
                                                {{ $v->validator?->name }}{{ $v->validated_at ? ' · ' . $v->validated_at->format('d/m/Y') : '' }}
                                            </span>
                                        @else
                                            <span class="rounded-full bg-amber-500/15 px-2 py-0.5 text-xs text-amber-700">À valider</span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-2 text-xs text-ink-muted max-w-xs">
                                        {{ $v->source }}
                                        @if ($v->notes) <span class="block">{{ $v->notes }}</span> @endif
                                    </td>
                                    <td class="px-4 py-2 text-right whitespace-nowrap">
                                        @if ($v->status !== 'validated')
                                            <button type="button" wire:click="validateRule({{ $v->id }})" wire:confirm="Confirmer que cette règle a été vérifiée par un comptable ? Votre nom et la date seront enregistrés." class="rounded-lg bg-ink px-3 py-1.5 text-sm font-medium text-surface">Valider</button>
                                            <button type="button" wire:click="editRule({{ $v->id }})" class="ml-2 text-sm text-ink underline">Modifier</button>
                                            <button type="button" wire:click="deleteRule({{ $v->id }})" wire:confirm="Supprimer cette version ?" class="ml-2 text-sm text-red-500">Supprimer</button>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @empty
            <p class="rounded-xl border border-line bg-surface p-6 text-center text-sm text-ink-muted">
                Aucune règle. Lancez <code>php artisan db:seed --class=PayrollRuleSeeder</code> pour charger les règles du Mali.
            </p>
        @endforelse
    </div>
</div>
