@php
    $fmt = fn ($n) => (rtrim(rtrim(number_format((float) $n, 2, ',', ''), '0'), ',') ?: '0');
    $labels = ['pending' => 'En attente', 'approved' => 'Validée', 'rejected' => 'Refusée', 'skipped' => 'Sautée', 'cancelled' => 'Annulée'];
@endphp

<div class="space-y-6">
    <div class="flex flex-wrap items-start justify-between gap-3">
        <div>
            <h2 class="text-2xl font-bold">{{ $permission->type?->name }}</h2>
            <p class="text-sm text-ink-muted">{{ $permission->employee?->full_name }} · {{ $permission->periodLabel() }} · {{ $fmt($permission->days) }} jour(s)</p>
        </div>
        <div class="flex flex-wrap items-center gap-2">
            <span class="rounded-full bg-surface-muted px-3 py-1 text-sm font-medium">{{ $permission->statusLabel() }}</span>
            @if ($canCancel)
                <button type="button" wire:click="cancel" wire:confirm="Annuler cette demande ?" class="rounded-lg border border-rose-300 px-3 py-1.5 text-sm text-rose-700 hover:bg-rose-50">Annuler la demande</button>
            @endif
        </div>
    </div>

    @if (session('status'))<p class="rounded-xl bg-emerald-50 px-4 py-3 text-sm text-emerald-900 dark:bg-emerald-500/10 dark:text-emerald-200">{{ session('status') }}</p>@endif
    @error('permission')<p class="rounded-xl bg-rose-50 px-4 py-3 text-sm text-rose-800 dark:bg-rose-500/10 dark:text-rose-200">{{ $message }}</p>@enderror

    @if ($permission->status === 'approved' && $permission->document)
        <section class="rounded-2xl border border-emerald-300 bg-emerald-50 p-5 dark:border-emerald-500/40 dark:bg-emerald-500/10">
            <p class="font-semibold text-emerald-900 dark:text-emerald-200">Permission accordée.</p>
            <p class="mt-2 text-sm">Fiche de permission
                <x-document-actions :view="route('permissions.sheet.view', $permission->id)" :download="route('permissions.sheet', $permission->id)" :ext="pathinfo($permission->document->file_path, PATHINFO_EXTENSION)" name="Fiche de permission" />
            </p>
        </section>
    @endif

    <section class="rounded-2xl border border-line bg-surface p-5 shadow-sm">
        <dl class="grid gap-4 text-sm sm:grid-cols-3">
            <div><dt class="text-ink-muted">Durée</dt><dd class="font-medium">{{ $fmt($permission->days) }} j @if ($permission->hours)({{ $fmt($permission->hours) }} h)@endif</dd></div>
            <div><dt class="text-ink-muted">Accordé sans retenue</dt><dd class="font-medium">{{ $fmt($permission->free_days) }} j</dd></div>
            <div><dt class="text-ink-muted">Retiré du congé annuel</dt><dd class="font-medium">{{ $fmt($permission->deducted_days) }} j</dd></div>
            @if ($permission->unpaid_days > 0)<div><dt class="text-ink-muted">Non payé</dt><dd class="font-medium">{{ $fmt($permission->unpaid_days) }} j</dd></div>@endif
            @if ($permission->balance_after !== null)<div><dt class="text-ink-muted">Solde de congé après</dt><dd class="font-medium">{{ $fmt($permission->balance_after) }} j</dd></div>@endif
        </dl>
        @if ($permission->reason)<p class="mt-4 whitespace-pre-line text-sm">{{ $permission->reason }}</p>@endif
        @if ($permission->exceeds_balance)<p class="mt-4 rounded-lg bg-amber-50 px-3 py-2 text-sm text-amber-900"><strong>Solde dépassé, toléré par exception.</strong> Motif : {{ $permission->exception_reason }}</p>@endif
        @if ($permission->attachment_path)
            <p class="mt-4 text-sm">Justificatif
                <x-document-actions :view="route('permissions.attachment.view', $permission->id)" :download="route('permissions.attachment', $permission->id)" :ext="pathinfo($permission->attachment_path, PATHINFO_EXTENSION)" name="Justificatif" />
            </p>
        @endif
    </section>

    <section class="rounded-2xl border border-line bg-surface p-5 shadow-sm">
        <h3 class="mb-3 text-sm font-semibold">Validation</h3>
        <ol class="space-y-3">
            @foreach ($permission->steps as $step)
                <li class="flex flex-wrap items-start justify-between gap-2 text-sm">
                    <div>
                        <p class="font-medium">{{ $step->kindLabel() }}</p>
                        @if ($step->decider)<p class="text-xs text-ink-muted">{{ $step->decider->name }}@if ($step->acted_as && $step->acted_as !== $step->kind) ({{ \App\Models\ApprovalStep::ACTED_AS[$step->acted_as] ?? $step->acted_as }})@endif · {{ $step->decided_at?->format('d/m/Y H:i') }}</p>@endif
                        @if ($step->comment)<p class="text-xs text-ink-muted">{{ $step->comment }}</p>@endif
                    </div>
                    <span class="rounded-full bg-surface-muted px-2 py-0.5 text-xs">{{ $labels[$step->status] ?? $step->status }}</span>
                </li>
            @endforeach
        </ol>
    </section>

    <a href="{{ route('permissions.index') }}" wire:navigate class="inline-flex items-center rounded-xl border border-line px-4 py-2 text-sm hover:bg-surface-muted">Retour aux permissions</a>
</div>
