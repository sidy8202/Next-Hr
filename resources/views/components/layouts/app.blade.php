@props(['title' => 'Next HR'])
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title }} · Next HR</title>
    <script>
        if (localStorage.theme === 'dark' || (!('theme' in localStorage) && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
            document.documentElement.classList.add('dark');
        }
    </script>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body data-user-id="{{ auth()->id() }}" class="bg-surface-muted text-ink antialiased">
    <x-ui.splash />
    <div x-data="{ collapsed: $persist(false), mobileOpen: false }" class="flex h-screen overflow-hidden">
        <x-sidebar />

        <div class="flex min-w-0 flex-1 flex-col">
            <x-navbar :title="$title" />

            <main class="flex-1 overflow-y-auto p-4 sm:p-6 lg:p-8">
                <div class="mx-auto w-full max-w-[1800px] space-y-6">
                    {{ $slot }}
                </div>
            </main>
        </div>
    </div>
    <x-ui.toast :flash="session('notify')" />
    <livewire:chat.chat-call-dock />
    @livewireScripts
</body>
</html>
