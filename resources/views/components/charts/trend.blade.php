@props(['labels' => [], 'series' => [], 'height' => 240, 'unit' => ''])
@php
    $labels = array_values((array) $labels);
    $series = collect((array) $series)->map(fn ($s) => ['name' => $s['name'] ?? '', 'color' => $s['color'] ?? '#6366f1', 'values' => array_values((array) ($s['values'] ?? [])), 'fill' => ! empty($s['fill'])])->values()->all();
    $uid = \Illuminate\Support\Str::random(5);
    $W = 640; $H = (int) $height; $L = 52; $R = 12; $T = 12; $B = 26;
    $n = max(1, count($labels));
    $rawMax = max(1, (float) (collect($series)->flatMap(fn ($s) => $s['values'])->max() ?? 1));
    $pow = 10 ** max(0, strlen((string) (int) $rawMax) - 1);
    $max = ceil($rawMax / $pow) * $pow;
    $px = fn ($i) => $L + ($n > 1 ? $i * (($W - $L - $R) / ($n - 1)) : ($W - $L - $R) / 2);
    $py = fn ($v) => $T + ($H - $T - $B) * (1 - $v / $max);
    $abbr = fn ($v) => $v >= 1000000 ? rtrim(rtrim(number_format($v / 1000000, 1, ',', ''), '0'), ',').' M' : ($v >= 1000 ? round($v / 1000).' k' : (string) round($v));
    $step = max(1, (int) ceil($n / 12));
@endphp
<div x-data="{ on: false }" x-init="nexthrReady(() => setTimeout(() => on = true, 150))" class="w-full">
    @if (! count($labels) || ! count($series))
        <p class="py-10 text-center text-sm text-ink-muted">Aucune donnée pour ces critères.</p>
    @else
        <svg viewBox="0 0 {{ $W }} {{ $H }}" class="h-auto w-full" role="img" aria-label="Graphique en courbes">
            <defs>
                @foreach ($series as $k => $s)
                    <linearGradient id="gt-{{ $uid }}-{{ $k }}" x1="0" y1="0" x2="0" y2="1"><stop offset="0" stop-color="{{ $s['color'] }}" stop-opacity=".28" /><stop offset="1" stop-color="{{ $s['color'] }}" stop-opacity="0" /></linearGradient>
                @endforeach
            </defs>
            @for ($g = 0; $g <= 4; $g++)
                @php
                    $y = $T + ($H - $T - $B) * $g / 4;
                @endphp
                <line x1="{{ $L }}" y1="{{ $y }}" x2="{{ $W - $R }}" y2="{{ $y }}" stroke="currentColor" stroke-opacity=".10" />
                <text x="{{ $L - 8 }}" y="{{ $y + 3 }}" text-anchor="end" font-size="10" fill="currentColor" fill-opacity=".55">{{ $abbr($max * (1 - $g / 4)) }}</text>
            @endfor
            @foreach ($labels as $i => $label)
                @if ($i % $step === 0)<text x="{{ $px($i) }}" y="{{ $H - 8 }}" text-anchor="middle" font-size="10" fill="currentColor" fill-opacity=".55">{{ $label }}</text>@endif
            @endforeach
            @foreach ($series as $k => $s)
                @php
                    $pts = collect($s['values'])->map(fn ($v, $i) => round($px($i), 1).','.round($py($v), 1));
                    $line = 'M'.$pts->implode(' L');
                    $area = $pts->count() > 1 ? $line.' L'.round($px(count($s['values']) - 1), 1).','.($H - $B).' L'.round($px(0), 1).','.($H - $B).' Z' : null;
                @endphp
                @if ($s['fill'] && $area)<path d="{{ $area }}" fill="url(#gt-{{ $uid }}-{{ $k }})" style="transition: opacity 1.1s ease .3s" :style="on ? 'opacity: 1' : 'opacity: 0'" />@endif
                @if ($pts->count() > 1)
                    <path d="{{ $line }}" fill="none" stroke="{{ $s['color'] }}" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" pathLength="1" style="stroke-dasharray: 1; transition: stroke-dashoffset 1.4s ease" :style="on ? 'stroke-dashoffset: 0' : 'stroke-dashoffset: 1'" />
                @endif
                @foreach ($s['values'] as $i => $v)
                    <circle cx="{{ round($px($i), 1) }}" cy="{{ round($py($v), 1) }}" r="3.2" fill="{{ $s['color'] }}" style="transition: opacity .6s ease 1.1s" :style="on ? 'opacity: 1' : 'opacity: 0'"><title>{{ $s['name'] }} · {{ $labels[$i] ?? '' }} : {{ number_format($v, 0, ',', ' ') }} {{ $unit }}</title></circle>
                @endforeach
            @endforeach
        </svg>
        <div class="mt-2 flex flex-wrap gap-4 text-xs text-ink-muted">@foreach ($series as $s)<span class="inline-flex items-center gap-1.5"><span class="h-2.5 w-2.5 rounded-full" style="background: {{ $s['color'] }}"></span>{{ $s['name'] }}</span>@endforeach</div>
    @endif
</div>
