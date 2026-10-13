@props([
    'title',
    'subtitle' => null,
])

<div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
    <div>
        <h1 class="text-2xl font-bold tracking-tight text-ink">{{ $title }}</h1>
        @if ($subtitle)
            <p class="mt-1 text-sm text-ink-muted">{{ $subtitle }}</p>
        @endif
    </div>

    @if (isset($actions))
        <div class="flex items-center gap-3">
            {{ $actions }}
        </div>
    @endif
</div>
