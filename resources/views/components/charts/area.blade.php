@props(['labels' => [], 'series' => [], 'height' => 230])
@php
    $uid = \Illuminate\Support\Str::random(5);
    $W = 640;
    $H = (int) $height;
    $L = 50;
    $R = 12;
    $T = 12;
    $B = 26;
    $n = max(1, count($labels));

    $rawMax = 1;
    foreach ($series as $s) {
        if (!empty($s['values'])) {
            $rawMax = max($rawMax, max($s['values']));
        }
    }
    $pow = 10 ** max(0, strlen((string) (int) $rawMax) - 1);
    $max = ceil($rawMax / $pow) * $pow;

    $px = function($i) use ($L, $W, $R, $n) {
        return $L + ($n > 1 ? $i * (($W - $L - $R) / ($n - 1)) : 0);
    };

    $py = function($v) use ($T, $H, $B, $max) {
        return $T + ($H - $T - $B) * (1 - $v / $max);
    };

    $abbr = function($v) {
        if ($v >= 1000000) {
            return rtrim(rtrim(number_format($v / 1000000, 1, ',', ''), '0'), ',').' M';
        }
        if ($v >= 1000) {
            return round($v / 1000).' k';
        }
        return (string) round($v);
    };
@endphp

<div x-data="{ on: false }" x-init="nexthrReady(() => setTimeout(() => on = true, 200))" class="w-full">
    <svg viewBox="0 0 {{ $W }} {{ $H }}" class="h-auto w-full" role="img" aria-label="Graphique en courbes">
        <defs>
            @foreach ($series as $k => $s)
                <linearGradient id="ga-{{ $uid }}-{{ $k }}" x1="0" y1="0" x2="0" y2="1">
                    <stop offset="0" stop-color="{{ $s['color'] }}" stop-opacity=".30" />
                    <stop offset="1" stop-color="{{ $s['color'] }}" stop-opacity="0" />
                </linearGradient>
            @endforeach
        </defs>

        @for ($g = 0; $g <= 4; $g++)
            @php
                $y = $T + ($H - $T - $B) * $g / 4;
                $valLabel = $abbr($max * (1 - $g / 4));
            @endphp
            <line x1="{{ $L }}" y1="{{ $y }}" x2="{{ $W - $R }}" y2="{{ $y }}" stroke="currentColor" stroke-opacity=".10" />
            <text x="{{ $L - 8 }}" y="{{ $y + 3 }}" text-anchor="end" font-size="10" fill="currentColor" fill-opacity=".55">{{ $valLabel }}</text>
        @endfor

        @foreach ($labels as $i => $label)
            <text x="{{ $px($i) }}" y="{{ $H - 8 }}" text-anchor="middle" font-size="10" fill="currentColor" fill-opacity=".55">{{ $label }}</text>
        @endforeach

        @foreach ($series as $k => $s)
            @php
                $pts = collect($s['values'])->map(fn ($v, $i) => round($px($i), 1).','.round($py($v), 1));
                $line = 'M'.$pts->implode(' L');
                $lastIdx = count($s['values']) - 1;
                $area = $line.' L'.round($px(max(0, $lastIdx)), 1).','.($H - $B).' L'.round($px(0), 1).','.($H - $B).' Z';
                $hasFill = !empty($s['fill']);
            @endphp

            @if ($hasFill)
                <path d="{{ $area }}" fill="url(#ga-{{ $uid }}-{{ $k }})" style="transition: opacity 1.2s ease .3s" :style="on ? 'opacity: 1' : 'opacity: 0'" />
            @endif

            <path d="{{ $line }}" fill="none" stroke="{{ $s['color'] }}" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" pathLength="1"
                  style="stroke-dasharray: 1; transition: stroke-dashoffset 1.5s ease" :style="on ? 'stroke-dashoffset: 0' : 'stroke-dashoffset: 1'" />

            @foreach ($s['values'] as $i => $v)
                @php
                    $cx = round($px($i), 1);
                    $cy = round($py($v), 1);
                    $titleText = $s['name'].' · '.($labels[$i] ?? '').' : '.number_format($v, 0, ',', ' ').' FCFA';
                @endphp
                <circle cx="{{ $cx }}" cy="{{ $cy }}" r="3.2" fill="{{ $s['color'] }}" style="transition: opacity .6s ease 1.2s" :style="on ? 'opacity: 1' : 'opacity: 0'">
                    <title>{{ $titleText }}</title>
                </circle>
            @endforeach
        @endforeach
    </svg>

    <div class="mt-2 flex flex-wrap gap-4 text-xs text-ink-muted">
        @foreach ($series as $s)
            <span class="inline-flex items-center gap-1.5">
                <span class="h-2.5 w-2.5 rounded-full" style="background: {{ $s['color'] }}"></span>
                {{ $s['name'] }}
            </span>
        @endforeach
    </div>
</div>
