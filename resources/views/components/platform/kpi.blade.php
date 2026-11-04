@props(['label', 'value' => 0, 'prefix' => '', 'suffix' => '', 'delta' => null, 'hint' => null, 'spark' => [], 'color' => '#6366f1', 'delay' => 0])

<x-ui.reveal :delay="$delay" class="rounded-2xl border border-line bg-surface p-4 shadow-sm">
    <p class="text-xs font-medium text-ink-muted">{{ $label }}</p>
    <div class="mt-1 flex items-end justify-between gap-2">
        <p class="text-2xl font-bold tracking-tight"><x-ui.count :value="$value" :prefix="$prefix" :suffix="$suffix" /></p>
        <x-charts.spark :values="$spark" :color="$color" />
    </div>
    <p class="mt-1 text-xs text-ink-muted">
        @if ($delta !== null)<span class="font-semibold {{ $delta >= 0 ? 'text-emerald-600' : 'text-rose-600' }}">{{ $delta >= 0 ? '▲' : '▼' }} {{ abs($delta) }} %</span> @endif
        {{ $hint }}
    </p>
</x-ui.reveal>
