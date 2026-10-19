@props(['title', 'description' => null])

<section {{ $attributes->merge(['class' => 'rounded-2xl border border-line bg-surface p-5 shadow-sm']) }}>
    <div class="mb-4">
        <h3 class="text-sm font-semibold text-brand-700 dark:text-brand-300">{{ $title }}</h3>
        @if ($description)<p class="mt-0.5 text-xs text-ink-muted">{{ $description }}</p>@endif
    </div>
    <div class="space-y-4">{{ $slot }}</div>
</section>
