<div class="space-y-6">
    <div><h2 class="text-2xl font-bold">Journal d’audit de la plateforme</h2><p class="text-sm text-ink-muted">Qui a fait quoi, sur quelle entreprise, quand et depuis quelle adresse. Ce journal est en lecture seule : rien ne peut y être modifié ni supprimé.</p></div>

    <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
        <input type="search" wire:model.live.debounce.300ms="search" class="input" placeholder="Rechercher dans les actions…">
        <select wire:model.live="user" class="input"><option value="">Toute l’équipe</option>@foreach ($staff as $s)<option value="{{ $s->id }}">{{ $s->name }}</option>@endforeach</select>
        <input type="date" wire:model.live="from" class="input" title="Du">
        <input type="date" wire:model.live="to" class="input" title="Au">
    </div>

    <div class="overflow-x-auto rounded-2xl border border-line bg-surface shadow-sm">
        <table class="w-full text-left text-sm">
            <thead class="bg-surface-muted text-xs text-ink-muted"><tr><th class="px-4 py-2">Date</th><th class="px-4 py-2">Qui</th><th class="px-4 py-2">Action</th><th class="px-4 py-2">Adresse IP</th></tr></thead>
            <tbody class="divide-y divide-line">
                @forelse ($this->logs as $l)
                    <tr wire:key="lg-{{ $l->id }}">
                        <td class="whitespace-nowrap px-4 py-3 text-xs">{{ $l->created_at->format('d/m/Y H:i') }}</td>
                        <td class="px-4 py-3">{{ $l->user?->name ?? '—' }}</td>
                        <td class="px-4 py-3">{{ $l->summary }}@if ($l->company_id && auth()->user()->platformCan('companies.view'))<a href="{{ route('platform.companies.show', $l->company_id) }}" wire:navigate class="ml-2 text-xs text-indigo-600 hover:underline">Voir l’entreprise</a>@endif</td>
                        <td class="px-4 py-3 font-mono text-xs text-ink-muted">{{ $l->ip }}</td>
                    </tr>
                @empty
                    <tr><td colspan="4" class="px-4 py-10 text-center text-ink-muted">Aucune action enregistrée.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{ $this->logs->links() }}
</div>
