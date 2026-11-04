@props(['labels' => [], 'series' => [], 'height' => 240, 'unit' => ''])
@php
    $labels = array_values((array) $labels);
    $series = collect((array) $series)->map(fn ($s) => ['name' => $s['name'] ?? '', 'color' => $s['color'] ?? '#6366f1', 'values' => array_values((array) ($s['values'] ?? []))])->values()->all();
    $W = 640; $H = (int) $height; $L = 52; $R = 8; $T = 12; $B = 28;
    $n = max(1, count($labels)); $m = max(1, count($series));
    $rawMax = max(1, (float) (collect($series)->flatMap(fn ($s) => $s['values'])->max() ?? 1));
    $pow = 10 ** max(0, strlen((string) (int) $rawMax) - 1);
    $max = ceil($rawMax / $pow) * $pow;
    $slot = ($W - $L - $R) / $n;
    $bw = min(38, $slot * 0.8 / $m);
    $base = $H - $B;
    $ph = $H - $T - $B;
    $abbr = fn ($v) => $v >= 1000000 ? rtrim(rtrim(number_format($v / 1000000, 1, ',', ''), '0'), ',').' M' : ($v >= 1000 ? round($v / 1000).' k' : (string) round($v));
    $step = max(1, (int) ceil($n / 12));
@endphp
<div x-data="{ on: false }" x-init="nexthrReady(() => setTimeout(() => on = true, 150))" class="w-full">
    @if (! count($labels) || ! count($series))
        <p class="py-10 text-center text-sm text-ink-muted">Aucune donnée pour ces critères.</p>
    @else
        <svg viewBox="0 0 {{ $W }} {{ $H }}" class="h-auto w-full" role="img" aria-label="Graphique en barres">
            @for ($g = 0; $g <= 4; $g++)
                @php($y = $T + $ph * $g / 4)
                <line x1="{{ $L }}" y1="{{ $y }}" x2="{{ $W - $R }}" y2="{{ $y }}" stroke="currentColor" stroke-opacity=".10" />
                <text x="{{ $L - 8 }}" y="{{ $y + 3 }}" text-anchor="end" font-size="10" fill="currentColor" fill-opacity=".55">{{ $abbr($max * (1 - $g / 4)) }}</text>
            @endfor
            @foreach ($labels as $i => $label)
                @php($cx = $L + $i * $slot + $slot / 2)
                @if ($i % $step === 0)<text x="{{ round($cx, 1) }}" y="{{ $H - 9 }}" text-anchor="middle" font-size="10" fill="currentColor" fill-opacity=".6">{{ \Illuminate\Support\Str::limit($label, 14, '…') }}</text>@endif
                @foreach ($series as $k => $s)
                    @php($v = (float) ($s['values'][$i] ?? 0))
                    @php($h = $v / $max * $ph)
                    @if ($v > 0)
                        <rect x="{{ round($cx - ($m * $bw) / 2 + $k * $bw, 1) }}" y="{{ round($base - $h, 1) }}" width="{{ round($bw - 2, 1) }}" height="{{ round($h, 1) }}" rx="3" fill="{{ $s['color'] }}"
                              style="transform-box: fill-box; transform-origin: bottom; transition: transform .8s ease {{ ($i * 0.04) + ($k * 0.08) }}s" :style="on ? 'transform: scaleY(1)' : 'transform: scaleY(0)'"><title>{{ $s['name'] }} · {{ $label }} : {{ number_format($v, 0, ',', ' ') }} {{ $unit }}</title></rect>
                    @endif
                @endforeach
            @endforeach
        </svg>
        @if (count($series) > 1 || ($series[0]['name'] ?? '') !== '')
            <div class="mt-2 flex flex-wrap gap-4 text-xs text-ink-muted">@foreach ($series as $s)<span class="inline-flex items-center gap-1.5"><span class="h-2.5 w-2.5 rounded-full" style="background: {{ $s['color'] }}"></span>{{ $s['name'] }}</span>@endforeach</div>
        @endif
    @endif
</div>
