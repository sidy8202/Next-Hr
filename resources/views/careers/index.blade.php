<x-layouts.public :company="$company->name" :title="'Carrières — '.$company->name">
    <div class="space-y-6">
        <div>
            <h1 class="text-2xl font-bold">Rejoignez {{ $company->name }}</h1>
            <p class="mt-1 text-sm text-ink-muted">Nos offres d’emploi ouvertes. Cliquez sur une offre pour la consulter et postuler en ligne.</p>
        </div>

        <div class="space-y-3">
            @forelse ($offers as $offer)
                <a href="{{ route('careers.offer', ['slug' => $slug, 'reference' => $offer->reference]) }}" class="block rounded-2xl border border-line bg-surface p-5 shadow-sm hover:border-indigo-400">
                    <div class="flex flex-wrap items-start justify-between gap-2">
                        <div>
                            <p class="text-lg font-semibold">{{ $offer->title }}</p>
                            <p class="text-sm text-ink-muted">
                                {{ config('recruitment.contract_types.'.$offer->contract_type, $offer->contract_type) }}
                                @if ($offer->department_id && isset($departments[$offer->department_id])) · {{ $departments[$offer->department_id] }}@endif
                                @if ($offer->vacancies > 1) · {{ $offer->vacancies }} postes @endif
                            </p>
                        </div>
                        @if ($offer->closes_at)
                            <span class="rounded-full bg-surface-muted px-3 py-1 text-xs">Jusqu’au {{ $offer->closes_at->format('d/m/Y') }}</span>
                        @endif
                    </div>
                    @if ($offer->description)
                        <p class="mt-2 line-clamp-2 text-sm text-ink-muted">{{ \Illuminate\Support\Str::limit(strip_tags($offer->description), 220) }}</p>
                    @endif
                </a>
            @empty
                <p class="rounded-2xl border border-dashed border-line px-4 py-10 text-center text-sm text-ink-muted">Aucune offre ouverte pour le moment. Revenez bientôt.</p>
            @endforelse
        </div>
    </div>
</x-layouts.public>
