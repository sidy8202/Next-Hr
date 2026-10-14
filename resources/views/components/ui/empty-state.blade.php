@props([
    'title' => 'Aucune donnée disponible',
    'text' => null,
    'icon' => 'folder-open',
])

<div class="flex flex-col items-center justify-center rounded-2xl border border-dashed border-line bg-surface p-8 text-center">
    <div class="flex size-12 items-center justify-center rounded-2xl bg-surface-muted text-ink-muted mb-3">
        <x-dynamic-component :component="'heroicon-o-' . $icon" class="size-6" />
    </div>
    <h3 class="text-base font-semibold text-ink">{{ $title }}</h3>
    @if ($text)
        <p class="mt-1 text-sm text-ink-muted max-w-sm">{{ $text }}</p>
    @endif
    @if (isset($action))
        <div class="mt-4">
            {{ $action }}
        </div>
    @endif
</div>
