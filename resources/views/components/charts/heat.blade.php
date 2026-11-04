@props(['cols' => [], 'rows' => [], 'color' => '99,102,241'])

{{-- Carte de chaleur : chaque case = part (en %) des entreprises de la formule qui utilisent le module. --}}
<div x-data="{ on: false }" x-init="nexthrReady(() => setTimeout(() => on = true, 250))" class="overflow-x-auto">
    <table class="w-full border-separate border-spacing-1 text-center text-xs">
        <thead><tr><th class="px-2 py-1 text-left font-medium text-ink-muted">Module</th>@foreach ($cols as $c)<th class="px-2 py-1 font-medium text-ink-muted">{{ $c }}</th>@endforeach</tr></thead>
        <tbody>
            @forelse ($rows as $label => $values)
                <tr>
                    <td class="whitespace-nowrap px-2 py-1.5 text-left font-medium">{{ $label }}</td>
                    @foreach ($values as $k => $v)
                        <td class="rounded-lg px-2 py-1.5 font-semibold transition duration-700"
                            style="transition-delay: {{ ($loop->parent->index * 60) + ($k * 40) }}ms; @if ($v !== null) background: rgba({{ $color }}, {{ round(0.06 + $v / 100 * 0.84, 2) }}); color: {{ $v > 55 ? '#fff' : 'inherit' }}; @endif"
                            :class="on ? 'scale-100 opacity-100' : 'scale-75 opacity-0'">{{ $v === null ? '—' : $v.' %' }}</td>
                    @endforeach
                </tr>
            @empty
                <tr><td colspan="{{ count($cols) + 1 }}" class="py-8 text-ink-muted">Aucune donnée d’usage disponible.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>
