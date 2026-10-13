@props([
    'label',
    'value',
    'hint' => null,
    'icon' => null,
])

<div class="rounded-2xl border border-line bg-surface p-5 shadow-sm flex items-start justify-between">
    <div class="space-y-1">
        <p class="text-xs font-semibold uppercase tracking-wider text-ink-muted">{{ $label }}</p>
        <p class="text-2xl font-bold text-ink">{{ $value }}</p>
        @if ($hint)
            <p class="text-xs text-ink-muted">{{ $hint }}</p>
        @endif
    </div>

    @if ($icon)
        <div class="flex size-10 shrink-0 items-center justify-center rounded-xl bg-brand-50 text-brand-600 dark:bg-brand-950 dark:text-brand-300">
            <x-dynamic-component :component="'heroicon-o-' . $icon" class="size-5" />
        </div>
    @endif
</div>
