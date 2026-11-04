@props(['cols' => [], 'rows' => [], 'format' => 'int', 'color' => '99,102,241', 'corner' => ''])
@php
    $flat = collect($rows)->flatten()->filter(fn ($v) => $v !== null);
    $max = max(1, (float) ($flat->max() ?? 1));
    $fmt = fn ($v) => match ($format) { 'pct' => $v.' %', 'money' => $v >= 1000000 ? rtrim(rtrim(number_format($v / 1000000, 1, ',', ''), '0'), ',').' M' : ($v >= 1000 ? round($v / 1000).' k' : (string) $v), default => (string) $v };
    $scale = $format === 'pct' ? 100 : $max;
@endphp
{{-- Grille colorée : l'intensité de chaque case est proportionnelle à sa valeur. --}}
<div x-data="{ on: false }" x-init="nexthrReady(() => setTimeout(() => on = true, 200))" class="overflow-x-auto">
    <table class="w-full border-separate border-spacing-1 text-center text-xs">
        <thead><tr><th class="px-2 py-1 text-left font-medium text-ink-muted">{{ $corner }}</th>@foreach ($cols as $c)<th class="px-1 py-1 font-medium text-ink-muted">{{ $c }}</th>@endforeach</tr></thead>
        <tbody>
            @forelse ($rows as $label => $values)
                <tr>
                    <td class="whitespace-nowrap px-2 py-1.5 text-left font-medium">{{ $label }}</td>
                    @foreach ($values as $k => $v)
                        <td class="rounded-md px-1 py-1.5 font-semibold transition duration-700"
                            style="transition-delay: {{ ($loop->parent->index * 40) + ($k * 25) }}ms; @if ($v !== null && $v > 0) background: rgba({{ $color }}, {{ round(0.08 + $v / $scale * 0.82, 2) }}); color: {{ $v / $scale > 0.55 ? '#fff' : 'inherit' }}; @endif"
                            :class="on ? 'scale-100 opacity-100' : 'scale-75 opacity-0'">{{ $v === null ? '' : ($v === 0 ? '·' : $fmt($v)) }}</td>
                    @endforeach
                </tr>
            @empty
                <tr><td colspan="{{ count($cols) + 1 }}" class="py-8 text-ink-muted">Aucune donnée pour ces critères.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>
