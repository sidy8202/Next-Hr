<div class="space-y-6">
    <div class="flex flex-wrap items-end justify-between gap-3">
        <div>
            <h2 class="text-2xl font-bold">Notifications</h2>
            <p class="text-sm text-ink-muted">Vos messages et alertes.</p>
        </div>
        <div class="flex items-center gap-2">
            <select wire:model.live="filter" class="input w-44" aria-label="Filtrer les notifications">
                <option value="all">Toutes</option><option value="unread">Non lues</option>
            </select>
            <x-ui.button variant="secondary" wire:click="markAllAsRead" icon="check">Tout marquer comme lu</x-ui.button>
        </div>
    </div>

    <div class="rounded-2xl border border-line bg-surface shadow-sm">
        <ul class="divide-y divide-line">
            @forelse ($notifications as $n)
                <li wire:key="notif-{{ $n->id }}">
                    <button type="button" wire:click="open('{{ $n->id }}')" class="flex w-full gap-4 px-5 py-4 text-left hover:bg-surface-muted">
                        <span class="flex size-10 shrink-0 items-center justify-center rounded-full bg-brand-50 text-brand-600 dark:bg-brand-500/15 dark:text-brand-300">
                            <x-dynamic-component :component="'heroicon-o-'.($n->data['icon'] ?? 'bell')" class="size-5" />
                        </span>
                        <span class="min-w-0 flex-1">
                            <span @class(['block text-sm', 'font-semibold' => ! $n->read_at])>{{ $n->data['title'] ?? 'Notification' }}</span>
                            <span class="block text-sm text-ink-muted">{{ $n->data['message'] ?? '' }}</span>
                            <span class="mt-1 block text-xs text-ink-muted">{{ $n->created_at->diffForHumans() }}</span>
                        </span>
                        @unless ($n->read_at)<x-ui.badge tone="indigo">Nouveau</x-ui.badge>@endunless
                    </button>
                </li>
            @empty
                <li><x-ui.empty-state title="Aucune notification" text="Les alertes et messages (anniversaires, demandes à valider…) apparaîtront ici." icon="bell" /></li>
            @endforelse
        </ul>
        <div class="border-t border-line px-4 py-3">{{ $notifications->links('pagination.livewire') }}</div>
    </div>
</div>
