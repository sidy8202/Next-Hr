<div class="space-y-6">
    @if ($grid['summary']['too_small'])
        <p class="rounded-2xl border border-line bg-surface p-8 text-center text-sm text-ink-muted">Moins de {{ $grid['minGroup'] }} personnes sont comparées avec ces critères : les chiffres ne sont pas affichés, pour ne pas révéler un salaire individuel.</p>
    @else
        <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
            <x-platform.kpi label="Employés comparés" :value="$grid['summary']['compared']" hint="salaire en vigueur et catégorie du poste" color="#6366f1" :delay="0" />
            <x-platform.kpi label="Sous le minimum" :value="$grid['summary']['below']" hint="salaire de base sous le plus petit échelon" color="#f43f5e" :delay="60" />
            <x-platform.kpi label="Au-dessus du maximum" :value="$grid['summary']['above']" hint="salaire de base au-dessus du plus grand échelon" color="#f59e0b" :delay="120" />
            <x-platform.kpi label="Écart à l’échelon" :value="$grid['summary']['step_mismatch']" hint="dans la fourchette, mais autre que l’échelon" color="#0ea5e9" :delay="180" />
        </div>
    @endif

    @if (count($grid['sentences']))
        <x-ui.reveal :delay="120" class="print-keep rounded-2xl border border-indigo-200 bg-indigo-50/60 p-5 dark:border-indigo-500/30 dark:bg-indigo-500/10">
            <h3 class="mb-2 text-sm font-semibold">Ce que disent les chiffres</h3>
            <ul class="space-y-2 text-sm">
                @foreach ($grid['sentences'] as $line)
                    <li wire:key="gs-{{ $loop->index }}" class="flex gap-2"><span class="text-indigo-600">▸</span><span>{{ $line['text'] }}<span class="block text-xs text-ink-muted">Règle : {{ $line['rule'] }}</span></span></li>
                @endforeach
            </ul>
            @if ($grid['salariesUrl'])
                <a href="{{ $grid['salariesUrl'] }}" class="no-print mt-3 inline-block text-xs font-medium text-indigo-600 hover:underline dark:text-indigo-300">Voir les salaires dans « Employés et salaires » →</a>
            @endif
        </x-ui.reveal>
    @endif

    @unless ($grid['summary']['too_small'])
        <div class="grid gap-4 xl:grid-cols-2">
            <section class="print-keep rounded-2xl border border-line bg-surface p-5 shadow-sm">
                <h3 class="mb-3 text-sm font-semibold">Par catégorie salariale</h3>
                <div class="overflow-x-auto">
                    <table class="w-full min-w-[420px] text-sm">
                        <thead class="text-xs text-ink-muted"><tr class="border-b border-line"><th class="py-2 pr-3 text-left font-medium">Catégorie</th><th class="px-3 py-2 text-right font-medium">Comparés</th><th class="px-3 py-2 text-right font-medium">Sous le min.</th><th class="px-3 py-2 text-right font-medium">Au-dessus du max.</th><th class="px-3 py-2 text-right font-medium">Écart échelon</th></tr></thead>
                        <tbody class="divide-y divide-line">
                            @forelse ($grid['summary']['categories']['items'] as $r)
                                <tr wire:key="gc-{{ $loop->index }}"><td class="py-2 pr-3 font-medium">{{ $r['name'] }}</td><td class="px-3 py-2 text-right">{{ $r['count'] }}</td><td class="px-3 py-2 text-right {{ $r['below'] > 0 ? 'font-semibold text-rose-600' : '' }}">{{ $r['below'] }}</td><td class="px-3 py-2 text-right">{{ $r['above'] }}</td><td class="px-3 py-2 text-right">{{ $r['step_mismatch'] }}</td></tr>
                            @empty
                                <tr><td colspan="5" class="py-8 text-center text-ink-muted">Aucune catégorie n’atteint l’effectif minimal d’affichage.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                @if ($grid['summary']['categories']['hidden_people'] > 0)
                    <p class="mt-3 text-xs text-ink-muted">{{ $grid['summary']['categories']['hidden_people'] }} {{ $grid['summary']['categories']['hidden_people'] > 1 ? 'personnes' : 'personne' }} de petites catégories {{ $grid['summary']['categories']['hidden_people'] > 1 ? 'ne sont pas affichées' : 'n’est pas affichée' }} (moins de {{ $grid['minGroup'] }} personnes).</p>
                @endif
            </section>

            <section class="print-keep rounded-2xl border border-line bg-surface p-5 shadow-sm">
                <h3 class="mb-3 text-sm font-semibold">Par service</h3>
                <div class="overflow-x-auto">
                    <table class="w-full min-w-[420px] text-sm">
                        <thead class="text-xs text-ink-muted"><tr class="border-b border-line"><th class="py-2 pr-3 text-left font-medium">Service</th><th class="px-3 py-2 text-right font-medium">Comparés</th><th class="px-3 py-2 text-right font-medium">Sous le min.</th><th class="px-3 py-2 text-right font-medium">Au-dessus du max.</th><th class="px-3 py-2 text-right font-medium">Écart échelon</th></tr></thead>
                        <tbody class="divide-y divide-line">
                            @forelse ($grid['summary']['departments']['items'] as $r)
                                <tr wire:key="gd-{{ $loop->index }}"><td class="py-2 pr-3 font-medium">{{ $r['name'] }}</td><td class="px-3 py-2 text-right">{{ $r['count'] }}</td><td class="px-3 py-2 text-right {{ $r['below'] > 0 ? 'font-semibold text-rose-600' : '' }}">{{ $r['below'] }}</td><td class="px-3 py-2 text-right">{{ $r['above'] }}</td><td class="px-3 py-2 text-right">{{ $r['step_mismatch'] }}</td></tr>
                            @empty
                                <tr><td colspan="5" class="py-8 text-center text-ink-muted">Aucun service n’atteint l’effectif minimal d’affichage.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                @if ($grid['summary']['departments']['hidden_people'] > 0)
                    <p class="mt-3 text-xs text-ink-muted">{{ $grid['summary']['departments']['hidden_people'] }} {{ $grid['summary']['departments']['hidden_people'] > 1 ? 'personnes' : 'personne' }} de petits services {{ $grid['summary']['departments']['hidden_people'] > 1 ? 'ne sont pas affichées' : 'n’est pas affichée' }} (moins de {{ $grid['minGroup'] }} personnes).</p>
                @endif
            </section>
        </div>
    @endunless

    @include('livewire.reports.report-thresholds')
</div>
