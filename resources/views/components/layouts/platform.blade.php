@props(['title' => 'Espace plateforme'])
@php
    $u = auth()->user();
    $nav = [
        ['Tableau de bord', 'platform', 'dashboard.view', 'M3 12l9-9 9 9M5 10v10h5v-6h4v6h5V10', 'platform'],
        ['Entreprises', 'platform.companies', 'companies.view', 'M3 21h18M5 21V5l7-2v18M19 21V9l-7-2M9 9h1M9 13h1M9 17h1', 'platform.companies*'],
        ['Formules et matrice', 'platform.plans', 'plans.manage', 'M12 3l9 5-9 5-9-5 9-5zM3 13l9 5 9-5', 'platform.plans'],
        ['Facturation et e-mails', 'platform.billing', 'plans.manage', 'M3 8l9 6 9-6M5 6h14a2 2 0 012 2v10a2 2 0 01-2 2H5a2 2 0 01-2-2V8a2 2 0 012-2z', 'platform.billing'],
        ['Équipe', 'platform.staff', 'staff.manage', 'M17 20v-2a4 4 0 00-4-4H7a4 4 0 00-4 4v2M10 10a4 4 0 100-8 4 4 0 000 8M21 20v-2a4 4 0 00-3-3.9M16 2.1a4 4 0 010 7.8', 'platform.staff'],
        ['Journal d’audit', 'platform.audit', 'audit.view', 'M12 3l8 3v6c0 5-3.5 8-8 9-4.5-1-8-4-8-9V6l8-3z', 'platform.audit'],
        ['Rapports et analyses', 'platform.reports', 'reports.view', 'M3 3v18h18M7 15l4-4 3 3 5-6', 'platform.reports'],
        ['Règles de paie', 'platform.payroll-rules', 'plans.manage', 'M12 3v18m-6-6 6 6 6-6M4 7h16', 'platform.payroll-rules'],
        ['Paquets par secteur', 'platform.payroll-packages', 'plans.manage', 'M12 3v18m-6-6 6 6 6-6M4 7h16', 'platform.payroll-packages'],

    ];
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title }} · Next HR Plateforme</title>
    <script>
        if (localStorage.theme === 'dark' || (!('theme' in localStorage) && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
            document.documentElement.classList.add('dark');
        }
    </script>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body class="bg-surface-muted text-ink antialiased">
    <x-ui.splash subtitle="Chargement de votre espace plateforme" />

    <div x-data="{ mobileOpen: false }" class="flex h-screen overflow-hidden">
        {{-- Barre latérale --}}
        <aside class="fixed inset-y-0 left-0 z-40 flex w-64 -translate-x-full flex-col border-r border-line bg-surface transition-transform lg:static lg:translate-x-0" :class="{ 'translate-x-0': mobileOpen }">
            <div class="flex h-16 items-center gap-3 border-b border-line px-5">
                <span class="flex h-9 w-9 items-center justify-center rounded-xl bg-gradient-to-br from-indigo-600 to-violet-600 text-sm font-bold text-white shadow">N</span>
                <div class="leading-tight"><p class="text-sm font-bold">Next HR</p><p class="text-xs text-ink-muted">Espace plateforme</p></div>
            </div>
            <nav class="flex-1 space-y-1 overflow-y-auto p-3">
                @foreach ($nav as [$label, $route, $perm, $icon, $pattern])
                    @if ($u->platformCan($perm) && Route::has($route))
                        <a href="{{ route($route) }}" wire:navigate @click="mobileOpen = false"
                           class="flex items-center gap-3 rounded-xl px-3 py-2 text-sm font-medium transition {{ request()->routeIs($pattern) ? 'bg-indigo-600 text-white shadow' : 'text-ink-muted hover:bg-surface-muted hover:text-ink' }}">
                            <svg class="h-5 w-5 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="{{ $icon }}"/></svg>
                            {{ $label }}
                        </a>
                    @endif
                @endforeach
            </nav>
            <div class="border-t border-line p-3">
                <div class="mb-2 rounded-xl bg-surface-muted px-3 py-2">
                    <p class="truncate text-sm font-semibold">{{ $u->name }}</p>
                    <p class="truncate text-xs text-ink-muted">{{ config('platform.roles.'.$u->platform_role.'.label', 'Équipe plateforme') }}</p>
                </div>
                <form method="POST" action="{{ route('logout') }}">@csrf
                    <button type="submit" class="w-full rounded-xl border border-line px-3 py-2 text-sm text-ink-muted hover:bg-surface-muted">Se déconnecter</button>
                </form>
            </div>
        </aside>
        <div x-show="mobileOpen" x-cloak @click="mobileOpen = false" class="fixed inset-0 z-30 bg-black/40 lg:hidden"></div>

        <div class="flex min-w-0 flex-1 flex-col">
            <header class="flex h-16 items-center justify-between gap-3 border-b border-line bg-surface px-4 sm:px-6">
                <div class="flex items-center gap-3">
                    <button type="button" class="rounded-lg border border-line p-2 lg:hidden" @click="mobileOpen = true" aria-label="Menu"><svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 6h16M4 12h16M4 18h16"/></svg></button>
                    <h1 class="text-lg font-semibold">{{ $title }}</h1>
                </div>
                <button type="button" class="rounded-lg border border-line px-3 py-1.5 text-sm text-ink-muted hover:bg-surface-muted"
                        onclick="const d = document.documentElement.classList.toggle('dark'); localStorage.theme = d ? 'dark' : 'light'">Thème</button>
            </header>

            <main class="flex-1 overflow-y-auto p-4 sm:p-6 lg:p-8">
                <div class="mx-auto w-full max-w-[1800px] space-y-6">{{ $slot }}</div>
            </main>
        </div>
    </div>

    <x-ui.toast :flash="session('notify')" />
    @livewireScripts
</body>
</html>
