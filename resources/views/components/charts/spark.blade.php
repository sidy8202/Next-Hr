@props(['values' => [], 'color' => '#6366f1', 'w' => 84, 'h' => 30])
@php
    $values = array_values($values);
    $n = count($values);
    $max = max(1, max($values ?: [1]));
    $min = min($values ?: [0]);
    $span = max(1, $max - $min);
    $pts = collect($values)->map(fn ($v, $i) => round(2 + $i * (($w - 4) / max(1, $n - 1)), 1).','.round($h - 3 - (($v - $min) / $span) * ($h - 6), 1))->implode(' L');
@endphp
@if ($n > 1)
    <svg viewBox="0 0 {{ $w }} {{ $h }}" width="{{ $w }}" height="{{ $h }}" class="shrink-0" aria-hidden="true"
         x-data="{ on: false }" x-init="nexthrReady(() => setTimeout(() => on = true, 250))">
        <path d="M{{ $pts }}" fill="none" stroke="{{ $color }}" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" pathLength="1"
              style="stroke-dasharray: 1; transition: stroke-dashoffset 1.2s ease" :style="on ? 'stroke-dashoffset: 0' : 'stroke-dashoffset: 1'" />
    </svg>
@endif
