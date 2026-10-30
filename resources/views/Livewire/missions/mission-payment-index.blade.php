@php($fcfa = fn ($v) => number_format((int) $v, 0, ',', ' ').' FCFA')

<div class="space-y-6">
    <div>
        <h2 class="text-2xl font-bold">Paiements des perdiems</h2>
        <p class="text-sm text-ink-muted">Toutes les tranches des missions approuvées. Ouvrez la mission pour enregistrer un paiement, joindre un reçu ou imprimer la fiche de perdiem.</p>
    </div>

    <div class="grid gap-4 sm:grid-cols-3">
        <div class="rounded-2xl border border-line bg-surface p-4 shadow-sm"><p class="text-xs text-ink-muted">Reste à payer</p><p class="mt-1 text-xl font-bold">{{ $fcfa($this->kpis['due']) }}</p></div>
        <div class="rounded-2xl border border-line bg-surface p-4 shadow-sm"><p class="text-xs text-ink-muted">Tranches en retard</p><p class="mt-1 text-xl font-bold {{ $this->kpis['overdue'] ? 'text-rose-600' : '' }}">{{ $this->kpis['overdue'] }}</p></div>
        <div class="rounded-2xl border border-line bg-surface p-4 shadow-sm"><p class="text-xs text-ink-muted">Payé ce mois</p><p class="mt-1 text-xl font-bold">{{ $fcfa($this->kpis['paidMonth']) }}</p></div>
    </div>

    <div class="max-w-xs">
        <select wire:model.live="status" class="input">
            <option value="due">À payer</option>
            <option value="overdue">En retard</option>
            <option value="paid">Payées</option>
            <option value="all">Toutes</option>
        </select>
    </div>

    <div class="overflow-x-auto rounded-2xl border border-line bg-surface shadow-sm">
        <table class="w-full text-left text-sm">
            <thead class="bg-surface-muted text-xs text-ink-muted"><tr><th class="px-4 py-2">Mission</th><th class="px-4 py-2">Bénéficiaire</th><th class="px-4 py-2">Tranche</th><th class="px-4 py-2 text-right">Montant</th><th class="px-4 py-2">Échéance</th><th class="px-4 py-2">Statut</th><th class="px-4 py-2"></th></tr></thead>
            <tbody class="divide-y divide-line">
                @forelse ($this->payments as $p)
                    <tr wire:key="pay-{{ $p->id }}">
                        <td class="px-4 py-3 font-mono text-xs">{{ $p->mission?->reference }}<span class="block font-sans text-ink-muted">{{ $p->mission?->destination_zone }}</span></td>
                        <td class="px-4 py-3">{{ $p->participant?->employee?->full_name }}<span class="block text-xs text-ink-muted">{{ $p->participant?->employee?->matricule }} · {{ $p->participant?->department?->name }}</span></td>
                        <td class="px-4 py-3">{{ $p->label }}</td>
                        <td class="px-4 py-3 text-right">{{ $fcfa($p->status === 'paid' ? $p->paid_amount : $p->planned_amount) }}</td>
                        <td class="px-4 py-3">{{ $p->planned_date?->format('d/m/Y') ?? '—' }}</td>
                        <td class="px-4 py-3">
                            @if ($p->status === 'paid')<span class="rounded-full bg-emerald-100 px-2 py-0.5 text-xs text-emerald-800">Payé le {{ $p->paid_at?->format('d/m/Y') }}</span>
                            @elseif ($p->isOverdue())<span class="rounded-full bg-rose-100 px-2 py-0.5 text-xs text-rose-800">En retard</span>
                            @else<span class="rounded-full bg-amber-100 px-2 py-0.5 text-xs text-amber-900">À payer</span>@endif
                        </td>
                        <td class="px-4 py-3 text-right"><a href="{{ route('missions.show', $p->mission_id) }}" wire:navigate class="rounded border border-line px-2 py-1 text-xs hover:bg-surface-muted">Ouvrir</a></td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="px-4 py-10 text-center text-ink-muted">Aucune tranche dans cette vue.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{ $this->payments->links() }}
</div>
