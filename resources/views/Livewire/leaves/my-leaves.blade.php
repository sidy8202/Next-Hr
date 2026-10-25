@php
    $fmt = fn ($n) => (rtrim(rtrim(number_format((float) $n, 2, ',', ''), '0'), ',') ?: '0');
    $statusStyles = [
        'pending' => 'bg-amber-100 text-amber-900 dark:bg-amber-500/20 dark:text-amber-200',
        'approved' => 'bg-emerald-100 text-emerald-800 dark:bg-emerald-500/20 dark:text-emerald-200',
        'rejected' => 'bg-rose-100 text-rose-800 dark:bg-rose-500/20 dark:text-rose-200',
        'cancelled' => 'bg-slate-100 text-slate-700 dark:bg-slate-500/20 dark:text-slate-200',
    ];
    $ledgerLabels = [
        'accrual' => 'Acquisition mensuelle', 'taken' => 'Congé pris', 'refund' => 'Jours rendus', 'unjustified_absence' => 'Absence non justifiée',
        'recovery_credit' => 'Jour de récupération accordé', 'adjustment' => 'Ajustement', 'expiry' => 'Récupération expirée',
    ];
@endphp

<div class="space-y-6">
    <div class="flex flex-wrap items-center justify-between gap-3">
        <div>
            <h2 class="text-2xl font-bold">Mes congés</h2>
            <p class="text-sm text-ink-muted">Votre solde, vos demandes et leur avancement.</p>
        </div>
        <div class="flex flex-wrap gap-2">
            @if ($this->toValidate > 0)
                <a href="{{ route('leaves.inbox') }}" wire:navigate class="inline-flex items-center rounded-xl border border-amber-300 bg-amber-50 px-4 py-2 text-sm font-semibold text-amber-900 hover:bg-amber-100 dark:border-amber-500/40 dark:bg-amber-500/10 dark:text-amber-200">{{ $this->toValidate }} demande(s) à valider</a>
            @endif
            @if (auth()->user()->canDo('create', 'leave_requests') && $this->employee)
                <a href="{{ route('leaves.create') }}" wire:navigate class="inline-flex items-center rounded-xl bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-500">Nouvelle demande de congé</a>
            @endif
        </div>
    </div>

    @if (session('status'))
        <p class="rounded-xl bg-emerald-50 px-4 py-3 text-sm text-emerald-900 dark:bg-emerald-500/10 dark:text-emerald-200">{{ session('status') }}</p>
    @endif

    @if (! $this->employee)
        <p class="rounded-xl bg-amber-50 px-4 py-3 text-sm text-amber-900 dark:bg-amber-500/10 dark:text-amber-200">Aucune fiche employé n’est rattachée à votre compte : vous n’avez pas de solde de congé personnel.</p>
    @else
        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
            <div class="rounded-2xl border border-line bg-surface p-4 shadow-sm">
                <p class="text-xs text-ink-muted">Solde de congé disponible</p>
                <p class="mt-1 text-3xl font-bold {{ $this->summary['available'] < 0 ? 'text-rose-600' : '' }}">{{ $fmt($this->summary['available']) }} j</p>
                <p class="mt-1 text-xs text-ink-muted">Solde {{ $fmt($this->summary['balance']) }} j @if ($this->summary['reserved'] > 0) − {{ $fmt($this->summary['reserved']) }} j en attente de validation @endif</p>
            </div>
            <div class="rounded-2xl border border-line bg-surface p-4 shadow-sm">
                <p class="text-xs text-ink-muted">Acquisition</p>
                <p class="mt-1 text-3xl font-bold">{{ $fmt($this->summary['rate']) }} j</p>
                <p class="mt-1 text-xs text-ink-muted">par mois de présence révolu</p>
            </div>
            <div class="rounded-2xl border border-line bg-surface p-4 shadow-sm sm:col-span-2">
                <p class="text-xs text-ink-muted">Jours de récupération (jours de repos travaillés)</p>
                <p class="mt-1 text-3xl font-bold">{{ $fmt($this->summary['recovery']) }} j</p>
                @foreach ($this->summary['credits'] as $credit)
                    <p class="mt-1 text-xs text-ink-muted">{{ $fmt($credit['remaining']) }} j à prendre @if ($credit['expires_on']) avant le {{ \Carbon\Carbon::parse($credit['expires_on'])->format('d/m/Y') }} @endif</p>
                @endforeach
            </div>
        </div>

        <section class="space-y-2">
            <h3 class="text-sm font-semibold">Mes demandes</h3>
            <div class="overflow-x-auto rounded-2xl border border-line bg-surface shadow-sm">
                <table class="w-full text-left text-sm">
                    <thead class="bg-surface-muted text-xs text-ink-muted">
                        <tr><th class="px-4 py-2">Type</th><th class="px-4 py-2">Période</th><th class="px-4 py-2">Jours</th><th class="px-4 py-2">Statut</th><th class="px-4 py-2"></th></tr>
                    </thead>
                    <tbody class="divide-y divide-line">
                        @forelse ($this->requests as $r)
                            <tr wire:key="req-{{ $r->id }}">
                                <td class="px-4 py-3">{{ $r->type?->name }}@if ($r->exceeds_balance) <span class="ml-1 rounded-full bg-amber-100 px-2 py-0.5 text-xs text-amber-900" title="Solde dépassé, toléré par exception">exception</span>@endif</td>
                                <td class="px-4 py-3">{{ $r->periodLabel() }}</td>
                                <td class="px-4 py-3">{{ $fmt($r->days) }}</td>
                                <td class="px-4 py-3"><span class="rounded-full px-2 py-0.5 text-xs {{ $statusStyles[$r->status] ?? '' }}">{{ $r->statusLabel() }}</span></td>
                                <td class="px-4 py-3 text-right"><a href="{{ route('leaves.show', $r->id) }}" wire:navigate class="rounded border border-line px-2 py-1 text-xs hover:bg-surface-muted">Détail</a></td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="px-4 py-8 text-center text-ink-muted">Aucune demande pour le moment.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>

        <section class="space-y-2">
            <h3 class="text-sm font-semibold">Historique de mon solde</h3>
            <div class="overflow-x-auto rounded-2xl border border-line bg-surface shadow-sm">
                <table class="w-full text-left text-sm">
                    <tbody class="divide-y divide-line">
                        @forelse ($this->ledger as $row)
                            <tr>
                                <td class="px-4 py-2 text-ink-muted">{{ \Carbon\Carbon::parse($row->effective_date)->format('d/m/Y') }}</td>
                                <td class="px-4 py-2">{{ $ledgerLabels[$row->type] ?? $row->type }} @if ($row->pool === 'recovery') <span class="text-xs text-ink-muted">(récupération)</span>@endif<span class="block text-xs text-ink-muted">{{ $row->note }}</span></td>
                                <td class="px-4 py-2 text-right font-medium {{ $row->days < 0 ? 'text-rose-600' : 'text-emerald-600' }}">{{ $row->days > 0 ? '+' : '' }}{{ $fmt($row->days) }} j</td>
                            </tr>
                        @empty
                            <tr><td class="px-4 py-6 text-center text-ink-muted">Aucune écriture pour le moment.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>
    @endif
</div>
