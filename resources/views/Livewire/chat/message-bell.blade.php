<div x-data="{ open: false }" class="relative" wire:poll.15s>
    <button type="button" @click="open = ! open" aria-label="Messagerie ({{ $count }} message(s) non lu(s))"
            :aria-expanded="open.toString()"
            class="relative rounded-xl p-2 text-ink-muted transition hover:bg-surface-muted hover:text-brand-600">
        <x-heroicon-o-chat-bubble-left-right class="size-6" />
        @if ($count > 0)
            <span class="absolute -right-0.5 -top-0.5 flex min-w-5 items-center justify-center rounded-full bg-rose-500 px-1 text-[11px] font-semibold leading-5 text-white ring-2 ring-surface">
                {{ $count > 9 ? '9+' : $count }}
            </span>
        @endif
    </button>

    <div x-show="open" x-cloak x-transition @click.outside="open = false"
         class="absolute right-0 z-50 mt-2 w-[24rem] max-w-[90vw] overflow-hidden rounded-2xl border border-line bg-surface shadow-xl">
        <div class="flex items-center justify-between border-b border-line px-4 py-3">
            <div>
                <p class="text-sm font-semibold">Messages non lus</p>
                <p class="text-xs text-ink-muted">{{ $count }} message(s) dans vos conversations</p>
            </div>
            <a href="{{ route('chat.index') }}" wire:navigate @click="open = false" class="text-xs font-semibold text-brand-600 hover:underline dark:text-brand-300">Tout voir</a>
        </div>

        <ul class="max-h-[26rem] divide-y divide-line overflow-y-auto">
            @forelse ($items as $conversation)
                <li wire:key="chat-bell-{{ $conversation->id }}">
                    <a href="{{ route('chat.index', ['conversation' => $conversation->id]) }}" wire:navigate @click="open = false"
                       class="flex gap-3 px-4 py-3 transition hover:bg-surface-muted">
                        <span class="flex size-10 shrink-0 items-center justify-center rounded-xl bg-brand-100 text-sm font-bold text-brand-700 dark:bg-brand-500/15 dark:text-brand-200">
                            {{ mb_strtoupper(mb_substr($conversation->sidebar_title, 0, 1)) }}
                        </span>
                        <span class="min-w-0 flex-1">
                            <span class="flex items-center justify-between gap-2">
                                <span class="truncate text-sm font-semibold">{{ $conversation->sidebar_title }}</span>
                                <span class="rounded-full bg-brand-600 px-2 py-0.5 text-[10px] font-bold text-white">{{ $conversation->unread_total }}</span>
                            </span>
                            <span class="mt-0.5 block truncate text-xs font-medium text-ink-muted">{{ $conversation->preview_sender }}</span>
                            <span class="mt-0.5 block truncate text-xs text-ink-muted">{{ $conversation->preview_text }}</span>
                            <span class="mt-1 block text-[10px] text-ink-muted">{{ $conversation->latestMessage?->created_at?->diffForHumans() }}</span>
                        </span>
                    </a>
                </li>
            @empty
                <li class="px-4 py-8 text-center">
                    <x-heroicon-o-chat-bubble-left-right class="mx-auto size-8 text-ink-muted/60" />
                    <p class="mt-2 text-sm font-medium">Aucun nouveau message</p>
                    <p class="mt-1 text-xs text-ink-muted">Les nouveaux messages apparaîtront ici.</p>
                </li>
            @endforelse
        </ul>
        <a href="{{ route('chat.index') }}" wire:navigate @click="open = false" class="block border-t border-line px-4 py-2.5 text-center text-sm font-medium text-brand-600 hover:bg-surface-muted dark:text-brand-300">Ouvrir la messagerie</a>
    </div>
</div>
