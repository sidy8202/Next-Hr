@props(['title' => null, 'company' => null])
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="index, follow">
    <title>{{ $title ?: ($company ?: config('app.name')) }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body class="min-h-screen bg-surface-muted text-ink antialiased">
    <header class="border-b border-line bg-surface">
        <div class="mx-auto flex max-w-4xl items-center justify-between px-4 py-4">
            <p class="font-semibold">{{ $company ?: config('app.name') }}</p>
            <p class="text-sm text-ink-muted">Carrières</p>
        </div>
    </header>

    <main class="mx-auto max-w-4xl px-4 py-8">
        {{ $slot }}
    </main>

    @livewireScripts
</body>
</html>
