@props(['items' => [], 'center' => null, 'centerLabel' => null])
@php
    $total = max(1, (float) collect($items)->sum('value'));
    $acc = 0;
@endphp
<div x-data="{ on: false }" x-init="nexthrReady(() => setTimeout(() => on = true, 250))" class="flex flex-wrap items-center justify-center gap-6">
    <div class="relative h-40 w-40">
        <svg viewBox="0 0 42 42" class="h-40 w-40 -rotate-90" role="img" aria-label="Graphique circulaire">
            <circle cx="21" cy="21" r="15.9155" fill="none" stroke="currentColor" stroke-opacity=".08" stroke-width="5" />
            @foreach ($items as $it)
                @continue($it['value'] <= 0)
                @php($pct = $it['value'] / $total * 100)
                <circle cx="21" cy="21" r="15.9155" fill="none" stroke="{{ $it['color'] }}" stroke-width="5" stroke-dashoffset="{{ round(-$acc, 3) }}"
                        style="transition: stroke-dasharray 1.1s ease {{ $loop->index * 0.15 }}s" :style="{ strokeDasharray: on ? '{{ round($pct, 3) }} {{ round(100 - $pct, 3) }}' : '0 100' }">
                    <title>{{ $it['label'] }} : {{ $it['value'] }} ({{ round($pct) }} %)</title>
                </circle>
                @php($acc += $pct)
            @endforeach
        </svg>
        @if ($center !== null)
            <div class="absolute inset-0 flex flex-col items-center justify-center"><span class="text-2xl font-bold"><x-ui.count :value="$center" /></span>@if ($centerLabel)<span class="text-xs text-ink-muted">{{ $centerLabel }}</span>@endif</div>
        @endif
    </div>
    <ul class="space-y-2 text-sm">
        @foreach ($items as $it)
            <li class="flex items-center gap-2"><span class="h-3 w-3 rounded-full" style="background: {{ $it['color'] }}"></span><span>{{ $it['label'] }}</span><span class="ml-1 font-semibold">{{ $it['value'] }}</span></li>
        @endforeach
    </ul>
</div>
