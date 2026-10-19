<div x-data="{ open: false }" class="relative" wire:poll.60s>
    <button type="button" @click="open = ! open" aria-label="Notifications ({{ $this->unreadCount }} non lues)"
            class="relative rounded-xl p-2 text-ink-muted hover:bg-surface-muted">
        <x-heroicon-o-bell class="size-6" />
        @if ($this->unreadCount > 0)
            <span class="absolute -right-0.5 -top-0.5 flex min-w-5 items-center justify-center rounded-full bg-rose-500 px-1 text-[11px] font-semibold leading-5 text-white ring-2 ring-surface">
                {{ $this->unreadCount > 9 ? '9+' : $this->unreadCount }}
            </span>
        @endif
    </button>

    <div x-show="open" x-cloak x-transition @click.outside="open = false"
         class="absolute right-0 mt-2 w-96 max-w-[90vw] overflow-hidden rounded-2xl border border-line bg-surface shadow-xl">
        <div class="flex items-center justify-between border-b border-line px-4 py-3">
            <p class="text-sm font-semibold">Notifications</p>
            @if ($this->unreadCount > 0)
                <button type="button" wire:click="markAllAsRead" class="text-xs font-medium text-brand-600 hover:underline dark:text-brand-300">Tout marquer comme lu</button>
            @endif
        </div>

        <ul class="max-h-96 divide-y divide-line overflow-y-auto">
            @forelse ($this->latest as $n)
                <li wire:key="bell-{{ $n->id }}">
                    <button type="button" wire:click="open('{{ $n->id }}')" class="flex w-full gap-3 px-4 py-3 text-left hover:bg-surface-muted">
                        <span class="mt-0.5 flex size-8 shrink-0 items-center justify-center rounded-full bg-brand-50 text-brand-600 dark:bg-brand-500/15 dark:text-brand-300">
                            <x-dynamic-component :component="'heroicon-o-'.($n->data['icon'] ?? 'bell')" class="size-4" />
                        </span>
                        <span class="min-w-0 flex-1">
                            <span @class(['block text-sm', 'font-semibold' => ! $n->read_at])>{{ $n->data['title'] ?? 'Notification' }}</span>
                            <span class="block text-sm text-ink-muted">{{ $n->data['message'] ?? '' }}</span>
                            <span class="mt-0.5 block text-xs text-ink-muted">{{ $n->created_at->diffForHumans() }}</span>
                        </span>
                        @unless ($n->read_at)<span class="mt-2 size-2 shrink-0 rounded-full bg-brand-500" aria-label="Non lue"></span>@endunless
                    </button>
                </li>
            @empty
                <li class="px-4 py-8 text-center text-sm text-ink-muted">Aucune notification pour le moment.</li>
            @endforelse
        </ul>

        <a href="{{ route('notifications.index') }}" wire:navigate class="block border-t border-line px-4 py-2.5 text-center text-sm font-medium text-brand-600 hover:bg-surface-muted dark:text-brand-300">Voir toutes les notifications</a>
    </div>
</div>
