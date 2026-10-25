<div class="space-y-6">
    <div class="flex flex-wrap items-center justify-between gap-3">
        <div>
            <h2 class="text-2xl font-bold">Banque de CV</h2>
            <p class="text-sm text-ink-muted">
                {{ $this->candidates->total() }} profil(s). Classement par domaine, niveau d’études et ancienneté
                (années d’expérience : junior {{ \App\Support\CandidateProfile::seniorityRange('junior') }},
                confirmé {{ \App\Support\CandidateProfile::seniorityRange('confirmed') }},
                senior {{ \App\Support\CandidateProfile::seniorityRange('senior') }},
                expert {{ \App\Support\CandidateProfile::seniorityRange('expert') }}).
            </p>
        </div>
        @if (auth()->user()->canDo('create', 'candidates'))
            <a href="{{ route('candidates.create') }}" wire:navigate class="inline-flex items-center rounded-xl bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-500">Ajouter un profil</a>
        @endif
    </div>

    @if (session('status'))
        <p class="rounded-xl bg-emerald-50 px-4 py-3 text-sm text-emerald-900 dark:bg-emerald-500/10 dark:text-emerald-200">{{ session('status') }}</p>
    @endif
    @error('candidate')
        <p class="rounded-xl bg-rose-50 px-4 py-3 text-sm text-rose-800 dark:bg-rose-500/10 dark:text-rose-200">{{ $message }}</p>
    @enderror

    {{-- Filtres --}}
    <div class="rounded-2xl border border-line bg-surface p-4 shadow-sm">
        <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
            <div>
                <label for="f-search" class="mb-1 block text-sm font-medium">Recherche</label>
                <input id="f-search" type="search" wire:model.live.debounce.300ms="search" class="input" placeholder="Nom, email, téléphone, poste actuel…">
            </div>
            <div>
                <label for="f-profile" class="mb-1 block text-sm font-medium">Domaine</label>
                <select id="f-profile" wire:model.live="profile" class="input">
                    <option value="">Tous les domaines</option>
                    @foreach (config('recruitment.profile_categories') as $cat)<option value="{{ $cat }}">{{ $cat }}</option>@endforeach
                </select>
            </div>
            <div>
                <label for="f-seniority" class="mb-1 block text-sm font-medium">Ancienneté</label>
                <select id="f-seniority" wire:model.live="seniority" class="input">
                    <option value="">Toutes</option>
                    @foreach (config('recruitment.seniority') as $key => $s)
                        <option value="{{ $key }}">{{ $s['label'] }} ({{ \App\Support\CandidateProfile::seniorityRange($key) }})</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label for="f-education" class="mb-1 block text-sm font-medium">Niveau d’études minimum</label>
                <select id="f-education" wire:model.live="education" class="input">
                    <option value="">Tous</option>
                    @foreach (config('recruitment.education_levels') as $key => $l)<option value="{{ $key }}">{{ $l['label'] }}</option>@endforeach
                </select>
            </div>
            <div>
                <label for="f-skill" class="mb-1 block text-sm font-medium">Compétence exacte</label>
                <input id="f-skill" type="search" wire:model.live.debounce.400ms="skill" class="input" placeholder="ex. excel, réseaux, sql">
            </div>
            <div>
                <label for="f-status" class="mb-1 block text-sm font-medium">Statut</label>
                <select id="f-status" wire:model.live="status" class="input">
                    <option value="">Tous</option>
                    @foreach (config('recruitment.candidate_statuses') as $key => $label)<option value="{{ $key }}">{{ $label }}</option>@endforeach
                </select>
            </div>
        </div>
        <div class="mt-3 flex justify-end">
            <button type="button" wire:click="resetFilters" class="rounded-lg border border-line px-3 py-1.5 text-sm hover:bg-surface-muted">Réinitialiser les filtres</button>
        </div>
    </div>

    {{-- Liste --}}
    <div class="overflow-x-auto rounded-2xl border border-line bg-surface shadow-sm">
        <table class="w-full text-left text-sm">
            <thead class="bg-surface-muted text-xs text-ink-muted">
                <tr>
                    <th class="px-4 py-2">Candidat</th>
                    <th class="px-4 py-2">Domaine</th>
                    <th class="px-4 py-2">Études</th>
                    <th class="px-4 py-2">Expérience</th>
                    <th class="px-4 py-2">Compétences</th>
                    <th class="px-4 py-2 text-right">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-line">
                @forelse ($this->candidates as $c)
                    <tr wire:key="cand-{{ $c->id }}">
                        <td class="px-4 py-3">
                            <p class="font-medium">{{ $c->full_name }}
                                @if (! $c->bank_consent)
                                    <span class="ml-1 rounded-full bg-amber-100 px-2 py-0.5 text-xs text-amber-900 dark:bg-amber-500/20 dark:text-amber-200" title="Le candidat n’a pas accepté la conservation pour d’autres offres">Cette offre uniquement</span>
                                @endif
                                @if ($c->status !== 'active')
                                    <span class="ml-1 rounded-full bg-slate-100 px-2 py-0.5 text-xs text-slate-700 dark:bg-slate-500/20 dark:text-slate-200">{{ config('recruitment.candidate_statuses.'.$c->status, $c->status) }}</span>
                                @endif
                            </p>
                            <p class="text-xs text-ink-muted">{{ $c->email ?: $c->phone }}@if ($c->current_job_title) · {{ $c->current_job_title }}@endif</p>
                        </td>
                        <td class="px-4 py-3">{{ $c->profile_category ?: '—' }}</td>
                        <td class="px-4 py-3">
                            {{ $c->educationLabel() }}
                            @if ($c->last_degree)<p class="text-xs text-ink-muted">{{ $c->last_degree }}</p>@endif
                        </td>
                        <td class="px-4 py-3">
                            {{ rtrim(rtrim(number_format($c->experience_years, 1, ',', ''), '0'), ',') }} an(s)
                            <x-ui.badge tone="indigo" class="ml-1">{{ $c->seniorityLabel() }}</x-ui.badge>
                        </td>
                        <td class="px-4 py-3">
                            <div class="flex max-w-xs flex-wrap gap-1">
                                @foreach (array_slice($c->skills ?? [], 0, 4) as $skill)
                                    <span class="rounded-full bg-surface-muted px-2 py-0.5 text-xs">{{ $skill }}</span>
                                @endforeach
                                @if (count($c->skills ?? []) > 4)<span class="text-xs text-ink-muted">+{{ count($c->skills) - 4 }}</span>@endif
                            </div>
                        </td>
                        <td class="px-4 py-3 text-right">
                            <div class="flex flex-wrap justify-end gap-2 text-xs">
                                @if ($c->cv_path)
                                    <x-document-actions :view="route('candidates.cv.view', $c)" :download="route('candidates.cv', $c)" :ext="pathinfo($c->cv_path, PATHINFO_EXTENSION)" :name="'CV — '.$c->full_name" />
                                @endif
                                @if (auth()->user()->canDo('update', 'candidates'))
                                    <a href="{{ route('candidates.edit', $c) }}" wire:navigate class="rounded border border-line px-2 py-1 hover:bg-surface-muted">Modifier</a>
                                    @if ($c->status !== 'hired')
                                        <button type="button" wire:click="archive({{ $c->id }})" class="rounded border border-line px-2 py-1 hover:bg-surface-muted">{{ $c->status === 'archived' ? 'Réactiver' : 'Archiver' }}</button>
                                    @endif
                                @endif
                                @if (auth()->user()->canDo('delete', 'candidates'))
                                    <button type="button" wire:click="delete({{ $c->id }})" wire:confirm="Supprimer définitivement ce profil et son CV ?" class="rounded border border-rose-300 px-2 py-1 text-rose-700 hover:bg-rose-50 dark:border-rose-500/40 dark:text-rose-300 dark:hover:bg-rose-500/10">Supprimer</button>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="px-4 py-10 text-center text-ink-muted">Aucun profil ne correspond. Modifiez les filtres ou ajoutez un profil à la banque de CV.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{ $this->candidates->links() }}
</div>
