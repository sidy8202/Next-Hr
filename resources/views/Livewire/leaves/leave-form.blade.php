@php($fmt = fn ($n) => (rtrim(rtrim(number_format((float) $n, 2, ',', ''), '0'), ',') ?: '0'))

<div class="space-y-6">
    <div>
        <h2 class="text-2xl font-bold">Demande de congé</h2>
        <p class="text-sm text-ink-muted">Votre demande est validée par votre chef de département (ou son intérimaire), puis par les ressources humaines. Une fiche de congé vous est ensuite envoyée.</p>
    </div>

    @error('leave')
        <p class="rounded-xl bg-rose-50 px-4 py-3 text-sm text-rose-800 dark:bg-rose-500/10 dark:text-rose-200">{{ $message }}</p>
    @enderror

    @if (! $this->target)
        <p class="rounded-xl bg-amber-50 px-4 py-3 text-sm text-amber-900 dark:bg-amber-500/10 dark:text-amber-200">Aucune fiche employé n’est rattachée à votre compte.@if ($this->asHr) Choisissez l’employé concerné ci-dessous.@endif</p>
    @endif

    <form wire:submit="save" class="space-y-6">
        @if ($this->asHr)
            <section class="rounded-2xl border border-line bg-surface p-5 shadow-sm">
                <label for="employee_id" class="mb-1 block text-sm font-medium">Employé concerné (ressources humaines)</label>
                <select id="employee_id" wire:model.live="employee_id" class="input max-w-md">
                    <option value="">Moi-même</option>
                    @foreach ($this->employees as $e)<option value="{{ $e->id }}">{{ $e->last_name }} {{ $e->first_name }} — {{ $e->matricule }}</option>@endforeach
                </select>
                @error('employee_id')<p class="mt-1 text-xs text-rose-600">{{ $message }}</p>@enderror
            </section>
        @endif

        <section class="rounded-2xl border border-line bg-surface p-5 shadow-sm">
            <h3 class="mb-4 text-sm font-semibold">Période</h3>
            <div class="grid gap-4 sm:grid-cols-2">
                <div class="sm:col-span-2">
                    <label for="leave_type_id" class="mb-1 block text-sm font-medium">Type de congé</label>
                    <select id="leave_type_id" wire:model.live="leave_type_id" class="input">
                        @foreach ($this->types as $t)<option value="{{ $t->id }}">{{ $t->name }}</option>@endforeach
                    </select>
                    @if ($this->selectedType)<p class="mt-1 text-xs text-ink-muted">{{ $this->selectedType->poolLabel() }}@if ($this->selectedType->requires_document) · justificatif obligatoire @endif</p>@endif
                    @error('leave_type_id')<p class="mt-1 text-xs text-rose-600">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label for="start_date" class="mb-1 block text-sm font-medium">Du</label>
                    <input id="start_date" type="date" wire:model.live="start_date" class="input">
                    <label class="mt-2 flex items-center gap-2 text-xs"><input type="checkbox" wire:model.live="start_half"> Je commence l’après-midi</label>
                    @error('start_date')<p class="mt-1 text-xs text-rose-600">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label for="end_date" class="mb-1 block text-sm font-medium">Au (inclus)</label>
                    <input id="end_date" type="date" wire:model.live="end_date" class="input">
                    <label class="mt-2 flex items-center gap-2 text-xs"><input type="checkbox" wire:model.live="end_half"> Je termine à midi</label>
                    @error('end_date')<p class="mt-1 text-xs text-rose-600">{{ $message }}</p>@enderror
                </div>
            </div>

            @if ($start_date && $end_date && $this->target)
                @php($p = $this->preview)
                <div class="mt-4 rounded-xl border border-line bg-surface-muted p-4 text-sm">
                    @if ($p['days'] > 0)
                        <p><strong>{{ $fmt($p['days']) }} jour(s) décompté(s)</strong> ({{ \App\Support\LeaveCalendar::modeLabel($this->target?->company_id) }}).</p>
                    @endif
                    @if ($p['available'] !== null)
                        <p class="text-ink-muted">Solde disponible : {{ $fmt($p['available']) }} j → après cette demande : <span class="{{ $p['after'] < 0 ? 'font-semibold text-rose-600' : '' }}">{{ $fmt($p['after']) }} j</span></p>
                    @endif
                    @if ($p['exception'])
                        <p class="mt-2 rounded-lg bg-amber-50 px-3 py-2 text-amber-900 dark:bg-amber-500/10 dark:text-amber-200">Votre solde sera dépassé de {{ $fmt(abs($p['after'])) }} j. C’est toléré par exception (jusqu’à {{ $fmt($p['tolerance']) }} j) : expliquez-le ci-dessous, la RH décidera.</p>
                    @endif
                    @foreach ($p['errors'] as $err)
                        <p class="mt-2 text-rose-700 dark:text-rose-300">{{ $err }}</p>
                    @endforeach
                </div>
            @endif
        </section>

        @if ($this->preview['exception'])
            <section class="rounded-2xl border border-amber-300 bg-surface p-5 shadow-sm">
                <label for="exception_reason" class="mb-1 block text-sm font-medium">Motif de l’exception (obligatoire)</label>
                <textarea id="exception_reason" rows="2" wire:model="exception_reason" class="input"></textarea>
                @error('exception_reason')<p class="mt-1 text-xs text-rose-600">{{ $message }}</p>@enderror
            </section>
        @endif

        <section class="rounded-2xl border border-line bg-surface p-5 shadow-sm">
            <h3 class="mb-4 text-sm font-semibold">Détails</h3>
            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <label for="interim" class="mb-1 block text-sm font-medium">Intérimaire pendant mon absence</label>
                    <select id="interim" wire:model="interim_employee_id" class="input">
                        <option value="">Aucun</option>
                        @foreach ($this->colleagues as $c)<option value="{{ $c->id }}">{{ $c->last_name }} {{ $c->first_name }}</option>@endforeach
                    </select>
                    <p class="mt-1 text-xs text-ink-muted">Chef de département : votre intérimaire pourra valider les demandes de votre équipe pendant votre congé.</p>
                    @error('interim_employee_id')<p class="mt-1 text-xs text-rose-600">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label for="attachment" class="mb-1 block text-sm font-medium">Justificatif @if ($this->selectedType?->requires_document)(obligatoire)@else(facultatif)@endif</label>
                    <input id="attachment" type="file" wire:model="attachment" accept=".pdf,.jpg,.jpeg,.png" class="block w-full text-sm">
                    <p wire:loading wire:target="attachment" class="mt-1 text-xs text-ink-muted">Envoi du fichier…</p>
                    @error('attachment')<p class="mt-1 text-xs text-rose-600">{{ $message }}</p>@enderror
                </div>
                <div class="sm:col-span-2">
                    <label for="reason" class="mb-1 block text-sm font-medium">Commentaire (facultatif)</label>
                    <textarea id="reason" rows="2" wire:model="reason" class="input"></textarea>
                    @error('reason')<p class="mt-1 text-xs text-rose-600">{{ $message }}</p>@enderror
                </div>
            </div>
        </section>

        <div class="flex justify-end gap-3">
            <a href="{{ route('leaves.mine') }}" wire:navigate class="inline-flex items-center rounded-xl border border-line px-4 py-2 text-sm hover:bg-surface-muted">Annuler</a>
            <button type="submit" wire:loading.attr="disabled" wire:target="save,attachment" @disabled(! $this->target || count($this->preview['errors']) > 0) class="inline-flex items-center rounded-xl bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-500 disabled:opacity-50">Envoyer la demande</button>
        </div>
    </form>
</div>
