@php
    $styles = ['awaiting_response' => 'bg-amber-100 text-amber-900', 'responded' => 'bg-sky-100 text-sky-800', 'decided' => 'bg-emerald-100 text-emerald-800', 'withdrawn' => 'bg-slate-100 text-slate-700'];
@endphp

<div class="space-y-6">
    <div class="flex flex-wrap items-center justify-between gap-3">
        <div>
            <h2 class="text-2xl font-bold">Demandes d’explication</h2>
            <p class="text-sm text-ink-muted">Lettre du chef → réponse de l’employé → décision de la RH. À chaque étape, la RH est prévenue en même temps que l’employé, et chaque lettre est générée en PDF.</p>
        </div>
        <div class="flex flex-wrap gap-2">
            @if (auth()->user()->canDo('read', 'settings'))
                <a href="{{ route('letterhead.edit') }}" wire:navigate class="inline-flex items-center rounded-xl border border-line px-4 py-2 text-sm hover:bg-surface-muted">En-tête des lettres</a>
            @endif
            @if (auth()->user()->canDo('create', 'explanation_letters'))
                <a href="{{ route('explanations.create') }}" wire:navigate class="inline-flex items-center rounded-xl bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-500">Nouvelle demande d’explication</a>
            @endif
        </div>
    </div>

    @if (session('status'))<p class="rounded-xl bg-emerald-50 px-4 py-3 text-sm text-emerald-900 dark:bg-emerald-500/10 dark:text-emerald-200">{{ session('status') }}</p>@endif

    <div class="max-w-xs">
        <select wire:model.live="status" class="input">
            <option value="">Tous les statuts</option>
            @foreach (\App\Models\ExplanationCase::STATUSES as $key => $label)<option value="{{ $key }}">{{ $label }}</option>@endforeach
        </select>
    </div>

    <div class="overflow-x-auto rounded-2xl border border-line bg-surface shadow-sm">
        <table class="w-full text-left text-sm">
            <thead class="bg-surface-muted text-xs text-ink-muted"><tr><th class="px-4 py-2">Réf.</th><th class="px-4 py-2">Employé</th><th class="px-4 py-2">Objet</th><th class="px-4 py-2">Échéance</th><th class="px-4 py-2">Statut</th><th class="px-4 py-2"></th></tr></thead>
            <tbody class="divide-y divide-line">
                @forelse ($this->cases as $c)
                    <tr wire:key="ex-{{ $c->id }}">
                        <td class="px-4 py-3 font-mono text-xs">{{ $c->reference }}</td>
                        <td class="px-4 py-3">{{ $c->employee?->full_name }}<span class="block text-xs text-ink-muted">{{ $c->employee?->matricule }}</span></td>
                        <td class="px-4 py-3">{{ $c->subject }}</td>
                        <td class="px-4 py-3">{{ $c->response_deadline->format('d/m/Y') }} @if ($c->isOverdue())<span class="ml-1 rounded-full bg-rose-100 px-2 py-0.5 text-xs text-rose-800">dépassée</span>@endif</td>
                        <td class="px-4 py-3"><span class="rounded-full px-2 py-0.5 text-xs {{ $styles[$c->status] ?? '' }}">{{ $c->statusLabel() }}</span>@if ($c->decisionLabel())<span class="block text-xs text-ink-muted">{{ $c->decisionLabel() }}</span>@endif</td>
                        <td class="px-4 py-3 text-right"><a href="{{ route('explanations.show', $c->id) }}" wire:navigate class="rounded border border-line px-2 py-1 text-xs hover:bg-surface-muted">Ouvrir</a></td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="px-4 py-10 text-center text-ink-muted">Aucune demande d’explication.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{ $this->cases->links() }}
</div>
