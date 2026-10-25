<x-layouts.public :company="$company->name" :title="$offer->title.' — '.$company->name">
    <div class="space-y-6">
        <a href="{{ route('careers.company', $slug) }}" class="text-sm text-indigo-600 underline dark:text-indigo-300">← Toutes les offres</a>

        <div>
            <h1 class="text-2xl font-bold">{{ $offer->title }}</h1>
            <p class="mt-1 text-sm text-ink-muted">
                {{ config('recruitment.contract_types.'.$offer->contract_type, $offer->contract_type) }}
                @if ($department) · {{ $department }}@endif
                @if ($offer->vacancies > 1) · {{ $offer->vacancies }} postes @endif
                @if ($offer->closes_at) · candidatures jusqu’au {{ $offer->closes_at->format('d/m/Y') }}@endif
            </p>
        </div>

        <section class="space-y-4 rounded-2xl border border-line bg-surface p-5 shadow-sm">
            @if ($offer->description)
                <div>
                    <h2 class="mb-1 text-sm font-semibold">Le poste</h2>
                    <p class="whitespace-pre-line text-sm">{{ $offer->description }}</p>
                </div>
            @endif
            @if ($offer->requirements)
                <div>
                    <h2 class="mb-1 text-sm font-semibold">Profil recherché</h2>
                    <p class="whitespace-pre-line text-sm">{{ $offer->requirements }}</p>
                </div>
            @endif
            @if ($offer->min_education_level || $offer->min_experience_years > 0)
                <p class="text-sm text-ink-muted">
                    @if ($offer->min_education_level) Niveau d’études minimum : {{ \App\Support\CandidateProfile::educationLabel($offer->min_education_level) }}.@endif
                    @if ($offer->min_experience_years > 0) Expérience minimale : {{ rtrim(rtrim(number_format($offer->min_experience_years, 1, ',', ''), '0'), ',') }} an(s).@endif
                </p>
            @endif
        </section>

        @if ($open)
            <div>
                <h2 class="mb-3 text-lg font-semibold">Postuler à cette offre</h2>
                <livewire:careers.apply-form :offer-id="$offer->id" />
            </div>
        @else
            <p class="rounded-xl bg-amber-50 px-4 py-3 text-sm text-amber-900 dark:bg-amber-500/10 dark:text-amber-200">Cette offre n’accepte plus de candidatures.</p>
        @endif
    </div>
</x-layouts.public>
