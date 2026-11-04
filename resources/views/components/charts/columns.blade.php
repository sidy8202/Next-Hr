@props(['labels' => [], 'up' => [], 'down' => [], 'upLabel' => 'Nouvelles', 'downLabel' => 'Départs', 'height' => 210])
@php
    $W = 640; $H = (int) $height; $L = 30; $R = 8; $T = 10; $B = 24;
    $n = max(1, count($labels));
    $max = max(1, max($up ?: [0]), max($down ?: [0]));
    $mid = $T + ($H - $T - $B) / 2;
    $half = ($H - $T - $B) / 2 - 4;
    $step = ($W - $L - $R) / $n;
    $bw = $step * 0.55;
@endphp
<div x-data="{ on: false }" x-init="nexthrReady(() => setTimeout(() => on = true, 250))" class="w-full">
    <svg viewBox="0 0 {{ $W }} {{ $H }}" class="h-auto w-full" role="img" aria-label="Graphique en colonnes">
        <line x1="{{ $L }}" y1="{{ $mid }}" x2="{{ $W - $R }}" y2="{{ $mid }}" stroke="currentColor" stroke-opacity=".25" />
        @foreach ($labels as $i => $label)
            @php($x = $L + $i * $step + ($step - $bw) / 2)
            @php($hu = $up[$i] / $max * $half)
            @php($hd = $down[$i] / $max * $half)
            @if ($up[$i] > 0)
                <rect x="{{ round($x, 1) }}" y="{{ round($mid - $hu, 1) }}" width="{{ round($bw, 1) }}" height="{{ round($hu, 1) }}" rx="3" fill="#10b981"
                      style="transform-box: fill-box; transform-origin: bottom; transition: transform .8s ease {{ $i * 0.05 }}s" :style="on ? 'transform: scaleY(1)' : 'transform: scaleY(0)'"><title>{{ $label }} : {{ $up[$i] }} {{ strtolower($upLabel) }}</title></rect>
            @endif
            @if ($down[$i] > 0)
                <rect x="{{ round($x, 1) }}" y="{{ round($mid, 1) }}" width="{{ round($bw, 1) }}" height="{{ round($hd, 1) }}" rx="3" fill="#f43f5e"
                      style="transform-box: fill-box; transform-origin: top; transition: transform .8s ease {{ $i * 0.05 }}s" :style="on ? 'transform: scaleY(1)' : 'transform: scaleY(0)'"><title>{{ $label }} : {{ $down[$i] }} {{ strtolower($downLabel) }}</title></rect>
            @endif
            <text x="{{ round($x + $bw / 2, 1) }}" y="{{ $H - 8 }}" text-anchor="middle" font-size="10" fill="currentColor" fill-opacity=".55">{{ $label }}</text>
        @endforeach
    </svg>
    <div class="mt-2 flex gap-4 text-xs text-ink-muted">
        <span class="inline-flex items-center gap-1.5"><span class="h-2.5 w-2.5 rounded-full bg-emerald-500"></span>{{ $upLabel }}</span>
        <span class="inline-flex items-center gap-1.5"><span class="h-2.5 w-2.5 rounded-full bg-rose-500"></span>{{ $downLabel }}</span>
    </div>
</div>
