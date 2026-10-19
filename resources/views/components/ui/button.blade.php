@props(['variant' => 'primary', 'type' => 'button', 'href' => null, 'icon' => null, 'size' => 'md'])

@php
    $base = 'inline-flex items-center justify-center gap-2 rounded-xl font-medium transition focus:outline-none focus-visible:ring-2 focus-visible:ring-brand-500 focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50';
    $sizes = ['sm' => 'px-3 py-1.5 text-sm', 'md' => 'px-4 py-2 text-sm'];
    $variants = [
        'primary' => 'bg-brand-600 text-white shadow-sm hover:bg-brand-700',
        'secondary' => 'border border-line bg-surface text-ink hover:bg-surface-muted',
        'danger' => 'bg-rose-600 text-white shadow-sm hover:bg-rose-700',
        'ghost' => 'text-ink-muted hover:bg-surface-muted hover:text-ink',
    ];
    $classes = $base.' '.($sizes[$size] ?? $sizes['md']).' '.($variants[$variant] ?? $variants['primary']);
@endphp

@if ($href)
    <a href="{{ $href }}" {{ $attributes->merge(['class' => $classes]) }}>
        @if ($icon)<x-dynamic-component :component="'heroicon-o-'.$icon" class="size-4" />@endif
        {{ $slot }}
    </a>
@else
    <button type="{{ $type }}" {{ $attributes->merge(['class' => $classes]) }}>
        @if ($icon)<x-dynamic-component :component="'heroicon-o-'.$icon" class="size-4" />@endif
        {{ $slot }}
    </button>
@endif
