@php($fmt = fn ($n) => (rtrim(rtrim(number_format((float) $n, 2, ',', ''), '0'), ',') ?: '0'))
@php($isHours = $this->selectedType?->kind === 'hours')

<div class="space-y-6">
    <div>
        <h2 class="text-2xl font-bold">Demande de permission</h2>
        <p class="text-sm text-ink-muted">Validée par votre chef de département (ou son intérimaire), puis par les ressources humaines. Une fiche de permission vous est ensuite envoyée.</p>
    </div>

    @error('permission')
        <p class="rounded-xl bg-rose-50 px-4 py-3 text-sm text-rose-800 dark:bg-rose-500/10 dark:text-rose-200">{{ $message }}</p>
    @enderror

    <form wire:submit="save" class="space-y-6">
        @if ($this->asHr)
            <section class="rounded-2xl border border-line bg-surface p-5 shadow-sm">
                <label class="mb-1 block text-sm font-medium">Employé concerné (ressources humaines)</label>
                <select wire:model.live="employee_id" class="input max-w-md">
                    <option value="">Moi-même</option>
                    @foreach ($this->employees as $e)<option value="{{ $e->id }}">{{ $e->last_name }} {{ $e->first_name }} — {{ $e->matricule }}</option>@endforeach
                </select>
            </section>
        @endif

        <section class="rounded-2xl border border-line bg-surface p-5 shadow-sm">
            <div class="grid gap-4 sm:grid-cols-3">
                <div class="sm:col-span-3">
                    <label class="mb-1 block text-sm font-medium">Type de permission</label>
                    <select wire:model.live="permission_type_id" class="input">
                        @foreach ($this->types as $t)<option value="{{ $t->id }}">{{ $t->name }}</option>@endforeach
                    </select>
                    @if ($this->selectedType)<p class="mt-1 text-xs text-ink-muted">{{ $this->selectedType->summary() }} · {{ \App\Models\PermissionType::POLICIES[$this->selectedType->excess_policy] }}@if ($this->selectedType->requires_document) · justificatif obligatoire @endif</p>@endif
                    @error('permission_type_id')<p class="mt-1 text-xs text-rose-600">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="mb-1 block text-sm font-medium">{{ $isHours ? 'Date' : 'Du' }}</label>
                    <input type="date" wire:model.live="start_date" class="input">
                    @error('start_date')<p class="mt-1 text-xs text-rose-600">{{ $message }}</p>@enderror
                </div>
                @if ($isHours)
                    <div><label class="mb-1 block text-sm font-medium">De</label><input type="time" wire:model.live="start_time" class="input">@error('start_time')<p class="mt-1 text-xs text-rose-600">{{ $message }}</p>@enderror</div>
                    <div><label class="mb-1 block text-sm font-medium">À</label><input type="time" wire:model.live="end_time" class="input">@error('end_time')<p class="mt-1 text-xs text-rose-600">{{ $message }}</p>@enderror</div>
                @else
                    <div><label class="mb-1 block text-sm font-medium">Au (inclus)</label><input type="date" wire:model.live="end_date" class="input">@error('end_date')<p class="mt-1 text-xs text-rose-600">{{ $message }}</p>@enderror</div>
                @endif
            </div>

            @if ($start_date && $this->target)
                @php($p = $this->preview)
                @if ($p['days'] > 0 || $p['errors'])
                    <div class="mt-4 rounded-xl border border-line bg-surface-muted p-4 text-sm">
                        @if ($p['days'] > 0)
                            <p><strong>{{ $fmt($p['days']) }} jour(s)</strong>@if ($p['hours']) ({{ $fmt($p['hours']) }} h)@endif :
                                {{ $fmt($p['free']) }} j accordé(s) sans retenue@if ($p['deducted'] > 0), <span class="font-semibold text-amber-700">{{ $fmt($p['deducted']) }} j retiré(s) de votre congé</span>@endif@if ($p['unpaid'] > 0), <span class="font-semibold text-amber-700">{{ $fmt($p['unpaid']) }} j non payé(s)</span>@endif.</p>
                        @endif
                        @if ($p['available'] !== null)<p class="text-ink-muted">Solde de congé disponible : {{ $fmt($p['available']) }} j → après : {{ $fmt($p['after']) }} j</p>@endif
                        @if ($p['exception'])<p class="mt-2 rounded-lg bg-amber-50 px-3 py-2 text-amber-900">Votre solde sera dépassé : toléré par exception (jusqu’à {{ $fmt($p['tolerance']) }} j). Expliquez-le ci-dessous.</p>@endif
                        @foreach ($p['errors'] as $err)<p class="mt-2 text-rose-700 dark:text-rose-300">{{ $err }}</p>@endforeach
                    </div>
               
            @endif
        </section>

        @if ($this->preview['exception'])
            <section class="rounded-2xl border border-amber-300 bg-surface p-5 shadow-sm">
                <label class="mb-1 block text-sm font-medium">Motif de l’exception (obligatoire)</label>
                <textarea rows="2" wire:model="exception_reason" class="input"></textarea>
            </section>
        @endif

        <section class="rounded-2xl border border-line bg-surface p-5 shadow-sm">
            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <label class="mb-1 block text-sm font-medium">Justificatif @if ($this->selectedType?->requires_document)(obligatoire)@else(facultatif)@endif</label>
                    <input type="file" wire:model="attachment" accept=".pdf,.jpg,.jpeg,.png" class="block w-full text-sm">
                    <p wire:loading wire:target="attachment" class="mt-1 text-xs text-ink-muted">Envoi du fichier…</p>
                    @error('attachment')<p class="mt-1 text-xs text-rose-600">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="mb-1 block text-sm font-medium">Motif de la demande</label>
                    <textarea rows="2" wire:model="reason" class="input"></textarea>
                </div>
            </div>
        </section>

        <div class="flex justify-end gap-3">
            <a href="{{ route('permissions.index') }}" wire:navigate class="inline-flex items-center rounded-xl border border-line px-4 py-2 text-sm hover:bg-surface-muted">Annuler</a>
            <button type="submit" wire:loading.attr="disabled" wire:target="save,attachment" @disabled(! $this->target || count($this->preview['errors']) > 0) class="inline-flex items-center rounded-xl bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-500 disabled:opacity-50">Envoyer la demande</button>
        </div>
    </form>
</div>
