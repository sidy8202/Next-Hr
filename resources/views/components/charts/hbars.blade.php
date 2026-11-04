@props(['items' => [], 'color' => '#6366f1', 'unit' => ''])
@php($max = max(1, (float) collect($items)->max('value')))
<div x-data="{ on: false }" x-init="nexthrReady(() => setTimeout(() => on = true, 250))">
    @forelse ($items as $it)
        <div class="mb-3" wire:key="hb-{{ $loop->index }}">
            <div class="flex items-baseline justify-between gap-2 text-xs">
                <span class="font-medium">{{ $it['label'] }}</span>
                <span class="text-ink-muted"><strong class="text-ink">{{ number_format($it['value'], 0, ',', ' ') }}</strong> {{ $unit }}@if (! empty($it['sub'])) · {{ $it['sub'] }}@endif</span>
            </div>
            <div class="mt-1 h-2.5 w-full overflow-hidden rounded-full bg-black/5 dark:bg-white/10">
                <div class="h-full rounded-full" style="background: {{ $color }}; transition: width 1s ease {{ $loop->index * 0.08 }}s" :style="{ width: on ? '{{ round($it['value'] / $max * 100, 1) }}%' : '0%' }"></div>
            </div>
        </div>
    @empty
        <p class="py-8 text-center text-sm text-ink-muted">Aucune donnée pour le moment.</p>
    @endforelse
</div>
