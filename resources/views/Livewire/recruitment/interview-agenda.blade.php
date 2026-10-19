<div class="space-y-6">
    <div>
        <h2 class="text-2xl font-bold">Entretiens</h2>
        <p class="text-sm text-ink-muted">Planifiez depuis la page d’une offre ; clôturez ici chaque entretien avec une note et un compte rendu.</p>
    </div>

    @error('interview')
        <p class="rounded-xl bg-rose-50 px-4 py-3 text-sm text-rose-800 dark:bg-rose-500/10 dark:text-rose-200">{{ $message }}</p>
    @enderror

    <div class="flex flex-wrap items-end gap-3 rounded-2xl border border-line bg-surface p-4 shadow-sm">
        <div>
            <label for="period" class="mb-1 block text-sm font-medium">Période</label>
            <select id="period" wire:model.live="period" class="input w-48">
                <option value="upcoming">À venir</option>
                <option value="past">Passés</option>
                <option value="all">Tous</option>
            </select>
        </div>
        <div>
            <label for="status" class="mb-1 block text-sm font-medium">Statut</label>
            <select id="status" wire:model.live="status" class="input w-48">
                <option value="">Tous</option>
                @foreach (\App\Models\Interview::STATUSES as $key => $label)<option value="{{ $key }}">{{ $label }}</option>@endforeach
            </select>
        </div>
        <label class="flex items-center gap-2 pb-2 text-sm"><input type="checkbox" wire:model.live="mine"> Seulement mes entretiens</label>
    </div>

    <div class="space-y-3">
        @forelse ($this->interviews as $iv)
            @php($canComplete = $iv->status === 'scheduled' && $iv->scheduled_at->isPast() && (auth()->user()->canDo('update', 'interviews') || (int) $iv->interviewer_id === (int) auth()->id()))
            <div wire:key="iv-{{ $iv->id }}" class="rounded-2xl border border-line bg-surface p-4 shadow-sm">
                <div class="flex flex-wrap items-start justify-between gap-3">
                    <div>
                        <p class="font-medium">
                            {{ $iv->application?->candidate?->full_name }}
                            <span class="ml-1 rounded-full bg-surface-muted px-2 py-0.5 text-xs">{{ $iv->statusLabel() }}</span>
                            @if ($iv->score)<span class="ml-1 text-amber-500">{{ str_repeat('★', $iv->score) }}</span>@endif
                        </p>
                        <p class="text-xs text-ink-muted">
                            {{ $iv->scheduled_at->format('d/m/Y à H:i') }} · {{ $iv->duration_minutes }} min · {{ $iv->modeLabel() }}@if ($iv->location) · {{ $iv->location }}@endif
                            @if ($iv->interviewer) · avec {{ $iv->interviewer->name }}@endif
                        </p>
                        @if ($iv->application?->jobOffer)
                            <p class="text-xs"><a href="{{ route('job-offers.show', $iv->application->jobOffer) }}" wire:navigate class="underline">{{ $iv->application->jobOffer->title }}</a></p>
                        @endif
                    </div>
                    @if ($canComplete && $completing !== $iv->id)
                        <button type="button" wire:click="startComplete({{ $iv->id }})" class="rounded-lg border border-line px-3 py-1.5 text-sm hover:bg-surface-muted">Clôturer</button>
                    @endif
                </div>

                @if ($iv->feedback)<p class="mt-2 whitespace-pre-line text-sm text-ink-muted">{{ $iv->feedback }}</p>@endif

                @if ($completing === $iv->id)
                    <form wire:submit="complete" class="mt-3 space-y-3 rounded-xl border border-line bg-surface-muted p-3">
                        <div class="grid gap-3 sm:grid-cols-3">
                            <div>
                                <label class="mb-1 block text-xs font-medium">Résultat</label>
                                <select wire:model.live="outcome" class="input py-1">
                                    <option value="done">Entretien réalisé</option>
                                    <option value="no_show">Candidat absent</option>
                                </select>
                            </div>
                            @if ($outcome === 'done')
                                <div>
                                    <label class="mb-1 block text-xs font-medium">Note (1 à 5)</label>
                                    <select wire:model="score" class="input py-1">
                                        @for ($n = 1; $n <= 5; $n++)<option value="{{ $n }}">{{ $n }}</option>@endfor
                                    </select>
                                </div>
                            @endif
                        </div>
                        <div>
                            <label class="mb-1 block text-xs font-medium">Compte rendu @if ($outcome === 'no_show')(facultatif)@endif</label>
                            <textarea wire:model="feedback" rows="3" class="input"></textarea>
                        </div>
                        <div class="flex justify-end gap-2">
                            <button type="button" wire:click="stopComplete" class="rounded-lg border border-line px-3 py-1.5 text-sm hover:bg-surface">Annuler</button>
                            <button type="submit" class="rounded-lg bg-indigo-600 px-3 py-1.5 text-sm font-semibold text-white hover:bg-indigo-500">Enregistrer</button>
                        </div>
                    </form>
                @endif
            </div>
        @empty
            <p class="rounded-2xl border border-dashed border-line px-4 py-10 text-center text-sm text-ink-muted">Aucun entretien pour ces critères. Planifiez-en un depuis la page d’une offre.</p>
        @endforelse
    </div>

    {{ $this->interviews->links() }}
</div>
