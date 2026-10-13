<x-layouts.guest title="Changement de mot de passe">
    <h2 class="text-center text-xl font-bold text-ink mb-1">Nouveau mot de passe requis</h2>
    <p class="text-center text-xs text-ink-muted mb-6">Veuillez personnaliser votre mot de passe temporaire</p>

    <form class="space-y-4" action="{{ route('password.update') }}" method="POST">
        @csrf
        @method('PUT')

        <div>
            <label for="current_password" class="block text-xs font-semibold text-ink mb-1">Mot de passe actuel</label>
            <input id="current_password" name="current_password" type="password" required
                   class="w-full rounded-xl border border-line bg-surface px-3 py-2 text-sm text-ink focus:border-brand-500 focus:outline-none focus:ring-1 focus:ring-brand-500 @error('current_password') border-rose-500 @enderror">
            @error('current_password')
                <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <label for="password" class="block text-xs font-semibold text-ink mb-1">Nouveau mot de passe</label>
            <input id="password" name="password" type="password" required
                   class="w-full rounded-xl border border-line bg-surface px-3 py-2 text-sm text-ink focus:border-brand-500 focus:outline-none focus:ring-1 focus:ring-brand-500 @error('password') border-rose-500 @enderror">
            @error('password')
                <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <label for="password_confirmation" class="block text-xs font-semibold text-ink mb-1">Confirmer le nouveau mot de passe</label>
            <input id="password_confirmation" name="password_confirmation" type="password" required
                   class="w-full rounded-xl border border-line bg-surface px-3 py-2 text-sm text-ink focus:border-brand-500 focus:outline-none focus:ring-1 focus:ring-brand-500">
        </div>

        <button type="submit"
                class="w-full rounded-xl bg-brand-600 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-brand-700 transition">
            Mettre à jour
        </button>
    </form>
</x-layouts.guest>
