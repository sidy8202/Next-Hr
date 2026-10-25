@php
    $fmt = fn ($n) => (rtrim(rtrim(number_format((float) $n, 2, ',', ''), '0'), ',') ?: '0');
    $stepStyles = [
        'pending' => 'bg-amber-100 text-amber-900 dark:bg-amber-500/20 dark:text-amber-200',
        'approved' => 'bg-emerald-100 text-emerald-800 dark:bg-emerald-500/20 dark:text-emerald-200',
        'rejected' => 'bg-rose-100 text-rose-800 dark:bg-rose-500/20 dark:text-rose-200',
        'skipped' => 'bg-slate-100 text-slate-700 dark:bg-slate-500/20 dark:text-slate-200',
        'cancelled' => 'bg-slate-100 text-slate-700 dark:bg-slate-500/20 dark:text-slate-200',
    ];
    $stepLabels = ['pending' => 'En attente', 'approved' => 'Validée', 'rejected' => 'Refusée', 'skipped' => 'Sautée', 'cancelled' => 'Annulée'];
@endphp

<div class="space-y-6">
    <div class="flex flex-wrap items-start justify-between gap-3">
        <div>
            <h2 class="text-2xl font-bold">{{ $leave->type?->name }}</h2>
            <p class="text-sm text-ink-muted">{{ $leave->employee?->full_name }} · {{ $leave->periodLabel() }} · {{ $fmt($leave->days) }} jour(s)</p>
        </div>
        <div class="flex flex-wrap items-center gap-2">
            <span class="rounded-full bg-surface-muted px-3 py-1 text-sm font-medium">{{ $leave->statusLabel() }}</span>
            @if ($canCancel)
                <button type="button" wire:click="cancel" wire:confirm="Annuler cette demande ?" class="rounded-lg border border-rose-300 px-3 py-1.5 text-sm text-rose-700 hover:bg-rose-50 dark:border-rose-500/40 dark:text-rose-300 dark:hover:bg-rose-500/10">Annuler la demande</button>
            @endif
        </div>
    </div>

    @if (session('status'))
        <p class="rounded-xl bg-emerald-50 px-4 py-3 text-sm text-emerald-900 dark:bg-emerald-500/10 dark:text-emerald-200">{{ session('status') }}</p>
    @endif
    @error('leave')
        <p class="rounded-xl bg-rose-50 px-4 py-3 text-sm text-rose-800 dark:bg-rose-500/10 dark:text-rose-200">{{ $message }}</p>
    @enderror

    @if ($leave->status === 'approved')
        <section class="rounded-2xl border border-emerald-300 bg-emerald-50 p-5 dark:border-emerald-500/40 dark:bg-emerald-500/10">
            <p class="font-semibold text-emerald-900 dark:text-emerald-200">Congé validé. Bonne jouissance de votre congé !</p>
            <p class="mt-1 text-sm text-emerald-900 dark:text-emerald-200">Profitez-en pleinement et reposez-vous bien.</p>
            @if ($leave->document)
                <p class="mt-3 text-sm">Fiche de congé
                    <x-document-actions :view="route('leaves.sheet.view', $leave->id)" :download="route('leaves.sheet', $leave->id)" :ext="pathinfo($leave->document->file_path, PATHINFO_EXTENSION)" name="Fiche de congé" />
                </p>
            @endif
        </section>
    @endif

    <section class="rounded-2xl border border-line bg-surface p-5 shadow-sm">
        <dl class="grid gap-4 text-sm sm:grid-cols-3">
            <div><dt class="text-ink-muted">Début</dt><dd class="font-medium">{{ $leave->start_date->translatedFormat('l d/m/Y') }}@if ($leave->start_half) (après-midi)@endif</dd></div>
            <div><dt class="text-ink-muted">Fin</dt><dd class="font-medium">{{ $leave->end_date->translatedFormat('l d/m/Y') }}@if ($leave->end_half) (jusqu’à midi)@endif</dd></div>
            <div><dt class="text-ink-muted">Durée</dt><dd class="font-medium">{{ $fmt($leave->days) }} jour(s)</dd></div>
            @if ($leave->interim)<div><dt class="text-ink-muted">Intérimaire</dt><dd class="font-medium">{{ $leave->interim->full_name }}</dd></div>@endif
            @if ($leave->balance_before !== null)<div><dt class="text-ink-muted">Solde disponible au dépôt</dt><dd class="font-medium">{{ $fmt($leave->balance_before) }} j</dd></div>@endif
            @if ($leave->balance_after !== null)<div><dt class="text-ink-muted">Solde après validation</dt><dd class="font-medium">{{ $fmt($leave->balance_after) }} j</dd></div>@endif
        </dl>
        @if ($leave->reason)<p class="mt-4 whitespace-pre-line text-sm">{{ $leave->reason }}</p>@endif
        @if ($leave->exceeds_balance)
            <p class="mt-4 rounded-lg bg-amber-50 px-3 py-2 text-sm text-amber-900 dark:bg-amber-500/10 dark:text-amber-200"><strong>Solde dépassé, toléré par exception.</strong> Motif : {{ $leave->exception_reason }}</p>
        @endif
        @if ($leave->attachment_path)
            <p class="mt-4 text-sm">Justificatif
                <x-document-actions :view="route('leaves.attachment.view', $leave->id)" :download="route('leaves.attachment', $leave->id)" :ext="pathinfo($leave->attachment_path, PATHINFO_EXTENSION)" name="Justificatif" />
            </p>
        @endif
    </section>

    <section class="rounded-2xl border border-line bg-surface p-5 shadow-sm">
        <h3 class="mb-3 text-sm font-semibold">Validation</h3>
        <ol class="space-y-3">
            @foreach ($leave->steps as $step)
                <li class="flex flex-wrap items-start justify-between gap-2 text-sm">
                    <div>
                        <p class="font-medium">{{ $step->kindLabel() }}</p>
                        @if ($step->decider)
                            <p class="text-xs text-ink-muted">{{ $step->decider->name }}@if ($step->acted_as && $step->acted_as !== $step->kind) ({{ \App\Models\ApprovalStep::ACTED_AS[$step->acted_as] ?? $step->acted_as }})@endif · {{ $step->decided_at?->format('d/m/Y H:i') }}</p>
                        @endif
                        @if ($step->comment)<p class="text-xs text-ink-muted">{{ $step->comment }}</p>@endif
                    </div>
                    <span class="rounded-full px-2 py-0.5 text-xs {{ $stepStyles[$step->status] ?? '' }}">{{ $stepLabels[$step->status] ?? $step->status }}</span>
                </li>
            @endforeach
        </ol>
    </section>

    <a href="{{ route('leaves.mine') }}" wire:navigate class="inline-flex items-center rounded-xl border border-line px-4 py-2 text-sm hover:bg-surface-muted">Retour à mes congés</a>
</div>
