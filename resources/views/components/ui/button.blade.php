@props([
    'variant' => 'primary',
    'type' => 'button',
    'icon' => null,
])

@php
    $baseClass = 'inline-flex items-center justify-center gap-2 rounded-xl text-sm font-semibold transition focus:outline-none focus:ring-2 focus:ring-offset-2 disabled:opacity-50 disabled:cursor-not-allowed px-4 py-2.5';
    $variants = [
        'primary'   => 'bg-brand-600 text-white hover:bg-brand-700 focus:ring-brand-500 shadow-sm',
        'secondary' => 'border border-line bg-surface text-ink hover:bg-surface-muted focus:ring-brand-500',
        'danger'    => 'bg-rose-600 text-white hover:bg-rose-700 focus:ring-rose-500 shadow-sm',
        'ghost'     => 'text-ink-muted hover:bg-surface-muted hover:text-ink',
    ];
    $classes = $baseClass . ' ' . ($variants[$variant] ?? $variants['primary']);
@endphp

<button type="{{ $type }}" {{ $attributes->merge(['class' => $classes]) }}>
    @if ($icon)
        <x-dynamic-component :component="'heroicon-o-' . $icon" class="size-4 shrink-0" />
    @endif
    <span>{{ $slot }}</span>
</button>
