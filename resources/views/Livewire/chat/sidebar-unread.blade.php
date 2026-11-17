<div wire:poll.15s>
    <a href="{{ route('chat.index') }}" title="Messagerie — {{ $count }} non lu(s)"
       @class([
           'relative flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-medium transition hover:bg-white/10 hover:text-white',
           'bg-brand-600 text-white shadow-sm' => request()->routeIs('chat.*'),
           'text-brand-200' => ! request()->routeIs('chat.*'),
       ])>
        <x-heroicon-o-chat-bubble-left-right class="size-5 shrink-0" />
        <span x-show="!collapsed" x-cloak class="flex-1 truncate">Messagerie</span>
        @if ($count > 0)
            <span x-show="!collapsed" x-cloak class="ml-auto flex min-w-5 items-center justify-center rounded-full bg-rose-500 px-1.5 py-0.5 text-[11px] font-bold text-white">{{ $count > 99 ? '99+' : $count }}</span>
            <span x-show="collapsed" x-cloak class="absolute right-1 top-0 flex min-w-4 items-center justify-center rounded-full bg-rose-500 px-1 text-[10px] font-bold text-white">{{ $count > 99 ? '99+' : $count }}</span>
        @endif
    </a>
</div>
