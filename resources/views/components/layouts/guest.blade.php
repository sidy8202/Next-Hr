@props(['title' => 'Next HR'])
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title }} · Next HR</title>
    <script>
        if (localStorage.theme === 'dark' || (!('theme' in localStorage) && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
            document.documentElement.classList.add('dark');
        }
    </script>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-surface-muted text-ink antialiased">
    <div class="min-h-screen flex flex-col justify-center items-center p-4 sm:p-6">
        <div class="mb-6 flex items-center gap-3">
            <span class="flex size-11 items-center justify-center rounded-2xl bg-brand-600 font-bold text-white text-xl shadow-md">N</span>
            <span class="text-2xl font-extrabold tracking-tight text-ink">Next HR</span>
        </div>

        <div class="w-full max-w-md rounded-2xl border border-line bg-surface p-8 shadow-sm">
            {{ $slot }}
        </div>
    </div>
</body>
</html>
