<div x-show="mobileOpen" x-cloak x-transition.opacity @click="mobileOpen = false"
     class="fixed inset-0 z-30 bg-black/50 lg:hidden"></div>

<aside
    class="fixed inset-y-0 left-0 z-40 flex w-64 flex-col bg-brand-950 text-brand-100 transition-all duration-200 lg:static"
    :class="[mobileOpen ? 'translate-x-0' : '-translate-x-full lg:translate-x-0', collapsed ? 'lg:w-[76px]' : 'lg:w-64']">

    <div class="flex h-16 shrink-0 items-center gap-3 px-5">
        <span class="flex size-9 shrink-0 items-center justify-center rounded-xl bg-brand-500 font-bold text-white">N</span>
        <div class="min-w-0" :class="collapsed && 'lg:hidden'">
            <p class="truncate text-sm font-semibold text-white">Next HR</p>
            <p class="truncate text-xs text-brand-300">{{ auth()->user()->company?->name }}</p>
        </div>
    </div>

    <nav class="flex-1 space-y-6 overflow-y-auto px-3 py-4">
        @foreach ($groups() as $group)
            <div>
                @if ($group['label'])
                    <p class="px-3 pb-2 text-[11px] font-semibold uppercase tracking-wider text-brand-400"
                       :class="collapsed && 'lg:hidden'">{{ $group['label'] }}</p>
                @endif

                <ul class="space-y-1">
                    @foreach ($group['items'] as $item)
                        @php($active = request()->routeIs(Str::before($item['route'], '.') . '*'))
                        <li>
                            <a href="{{ route($item['route']) }}" title="{{ $item['label'] }}"
                               @class([
                                   'flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-medium transition',
                                   'bg-brand-600 text-white shadow-sm' => $active,
                                   'text-brand-200 hover:bg-white/10 hover:text-white' => ! $active,
                               ])>
                                <x-dynamic-component :component="'heroicon-o-' . $item['icon']" class="size-5 shrink-0" />
                                <span class="truncate" :class="collapsed && 'lg:hidden'">{{ $item['label'] }}</span>
                            </a>
                        </li>
                    @endforeach
                </ul>
            </div>
        @endforeach
    </nav>
</aside>
