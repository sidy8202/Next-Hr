<x-layouts.guest title="Connexion">
    <h2 class="text-center text-xl font-bold text-ink mb-1">Connexion à votre espace</h2>
    <p class="text-center text-xs text-ink-muted mb-6">Saisissez vos identifiants pour continuer</p>

    <form class="space-y-4" action="{{ route('login') }}" method="POST">
        @csrf

        <div>
            <label for="email" class="block text-xs font-semibold text-ink mb-1">Adresse e-mail</label>
            <input id="email" name="email" type="email" required value="{{ old('email') }}"
                   class="w-full rounded-xl border border-line bg-surface px-3 py-2 text-sm text-ink placeholder-ink-muted focus:border-brand-500 focus:outline-none focus:ring-1 focus:ring-brand-500 @error('email') border-rose-500 @enderror">
            @error('email')
                <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <label for="password" class="block text-xs font-semibold text-ink mb-1">Mot de passe</label>
            <input id="password" name="password" type="password" required
                   class="w-full rounded-xl border border-line bg-surface px-3 py-2 text-sm text-ink placeholder-ink-muted focus:border-brand-500 focus:outline-none focus:ring-1 focus:ring-brand-500">
        </div>

        <div class="flex items-center">
            <input id="remember" name="remember" type="checkbox"
                   class="size-4 rounded border-line text-brand-600 focus:ring-brand-500">
            <label for="remember" class="ml-2 block text-xs text-ink-muted">Se souvenir de moi</label>
        </div>

        <button type="submit"
                class="w-full rounded-xl bg-brand-600 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-brand-700 transition">
            Se connecter
        </button>
    </form>
</x-layouts.guest>
