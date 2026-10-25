@php($fmt = fn ($n) => (rtrim(rtrim(number_format((float) $n, 2, ',', ''), '0'), ',') ?: '0'))

<div class="space-y-6">
    <div>
        <h2 class="text-2xl font-bold">Demandes à valider</h2>
        <p class="text-sm text-ink-muted">Les demandes qui attendent votre décision : en tant que chef de département, intérimaire ou ressources humaines. Vous ne validez jamais votre propre demande.</p>
    </div>

    @error('decide')
        <p class="rounded-xl bg-rose-50 px-4 py-3 text-sm text-rose-800 dark:bg-rose-500/10 dark:text-rose-200">{{ $message }}</p>
    @enderror

    @forelse ($this->items as $item)
        @php($step = $item['step'])
        @php($subject = $item['subject'])
        @php($employee = $subject->employee)
        <div wire:key="step-{{ $step->id }}" class="space-y-3 rounded-2xl border border-line bg-surface p-5 shadow-sm">
            <div class="flex flex-wrap items-start justify-between gap-2">
                <div>
                    <p class="font-semibold">{{ $employee?->full_name }} <span class="font-normal text-ink-muted">· {{ $employee?->matricule }}</span></p>
                    <p class="text-sm">{{ $item['handler']->label($subject) }}</p>
                </div>
                <span class="rounded-full bg-surface-muted px-3 py-1 text-xs">Étape : {{ \App\Models\ApprovalStep::ACTED_AS[$item['capacity']] ?? $step->kindLabel() }}</span>
            </div>

            @if ($step->approvable_type === 'leave')
                @if ($subject->reason)<p class="whitespace-pre-line text-sm text-ink-muted">{{ $subject->reason }}</p>@endif
                @if ($subject->interim)<p class="text-xs text-ink-muted">Intérimaire désigné : {{ $subject->interim->full_name }}</p>@endif
                @if ($subject->balance_before !== null)<p class="text-xs text-ink-muted">Solde disponible au dépôt : {{ $fmt($subject->balance_before) }} j</p>@endif
                @if ($subject->exceeds_balance)
                    <p class="rounded-lg bg-amber-50 px-3 py-2 text-sm text-amber-900 dark:bg-amber-500/10 dark:text-amber-200"><strong>Solde dépassé (exception).</strong> Motif de l’employé : {{ $subject->exception_reason }}</p>
                @endif
                @if (($n = $this->overlaps($subject)) > 0)
                    <p class="text-xs text-amber-700 dark:text-amber-300">{{ $n }} collègue(s) du même département ont aussi une demande ou un congé sur cette période.</p>
                @endif
                @if ($subject->attachment_path)
                    <p class="text-xs text-ink-muted">Justificatif
                        <x-document-actions :view="route('leaves.attachment.view', $subject->id)" :download="route('leaves.attachment', $subject->id)" :ext="pathinfo($subject->attachment_path, PATHINFO_EXTENSION)" :name="'Justificatif — '.$employee?->full_name" />
                    </p>
                @endif
            @elseif ($step->approvable_type === 'overtime')
                @if ($subject->reason)<p class="text-sm text-ink-muted">{{ $subject->reason }}</p>@endif
                @if ($step->kind === 'hr')
                    <div class="grid gap-3 rounded-xl border border-line bg-surface-muted p-3 sm:grid-cols-3">
                        <div class="sm:col-span-2">
                            <label class="mb-1 block text-xs font-medium">Compensation proposée à l’employé</label>
                            <select wire:model="compensation.{{ $step->id }}" class="input py-1">
                                <option value="">Choisir…</option>
                                @foreach (\App\Models\OvertimeRecord::COMPENSATIONS as $key => $label)<option value="{{ $key }}">{{ $label }}</option>@endforeach
                            </select>
                        </div>
                        <div>
                            <label class="mb-1 block text-xs font-medium">Jours accordés (0,25 à 1)</label>
                            <input type="number" step="0.25" min="0.25" max="1" wire:model="credited.{{ $step->id }}" class="input py-1" placeholder="{{ $fmt($subject->days) }}">
                        </div>
                    </div>
                @endif
            @endif

            <div>
                <label class="mb-1 block text-xs font-medium">Commentaire (obligatoire en cas de refus)</label>
                <input type="text" wire:model="comments.{{ $step->id }}" class="input py-1">
            </div>

            <div class="flex flex-wrap justify-end gap-2">
                <a href="{{ $item['handler']->url($subject) }}" wire:navigate class="rounded-lg border border-line px-3 py-1.5 text-sm hover:bg-surface-muted">Voir le détail</a>
                <button type="button" wire:click="decide({{ $step->id }}, 'reject')" wire:loading.attr="disabled" class="rounded-lg border border-rose-300 px-3 py-1.5 text-sm text-rose-700 hover:bg-rose-50 dark:border-rose-500/40 dark:text-rose-300 dark:hover:bg-rose-500/10">Refuser</button>
                <button type="button" wire:click="decide({{ $step->id }}, 'approve')" wire:loading.attr="disabled" class="rounded-lg bg-indigo-600 px-3 py-1.5 text-sm font-semibold text-white hover:bg-indigo-500">Valider</button>
            </div>
        </div>
    @empty
        <p class="rounded-2xl border border-dashed border-line px-4 py-10 text-center text-sm text-ink-muted">Rien à valider pour le moment.</p>
    @endforelse
</div>
