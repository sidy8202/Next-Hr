@assets
<script>window.nexthrReady = window.nexthrReady || function (cb) { requestAnimationFrame(function () { cb(); }); };</script>
@endassets

<div class="space-y-6">
    <x-ui.reveal class="flex flex-wrap items-end justify-between gap-3">
        <div>
            <h2 class="text-2xl font-bold">{{ now()->hour < 12 ? 'Bonjour' : (now()->hour < 18 ? 'Bon après-midi' : 'Bonsoir') }} {{ explode(' ', auth()->user()->name)[0] }}</h2>
            <p class="text-sm text-ink-muted">{{ now()->translatedFormat('l d F Y') }}</p>
        </div>
        <div class="flex flex-wrap gap-2">
            @if (\Illuminate\Support\Facades\Route::has('leaves.create') && auth()->user()->canDo('create', 'leave_requests'))<a href="{{ route('leaves.create') }}" wire:navigate class="rounded-xl bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-500">Demander un congé</a>@endif
            @if (\Illuminate\Support\Facades\Route::has('missions.create') && auth()->user()->canDo('create', 'mission_orders'))<a href="{{ route('missions.create') }}" wire:navigate class="rounded-xl border border-line bg-surface px-4 py-2 text-sm hover:bg-surface-muted">Nouvelle mission</a>@endif
        </div>
    </x-ui.reveal>

    @if (count($views) > 1)
        <nav class="flex flex-wrap gap-1 rounded-2xl border border-line bg-surface p-1 shadow-sm">
            @foreach ($views as $k => $label)
                <button type="button" wire:click="$set('view', '{{ $k }}')" class="rounded-xl px-4 py-2 text-sm font-medium transition {{ $view === $k ? 'bg-indigo-600 text-white shadow' : 'text-ink-muted hover:bg-surface-muted hover:text-ink' }}">{{ $label }}</button>
            @endforeach
        </nav>
    @endif

    @if (! count($views))
        <p class="rounded-2xl border border-line bg-surface p-8 text-center text-sm text-ink-muted">Aucun tableau de bord n’est disponible pour votre profil. Contactez l’administrateur de votre entreprise.</p>
    @elseif ($d === null)
        <p class="rounded-2xl border border-line bg-surface p-8 text-center text-sm text-ink-muted">Votre compte n’est pas rattaché à une fiche employé ou à un département : cette vue n’a pas encore de données.</p>
    @else
        @include('livewire.dashboard.dash-'.$view)
    @endif
</div>
