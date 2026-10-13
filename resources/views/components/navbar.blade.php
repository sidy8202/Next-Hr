@props(['title' => ''])

<header class="sticky top-0 z-20 flex h-16 shrink-0 items-center gap-3 border-b border-line bg-surface/80 px-4 backdrop-blur sm:px-6">
    <button type="button" @click="mobileOpen = true" aria-label="Ouvrir le menu"
            class="rounded-lg p-2 text-ink-muted hover:bg-surface-muted lg:hidden">
        <x-heroicon-o-bars-3 class="size-6" />
    </button>
    <button type="button" @click="collapsed = ! collapsed" aria-label="Réduire ou agrandir le menu"
            class="hidden rounded-lg p-2 text-ink-muted hover:bg-surface-muted lg:block">
        <x-heroicon-o-bars-3-bottom-left class="size-6" />
    </button>

    <h1 class="truncate text-base font-semibold">{{ $title }}</h1>
    <div class="flex-1"></div>

    {{-- Recherche globale : sera activée plus tard --}}
    <button type="button" class="hidden items-center gap-2 rounded-xl border border-line bg-surface-muted px-3 py-2 text-sm text-ink-muted sm:flex">
        <x-heroicon-o-magnifying-glass class="size-4" />
        <span>Rechercher…</span>
        <kbd class="rounded border border-line px-1.5 text-xs">Ctrl K</kbd>
    </button>

    {{-- Notifications (branchées à l'étape 2.4) --}}
    <div x-data="{ open: false }" class="relative">
        <button type="button" @click="open = ! open" aria-label="Notifications"
                class="relative rounded-xl p-2 text-ink-muted hover:bg-surface-muted">
            <x-heroicon-o-bell class="size-6" />
            <span class="absolute right-1.5 top-1.5 size-2.5 rounded-full bg-rose-500 ring-2 ring-surface"></span>
        </button>
        <div x-show="open" x-cloak x-transition @click.outside="open = false"
             class="absolute right-0 mt-2 w-80 rounded-2xl border border-line bg-surface p-4 shadow-xl">
            <p class="text-sm font-semibold">Notifications</p>
            <p class="mt-3 text-sm text-ink-muted">Aucune notification pour le moment.</p>
        </div>
    </div>

    {{-- Thème clair / sombre --}}
    <button type="button" x-data
            @click="const d = document.documentElement.classList.toggle('dark'); localStorage.theme = d ? 'dark' : 'light'"
            aria-label="Changer de thème" class="rounded-xl p-2 text-ink-muted hover:bg-surface-muted">
        <x-heroicon-o-moon class="size-6 dark:hidden" />
        <x-heroicon-o-sun class="hidden size-6 dark:block" />
    </button>

    {{-- Menu utilisateur --}}
    <div x-data="{ open: false }" class="relative">
        <button type="button" @click="open = ! open" class="flex items-center gap-2 rounded-xl p-1.5 hover:bg-surface-muted">
            <span class="flex size-8 items-center justify-center rounded-full bg-brand-600 text-xs font-semibold text-white">
                {{ Str::upper(Str::substr(auth()->user()->name, 0, 2)) }}
            </span>
            <span class="hidden text-sm font-medium sm:block">{{ auth()->user()->name }}</span>
        </button>
        <div x-show="open" x-cloak x-transition @click.outside="open = false"
             class="absolute right-0 mt-2 w-60 rounded-2xl border border-line bg-surface p-2 shadow-xl">
            <div class="px-3 py-2">
                <p class="text-sm font-semibold">{{ auth()->user()->name }}</p>
                <p class="truncate text-xs text-ink-muted">{{ auth()->user()->email }}</p>
            </div>
            <form method="POST" action="{{ route('logout') }}" class="border-t border-line pt-2">
                @csrf
                <button type="submit" class="w-full rounded-lg px-3 py-2 text-left text-sm text-rose-600 hover:bg-surface-muted">Déconnexion</button>
            </form>
        </div>
    </div>
</header>
