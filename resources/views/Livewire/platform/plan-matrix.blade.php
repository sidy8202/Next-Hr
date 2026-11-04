@php($groups = collect(config('plans.features'))->groupBy('module', true))

<div class="space-y-6">
    <div class="flex flex-wrap items-end justify-between gap-3">
        <div><h2 class="text-2xl font-bold">Formules et matrice des fonctionnalités</h2><p class="text-sm text-ink-muted">Prix, plafonds et fonctionnalités de chaque formule. Les changements s’appliquent immédiatement à toutes les entreprises abonnées.</p></div>
        <button type="button" wire:click="save" wire:loading.attr="disabled" wire:confirm="Enregistrer ? La matrice s’applique tout de suite aux entreprises." class="rounded-xl bg-indigo-600 px-5 py-2 text-sm font-semibold text-white hover:bg-indigo-500 disabled:opacity-60">Enregistrer</button>
    </div>

    @if ($errors->any())<p class="rounded-xl bg-rose-50 px-4 py-3 text-sm text-rose-800">Certains champs sont invalides : {{ $errors->first() }}</p>@endif

    {{-- Plafonds et prix --}}
    <section class="rounded-2xl border border-line bg-surface p-5 shadow-sm">
        <h3 class="mb-3 text-sm font-semibold">Plafonds et prix</h3>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="text-xs text-ink-muted"><tr class="border-b border-line"><th class="py-2 pr-3 text-left font-medium"></th>@foreach ($plans as $p)<th class="px-2 py-2 text-left font-semibold" style="color: {{ $p->color }}">{{ $p->name }}</th>@endforeach</tr></thead>
                <tbody class="divide-y divide-line">
                    <tr><td class="py-2 pr-3">Nom affiché</td>@foreach ($plans as $p)<td class="px-2 py-1.5"><input type="text" wire:model="limits.{{ $p->id }}.name" class="input py-1"></td>@endforeach</tr>
                    <tr><td class="py-2 pr-3">Effectif maximum <span class="block text-xs text-ink-muted">vide = illimité</span></td>@foreach ($plans as $p)<td class="px-2 py-1.5"><input type="number" min="1" wire:model="limits.{{ $p->id }}.max_employees" class="input py-1"></td>@endforeach</tr>
                    <tr><td class="py-2 pr-3">Stockage (Go)</td>@foreach ($plans as $p)<td class="px-2 py-1.5"><input type="number" min="1" wire:model="limits.{{ $p->id }}.storage_gb" class="input py-1"></td>@endforeach</tr>
                    <tr><td class="py-2 pr-3">Conservation de l’audit (mois)</td>@foreach ($plans as $p)<td class="px-2 py-1.5"><input type="number" min="1" wire:model="limits.{{ $p->id }}.audit_retention_months" class="input py-1"></td>@endforeach</tr>
                    @foreach (config('platform.periods') as $months => $label)
                        <tr><td class="py-2 pr-3">Prix {{ strtolower($label) }} (FCFA)</td>@foreach ($plans as $p)<td class="px-2 py-1.5"><input type="number" min="0" wire:model="prices.{{ $p->id }}.{{ $months }}" class="input py-1" placeholder="À définir"></td>@endforeach</tr>
                    @endforeach
                    <tr><td class="py-2 pr-3">Formule proposée à la vente</td>@foreach ($plans as $p)<td class="px-2 py-2"><input type="checkbox" wire:model="limits.{{ $p->id }}.is_active"></td>@endforeach</tr>
                </tbody>
            </table>
        </div>
    </section>

    {{-- Matrice --}}
    <section class="rounded-2xl border border-line bg-surface p-5 shadow-sm">
        <h3 class="mb-1 text-sm font-semibold">Matrice des fonctionnalités</h3>
        <p class="mb-3 text-xs text-ink-muted">Cochez ce que chaque formule inclut. La paie et ce qui l’alimente (heures supplémentaires, congés, absences, sorties) sont dans toutes les formules par défaut.</p>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="text-xs"><tr class="border-b border-line"><th class="py-2 pr-3 text-left font-medium text-ink-muted">Fonctionnalité</th>@foreach ($plans as $p)<th class="w-28 px-2 py-2 text-center font-semibold" style="color: {{ $p->color }}">{{ $p->name }}</th>@endforeach</tr></thead>
                <tbody>
                    @foreach ($groups as $module => $features)
                        <tr><td colspan="{{ count($plans) + 1 }}" class="bg-surface-muted px-2 py-1.5 text-xs font-semibold uppercase tracking-wide text-ink-muted">{{ $module }}</td></tr>
                        @foreach ($features as $key => $f)
                            <tr class="border-b border-line" wire:key="ft-{{ $key }}">
                                <td class="py-2 pr-3">{{ $f['label'] }}</td>
                                @foreach ($plans as $p)<td class="px-2 py-2 text-center"><input type="checkbox" wire:model="flags.{{ $p->id }}.{{ $key }}" class="h-4 w-4 rounded"></td>@endforeach
                            </tr>
                        @endforeach
                    @endforeach
                </tbody>
            </table>
        </div>
    </section>
</div>
