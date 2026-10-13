<x-layouts.app title="Tableau de bord">

    @php
        $user = auth()->user();
        $firstName = $user->employee?->first_name ?? $user->name;
    @endphp

    <x-ui.page-header title="Bonjour {{ $firstName }}" subtitle="Voici l'aperçu global de votre organisation aujourd'hui." />

    {{-- Bannière de bienvenue conditionnelle --}}
    @if($welcomeMessage !== null)
        <div class="rounded-2xl bg-brand-950 p-6 text-white shadow-lg relative overflow-hidden border border-brand-900">
            <div class="relative z-10">
                <h3 class="text-xl font-bold mb-2">Bienvenue sur Next HR !</h3>
                <p class="text-brand-200 mb-6 max-w-2xl text-sm">
                    {{ $welcomeMessage }}
                </p>
                <form method="POST" action="{{ route('welcome.dismiss') }}">
                    @csrf
                    <button type="submit" class="bg-white text-brand-950 hover:bg-brand-50 font-semibold px-4 py-2 rounded-xl text-sm shadow transition">
                        J'ai compris, masquer ce message
                    </button>
                </form>
            </div>
        </div>
    @endif

    {{-- Cartes de statistiques --}}
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
        <x-ui.stat-card label="Effectif total" value="—" icon="users" hint="Employés actifs" />
        <x-ui.stat-card label="Présents aujourd'hui" value="—" icon="check-circle" hint="Sur site ou télétravail" />
        <x-ui.stat-card label="En congé" value="—" icon="sun" hint="Absences autorisées" />
        <x-ui.stat-card label="En mission" value="—" icon="paper-airplane" hint="Ordres de mission" />
    </div>

    {{-- Cartes de squelette pour la BI --}}
    <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
        <x-ui.card>
            <h3 class="text-base font-semibold text-ink mb-4">Effectif par département</h3>
            <div class="h-48 flex items-center justify-center border border-dashed border-line rounded-xl text-ink-muted text-sm">
                Données à venir (Étape BI)
            </div>
        </x-ui.card>

        <x-ui.card>
            <h3 class="text-base font-semibold text-ink mb-4">Prochains événements</h3>
            <div class="h-48 flex items-center justify-center border border-dashed border-line rounded-xl text-ink-muted text-sm">
                Aucun événement à afficher
            </div>
        </x-ui.card>
    </div>

</x-layouts.app>
