<div class="space-y-6">
    <div class="flex flex-wrap items-center justify-between gap-3">
        <div>
            <h2 class="text-2xl font-bold">Offres d’emploi</h2>
            <p class="text-sm text-ink-muted">Publiez une offre, proposez-y des profils de la banque de CV et suivez chaque candidature.</p>
        </div>
        @if (auth()->user()->canDo('create', 'job_offers'))
            <a href="{{ route('job-offers.create') }}" wire:navigate class="inline-flex items-center rounded-xl bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-500">Nouvelle offre</a>
        @endif
    </div>

    @if (session('status'))
        <p class="rounded-xl bg-emerald-50 px-4 py-3 text-sm text-emerald-900 dark:bg-emerald-500/10 dark:text-emerald-200">{{ session('status') }}</p>
    @endif
    @error('offer')
        <p class="rounded-xl bg-rose-50 px-4 py-3 text-sm text-rose-800 dark:bg-rose-500/10 dark:text-rose-200">{{ $message }}</p>
    @enderror

    <div class="max-w-xs">
        <label for="f-status" class="mb-1 block text-sm font-medium">Statut</label>
        <select id="f-status" wire:model.live="status" class="input">
            <option value="">Toutes les offres</option>
            @foreach (config('recruitment.offer_statuses') as $key => $label)<option value="{{ $key }}">{{ $label }}</option>@endforeach
        </select>
    </div>

    <div class="overflow-x-auto rounded-2xl border border-line bg-surface shadow-sm">
        <table class="w-full text-left text-sm">
            <thead class="bg-surface-muted text-xs text-ink-muted">
                <tr>
                    <th class="px-4 py-2">Offre</th>
                    <th class="px-4 py-2">Département</th>
                    <th class="px-4 py-2">Contrat</th>
                    <th class="px-4 py-2">Postes</th>
                    <th class="px-4 py-2">Candidatures</th>
                    <th class="px-4 py-2">Statut</th>
                    <th class="px-4 py-2 text-right">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-line">
                @forelse ($this->offers as $o)
                    <tr wire:key="offer-{{ $o->id }}">
                        <td class="px-4 py-3">
                            <a href="{{ route('job-offers.show', $o) }}" wire:navigate class="font-medium hover:underline">{{ $o->title }}</a>
                            <p class="text-xs text-ink-muted">{{ $o->reference }}@if ($o->closes_at) · clôture le {{ $o->closes_at->format('d/m/Y') }}@endif</p>
                        </td>
                        <td class="px-4 py-3">{{ $o->department?->name ?? '—' }}</td>
                        <td class="px-4 py-3">{{ config('recruitment.contract_types.'.$o->contract_type, $o->contract_type) }}</td>
                        <td class="px-4 py-3">{{ $o->vacancies }}</td>
                        <td class="px-4 py-3">{{ $o->applications_count }}</td>
                        <td class="px-4 py-3"><x-ui.badge tone="indigo">{{ $o->statusLabel() }}</x-ui.badge></td>
                        <td class="px-4 py-3 text-right">
                            <div class="flex flex-wrap justify-end gap-2 text-xs">
                                <a href="{{ route('job-offers.show', $o) }}" wire:navigate class="rounded border border-line px-2 py-1 hover:bg-surface-muted">Ouvrir</a>
                                @if (auth()->user()->canDo('update', 'job_offers'))
                                    <a href="{{ route('job-offers.edit', $o) }}" wire:navigate class="rounded border border-line px-2 py-1 hover:bg-surface-muted">Modifier</a>
                                @endif
                                @if (auth()->user()->canDo('delete', 'job_offers'))
                                    <button type="button" wire:click="delete({{ $o->id }})" wire:confirm="Supprimer cette offre ?" class="rounded border border-rose-300 px-2 py-1 text-rose-700 hover:bg-rose-50 dark:border-rose-500/40 dark:text-rose-300 dark:hover:bg-rose-500/10">Supprimer</button>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="px-4 py-10 text-center text-ink-muted">Aucune offre pour le moment. Créez une première offre pour recevoir des candidatures.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{ $this->offers->links() }}
</div>
