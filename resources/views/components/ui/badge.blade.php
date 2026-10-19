@props(['tone' => 'gray'])

@php
    $tones = [
        'gray' => 'bg-gray-100 text-gray-700 dark:bg-white/10 dark:text-gray-300',
        'green' => 'bg-emerald-100 text-emerald-800 dark:bg-emerald-500/15 dark:text-emerald-300',
        'red' => 'bg-rose-100 text-rose-800 dark:bg-rose-500/15 dark:text-rose-300',
        'amber' => 'bg-amber-100 text-amber-800 dark:bg-amber-500/15 dark:text-amber-300',
        'indigo' => 'bg-brand-100 text-brand-800 dark:bg-brand-500/20 dark:text-brand-200',
        'sky' => 'bg-sky-100 text-sky-800 dark:bg-sky-500/15 dark:text-sky-300',
    ];
@endphp

<span {{ $attributes->merge(['class' => 'inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium '.($tones[$tone] ?? $tones['gray'])]) }}>{{ $slot }}</span>
