<!DOCTYPE html>
<html lang="fr" class="h-full bg-gray-50">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title ?? 'Next HR' }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="h-full flex flex-col font-sans text-gray-900 antialiased">
    <nav class="bg-white border-b border-gray-200">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-16">
                <div class="flex items-center space-x-8">
                    <span class="text-xl font-bold text-indigo-600">Next HR</span>

                @auth
                    <div class="flex items-center space-x-4">
                        <span class="text-sm text-gray-600 font-medium">
                            {{ auth()->user()->name }}
                            @if(auth()->user()->company)
                                <span class="text-xs text-gray-400">({{ auth()->user()->company->name }})</span>
                            @endif
                        </span>
                        <form method="POST" action="{{ route('logout') }}" class="inline">
                            @csrf
                            <button type="submit" class="text-sm font-medium text-red-600 hover:text-red-800 transition">
                                Déconnexion
                            </button>
                        </form>
                    </div>
                @endauth
            </div>
        </div>
    </nav>
</body>
</html>
