<div class="mt-4 border-t border-line pt-3">
    <div class="flex flex-wrap items-center justify-between gap-2">
        <p class="text-sm font-medium">Entretiens ({{ $this->interviews->count() }})</p>
        @if (! $open && auth()->user()->canDo('create', 'interviews') && ! in_array($this->application->stage, ['hired', 'rejected'], true))
            <button type="button" wire:click="openForm" class="rounded-lg border border-line px-3 py-1 text-xs hover:bg-surface-muted">Planifier un entretien</button>
        @endif
    </div>

    @error('interview')
        <p class="mt-2 rounded-lg bg-rose-50 px-3 py-2 text-xs text-rose-800 dark:bg-rose-500/10 dark:text-rose-200">{{ $message }}</p>
    @enderror

    @if ($this->interviews->isNotEmpty())
        <ul class="mt-2 space-y-2">
            @foreach ($this->interviews as $iv)
                <li wire:key="iv-{{ $iv->id }}" class="rounded-lg border border-line px-3 py-2 text-sm">
                    <div class="flex flex-wrap items-center justify-between gap-2">
                        <div>
                            <span class="font-medium">{{ $iv->scheduled_at->format('d/m/Y à H:i') }}</span>
                            <span class="text-ink-muted">· {{ $iv->duration_minutes }} min · {{ $iv->modeLabel() }}@if ($iv->location) · {{ $iv->location }}@endif @if ($iv->interviewer) · avec {{ $iv->interviewer->name }}@endif</span>
                            <span class="ml-1 rounded-full bg-surface-muted px-2 py-0.5 text-xs">{{ $iv->statusLabel() }}</span>
                            @if ($iv->score)<span class="ml-1 text-amber-500" title="Note">{{ str_repeat('★', $iv->score) }}</span>@endif
                        </div>
                        @if ($iv->status === 'scheduled' && auth()->user()->canDo('update', 'interviews'))
                            <div class="flex gap-2 text-xs">
                                <button type="button" wire:click="edit({{ $iv->id }})" class="rounded border border-line px-2 py-1 hover:bg-surface-muted">Déplacer</button>
                                <button type="button" wire:click="cancelInterview({{ $iv->id }})" wire:confirm="Annuler cet entretien ?" class="rounded border border-rose-300 px-2 py-1 text-rose-700 hover:bg-rose-50 dark:border-rose-500/40 dark:text-rose-300 dark:hover:bg-rose-500/10">Annuler</button>
                            </div>
                        @endif
                    </div>
                    @if ($iv->feedback)
                        <p class="mt-1 whitespace-pre-line text-xs text-ink-muted">{{ $iv->feedback }}</p>
                    @endif
                </li>
            @endforeach
        </ul>
    @endif

    @if ($open)
        <form wire:submit="save" class="mt-3 space-y-3 rounded-xl border border-line bg-surface-muted p-3">
            <p class="text-sm font-medium">{{ $editingId ? 'Déplacer l’entretien' : 'Nouvel entretien' }}</p>
            <div class="grid gap-3 sm:grid-cols-4">
                <div class="sm:col-span-2">
                    <label class="mb-1 block text-xs font-medium">Date et heure</label>
                    <input type="datetime-local" wire:model="scheduled_at" class="input py-1">
                    @error('scheduled_at')<p class="mt-1 text-xs text-rose-600">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="mb-1 block text-xs font-medium">Durée (min)</label>
                    <input type="number" min="15" max="480" step="15" wire:model="duration_minutes" class="input py-1">
                    @error('duration_minutes')<p class="mt-1 text-xs text-rose-600">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="mb-1 block text-xs font-medium">Modalité</label>
                    <select wire:model.live="mode" class="input py-1">
                        @foreach (\App\Models\Interview::MODES as $key => $label)<option value="{{ $key }}">{{ $label }}</option>@endforeach
                    </select>
                    @error('mode')<p class="mt-1 text-xs text-rose-600">{{ $message }}</p>@enderror
                </div>
                <div class="sm:col-span-2">
                    <label class="mb-1 block text-xs font-medium">{{ $mode === 'video' ? 'Lien de visioconférence' : 'Lieu' }}</label>
                    <input type="text" wire:model="location" class="input py-1" placeholder="{{ $mode === 'video' ? 'https://…' : 'Salle, adresse…' }}">
                    @error('location')<p class="mt-1 text-xs text-rose-600">{{ $message }}</p>@enderror
                </div>
                <div class="sm:col-span-2">
                    <label class="mb-1 block text-xs font-medium">Évaluateur</label>
                    <select wire:model="interviewer_id" class="input py-1">
                        <option value="">Non désigné</option>
                        @foreach ($this->users as $u)<option value="{{ $u->id }}">{{ $u->name }}</option>@endforeach
                    </select>
                    @error('interviewer_id')<p class="mt-1 text-xs text-rose-600">{{ $message }}</p>@enderror
                </div>
            </div>
            <label class="flex items-center gap-2 text-sm"><input type="checkbox" wire:model="notify"> Prévenir par email le candidat et l’évaluateur (avec invitation calendrier)</label>
            <div class="flex justify-end gap-2">
                <button type="button" wire:click="closeForm" class="rounded-lg border border-line px-3 py-1.5 text-sm hover:bg-surface">Annuler</button>
                <button type="submit" wire:loading.attr="disabled" wire:target="save" class="rounded-lg bg-indigo-600 px-3 py-1.5 text-sm font-semibold text-white hover:bg-indigo-500 disabled:opacity-60">{{ $editingId ? 'Enregistrer' : 'Planifier' }}</button>
            </div>
        </form>
    @endif
</div>
