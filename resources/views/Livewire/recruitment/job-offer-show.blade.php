@php
    $stageStyles = [
        'new' => 'bg-slate-100 text-slate-700 dark:bg-slate-500/20 dark:text-slate-200',
        'screening' => 'bg-sky-100 text-sky-800 dark:bg-sky-500/20 dark:text-sky-200',
        'interview' => 'bg-indigo-100 text-indigo-800 dark:bg-indigo-500/20 dark:text-indigo-200',
        'test' => 'bg-violet-100 text-violet-800 dark:bg-violet-500/20 dark:text-violet-200',
        'offer' => 'bg-amber-100 text-amber-900 dark:bg-amber-500/20 dark:text-amber-200',
        'hired' => 'bg-emerald-100 text-emerald-800 dark:bg-emerald-500/20 dark:text-emerald-200',
        'rejected' => 'bg-rose-100 text-rose-800 dark:bg-rose-500/20 dark:text-rose-200',
    ];
    $canUpdateOffer = auth()->user()->canDo('update', 'job_offers');
    $canMove = auth()->user()->canDo('update', 'applications');
    $canAdd = auth()->user()->canDo('create', 'applications') && $jobOffer->status === 'open';
@endphp

<div class="space-y-6">
    {{-- En-tête --}}
    <div class="flex flex-wrap items-start justify-between gap-3">
        <div>
            <h2 class="text-2xl font-bold">{{ $jobOffer->title }}</h2>
            <p class="text-sm text-ink-muted">
                {{ $jobOffer->reference }} · {{ config('recruitment.contract_types.'.$jobOffer->contract_type, $jobOffer->contract_type) }}
                · {{ $jobOffer->vacancies }} poste(s)
                @if ($jobOffer->department) · {{ $jobOffer->department->name }}@endif
                @if ($jobOffer->closes_at) · clôture le {{ $jobOffer->closes_at->format('d/m/Y') }}@endif
            </p>
        </div>
        <div class="flex flex-wrap items-center gap-2">
            <x-ui.badge tone="indigo">{{ $jobOffer->statusLabel() }}</x-ui.badge>
            @if ($canUpdateOffer)
                <a href="{{ route('job-offers.edit', $jobOffer) }}" wire:navigate class="rounded-lg border border-line px-3 py-1.5 text-sm hover:bg-surface-muted">Modifier</a>
                @if (in_array($jobOffer->status, ['draft', 'closed'], true))
                    <button type="button" wire:click="setStatus('open')" class="rounded-lg bg-indigo-600 px-3 py-1.5 text-sm font-semibold text-white hover:bg-indigo-500">{{ $jobOffer->status === 'draft' ? 'Publier l’offre' : 'Rouvrir l’offre' }}</button>
                @elseif (in_array($jobOffer->status, ['open', 'filled'], true))
                    <button type="button" wire:click="setStatus('closed')" wire:confirm="Clôturer cette offre ?" class="rounded-lg border border-line px-3 py-1.5 text-sm hover:bg-surface-muted">Clôturer</button>
                @endif
            @endif
        </div>
    </div>

    @if (session('status'))
        <p class="rounded-xl bg-emerald-50 px-4 py-3 text-sm text-emerald-900 dark:bg-emerald-500/10 dark:text-emerald-200">{{ session('status') }}</p>
    @endif
    @foreach (['status', 'candidate', 'move'] as $errorKey)
        @error($errorKey)
            <p class="rounded-xl bg-rose-50 px-4 py-3 text-sm text-rose-800 dark:bg-rose-500/10 dark:text-rose-200">{{ $message }}</p>
        @enderror
    @endforeach

    @if ($jobOffer->is_public && $jobOffer->status === 'open')
        @php($publicUrl = route('careers.offer', ['slug' => \App\Support\CareerSlug::ensure($jobOffer->company_id), 'reference' => $jobOffer->reference]))
        <div x-data="{ copied: false }" class="flex flex-wrap items-center gap-3 rounded-2xl border border-line bg-surface p-4 text-sm shadow-sm">
            <span class="font-medium">Offre publiée sur la page publique</span>
            <a href="{{ $publicUrl }}" target="_blank" rel="noopener" class="truncate text-indigo-600 underline dark:text-indigo-300">{{ $publicUrl }}</a>
            <button type="button" @click="navigator.clipboard.writeText('{{ $publicUrl }}'); copied = true; setTimeout(() => copied = false, 2000)" class="rounded-lg border border-line px-2 py-1 text-xs hover:bg-surface-muted" x-text="copied ? 'Lien copié' : 'Copier le lien'"></button>
        </div>
    @elseif ($jobOffer->is_public)
        <p class="rounded-xl bg-amber-50 px-4 py-3 text-sm text-amber-900 dark:bg-amber-500/10 dark:text-amber-200">Cette offre est marquée « externe » : elle apparaîtra sur la page publique dès qu’elle sera publiée (statut « Ouverte »).</p>
    @endif

    {{-- Critères --}}
    <section class="rounded-2xl border border-line bg-surface p-5 shadow-sm">
        <h3 class="mb-3 text-sm font-semibold">Profil recherché</h3>
        <dl class="grid gap-3 text-sm sm:grid-cols-3">
            <div><dt class="text-ink-muted">Domaine</dt><dd class="font-medium">{{ $jobOffer->profile_category ?: 'Tous domaines' }}</dd></div>
            <div><dt class="text-ink-muted">Études minimum</dt><dd class="font-medium">{{ $jobOffer->min_education_level ? \App\Support\CandidateProfile::educationLabel($jobOffer->min_education_level) : 'Aucun' }}</dd></div>
            <div><dt class="text-ink-muted">Expérience minimale</dt><dd class="font-medium">{{ rtrim(rtrim(number_format($jobOffer->min_experience_years, 1, ',', ''), '0'), ',') }} an(s)</dd></div>
        </dl>
        @if ($jobOffer->description)
            <p class="mt-4 whitespace-pre-line text-sm">{{ $jobOffer->description }}</p>
        @endif
        @if ($jobOffer->requirements)
            <p class="mt-3 whitespace-pre-line text-sm text-ink-muted">{{ $jobOffer->requirements }}</p>
        @endif
    </section>

    @if ($this->canSeeApplications)
        {{-- Synthèse du pipeline --}}
        <div class="flex flex-wrap gap-2">
            @foreach ($this->stageCounts as $key => $stage)
                <span class="rounded-full px-3 py-1 text-xs font-medium {{ $stageStyles[$key] ?? '' }}">{{ $stage['label'] }} : {{ $stage['count'] }}</span>
            @endforeach
        </div>

        {{-- Ajouter un profil --}}
        @if ($canAdd)
            <section class="rounded-2xl border border-line bg-surface p-5 shadow-sm">
                <h3 class="mb-3 text-sm font-semibold">Proposer un profil de la banque de CV</h3>

                <div class="max-w-md">
                    <label for="cand-search" class="mb-1 block text-sm font-medium">Rechercher un profil</label>
                    <input id="cand-search" type="search" wire:model.live.debounce.300ms="candidateSearch" class="input" placeholder="Nom, email ou poste (2 lettres minimum)" autocomplete="off">
                </div>

                @php($list = mb_strlen(trim($candidateSearch)) >= 2 ? $this->searchResults : $this->suggestions)
                <p class="mt-4 text-xs text-ink-muted">{{ mb_strlen(trim($candidateSearch)) >= 2 ? 'Résultats de la recherche' : 'Profils qui répondent aux critères de l’offre' }}</p>

                <ul class="mt-2 divide-y divide-line rounded-xl border border-line">
                    @forelse ($list as $c)
                        <li wire:key="cand-{{ $c->id }}" class="flex flex-wrap items-center justify-between gap-2 px-3 py-2 text-sm">
                            <div>
                                <span class="font-medium">{{ $c->full_name }}</span>
                                <span class="text-ink-muted">· {{ $c->educationLabel() }} · {{ $c->seniorityLabel() }} · {{ $c->profile_category ?: 'domaine non précisé' }}</span>
                                @if ($jobOffer->matches($c))<x-ui.badge tone="indigo" class="ml-1">Correspond</x-ui.badge>@endif
                            </div>
                            <button type="button" wire:click="addCandidate({{ $c->id }})" class="rounded border border-line px-2 py-1 text-xs hover:bg-surface-muted">Ajouter à l’offre</button>
                        </li>
                    @empty
                        <li class="px-3 py-3 text-sm text-ink-muted">Aucun profil à proposer pour le moment.</li>
                    @endforelse
                </ul>
            </section>
        @endif

        {{-- Candidatures --}}
        <section class="space-y-3">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <h3 class="text-sm font-semibold">Candidatures ({{ $this->applications->count() }}@if ($matchFilter !== '') sur {{ $this->bandCounts['all'] }}@endif)</h3>

                @if ($this->bandCounts['scored'] > 0)
                    <div class="flex flex-wrap items-center gap-2 text-xs">
                        <label for="match-filter" class="text-ink-muted">Correspondance</label>
                        <select id="match-filter" wire:model.live="matchFilter" class="input w-auto py-1 text-xs">
                            <option value="">Toutes ({{ $this->bandCounts['all'] }})</option>
                            <option value="match">Correspondent, {{ config('careers.shortlist_threshold') }} et + ({{ $this->bandCounts['match'] }})</option>
                            <option value="review">À examiner ({{ $this->bandCounts['review'] }})</option>
                            <option value="low">Correspondent peu ({{ $this->bandCounts['low'] }})</option>
                        </select>
                        @if ($canMove)
                            <button type="button" wire:click="shortlistMatching" wire:confirm="Présélectionner toutes les candidatures « Nouvelle » dont le score atteint {{ config('careers.shortlist_threshold') }} ?" class="rounded-lg bg-indigo-600 px-3 py-1.5 font-semibold text-white hover:bg-indigo-500">Présélectionner les profils qui correspondent</button>
                            <button type="button" wire:click="recomputeScores" class="rounded-lg border border-line px-3 py-1.5 hover:bg-surface-muted" title="À utiliser après avoir modifié les critères de l’offre">Recalculer les scores</button>
                        @endif
                    </div>
                @endif
            </div>
            @if ($this->bandCounts['scored'] === 0 && $this->bandCounts['all'] > 0)
                <p class="text-xs text-ink-muted">Aucun score : renseignez le domaine, les études, l’expérience ou les compétences recherchées dans l’offre, puis recalculez.</p>
            @endif

            @forelse ($this->applications as $app)
                @php($next = config('recruitment.stages.'.$app->stage.'.next', []))
                <div wire:key="app-{{ $app->id }}" class="rounded-2xl border border-line bg-surface p-4 shadow-sm">
                    <div class="flex flex-wrap items-start justify-between gap-3">
                        <div>
                            <p class="font-medium">{{ $app->candidate?->full_name }}
                                <span class="ml-1 rounded-full px-2 py-0.5 text-xs {{ $stageStyles[$app->stage] ?? '' }}">{{ $app->stageLabel() }}</span>
                                @if ($app->match_score !== null)
                                    @php($band = \App\Support\MatchScorer::band($app->match_score))
                                    <span class="ml-1 rounded-full px-2 py-0.5 text-xs font-semibold {{ $band['class'] }}" title="{{ implode(' · ', $app->match_details ?? []) }}">{{ $app->match_score }}/100 · {{ $band['label'] }}</span>
                                @endif
                                @if ($app->channel === 'portal')
                                    <span class="ml-1 rounded-full bg-surface-muted px-2 py-0.5 text-xs" title="Reçue par la page publique de candidature">via le portail</span>
                                @endif
                            </p>
                            <p class="text-xs text-ink-muted">
                                {{ $app->candidate?->educationLabel() }} · {{ $app->candidate?->seniorityLabel() }}
                                · postulé le {{ $app->applied_at?->format('d/m/Y') }}
                                @if ($app->candidate?->cv_path && auth()->user()->canDo('read', 'candidates'))
                                    · CV <x-document-actions :view="route('candidates.cv.view', $app->candidate)" :download="route('candidates.cv', $app->candidate)" :ext="pathinfo($app->candidate->cv_path, PATHINFO_EXTENSION)" :name="'CV — '.$app->candidate->full_name" />
                                @endif
                            </p>
                            @if ($app->cover_letter_path)
                                <p class="mt-1 text-xs text-ink-muted">Lettre de motivation
                                    <x-document-actions :view="route('applications.cover.view', $app)" :download="route('applications.cover.download', $app)" :ext="pathinfo($app->cover_letter_path, PATHINFO_EXTENSION)" :name="'Lettre — '.$app->candidate?->full_name" />
                                </p>
                            @endif
                            @if ($app->stage === 'rejected' && $app->rejection_reason)
                                <p class="mt-1 text-xs text-rose-700 dark:text-rose-300">Motif : {{ $app->rejection_reason }}</p>
                            @endif
                        </div>

                        <div class="flex items-center gap-1" aria-label="Note">
                            @for ($i = 1; $i <= 5; $i++)
                                <button type="button" @if ($canMove) wire:click="rate({{ $app->id }}, {{ $i }})" @else disabled @endif
                                        class="text-lg leading-none {{ ($app->rating ?? 0) >= $i ? 'text-amber-500' : 'text-slate-300' }}" aria-label="{{ $i }} sur 5">★</button>
                            @endfor
                        </div>
                    </div>

                    @if ($canMove && $next)
                        <div class="mt-3 grid gap-2 sm:grid-cols-4">
                            <select wire:model.live="moveTo.{{ $app->id }}" class="input py-1" aria-label="Étape suivante">
                                <option value="">Faire passer à…</option>
                                @foreach ($next as $to)<option value="{{ $to }}">{{ config('recruitment.stages.'.$to.'.label') }}</option>@endforeach
                            </select>

                            @if (($moveTo[$app->id] ?? '') === 'rejected')
                                <input type="text" wire:model="reasons.{{ $app->id }}" list="reasons-list" class="input py-1 sm:col-span-1" placeholder="Motif du refus (obligatoire)">
                            @else
                                <span class="hidden sm:block"></span>
                            @endif

                            <input type="text" wire:model="notes.{{ $app->id }}" class="input py-1" placeholder="Note (facultatif)">

                            <button type="button" wire:click="move({{ $app->id }})" @disabled(empty($moveTo[$app->id])) class="rounded-lg bg-indigo-600 px-3 py-1.5 text-sm font-semibold text-white hover:bg-indigo-500 disabled:opacity-50">Valider</button>
                        </div>
                        @if (($moveTo[$app->id] ?? '') === 'rejected' && $app->candidate?->email)
                            <label class="mt-2 flex items-center gap-2 text-sm"><input type="checkbox" wire:model="notify.{{ $app->id }}"> Informer le candidat par email (message courtois, sans le motif interne)</label>
                        @endif
                    @endif

                    @if ($app->stage === 'hired' && auth()->user()->canDo('create', 'employees'))
                        <div class="mt-3">
                            @if ($app->candidate?->employee_id)
                                <span class="rounded-full bg-emerald-100 px-3 py-1 text-xs text-emerald-800 dark:bg-emerald-500/20 dark:text-emerald-200">Fiche employé créée</span>
                            @else
                                <a href="{{ route('applications.convert', $app) }}" wire:navigate class="inline-flex items-center rounded-lg bg-indigo-600 px-3 py-1.5 text-sm font-semibold text-white hover:bg-indigo-500">Créer la fiche employé</a>
                            @endif
                        </div>
                    @endif

                    @if (auth()->user()->canDo('read', 'interviews'))
                        <livewire:recruitment.interview-panel :application-id="$app->id" :key="'iv-panel-'.$app->id" />
                    @endif
                </div>
            @empty
                <p class="rounded-2xl border border-dashed border-line px-4 py-8 text-center text-sm text-ink-muted">
                    Aucune candidature. @if ($canAdd) Proposez un profil de la banque de CV ci-dessus. @else Publiez l’offre pour pouvoir y ajouter des candidats. @endif
                </p>
            @endforelse

            <datalist id="reasons-list">
                @foreach (config('recruitment.rejection_reasons') as $r)<option value="{{ $r }}"></option>@endforeach
            </datalist>
        </section>
    @endif
</div>
