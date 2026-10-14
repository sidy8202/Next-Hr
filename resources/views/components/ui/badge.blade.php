@props(['tone' => 'gray'])

@php
    $tones = [
        'green'  => 'bg-emerald-50 text-emerald-800 border-emerald-200 dark:bg-emerald-950/50 dark:text-emerald-300 dark:border-emerald-800',
        'red'    => 'bg-rose-50 text-rose-800 border-rose-200 dark:bg-rose-950/50 dark:text-rose-300 dark:border-rose-800',
        'amber'  => 'bg-amber-50 text-amber-800 border-amber-200 dark:bg-amber-950/50 dark:text-amber-300 dark:border-amber-800',
        'gray'   => 'bg-surface-muted text-ink-muted border-line',
        'indigo' => 'bg-brand-50 text-brand-800 border-brand-200 dark:bg-brand-950/50 dark:text-brand-300 dark:border-brand-800',
    ];
@endphp

<span {{ $attributes->merge(['class' => 'inline-flex items-center rounded-lg border px-2.5 py-0.5 text-xs font-medium ' . ($tones[$tone] ?? $tones['gray'])]) }}>
    {{ $slot }}
</span>
