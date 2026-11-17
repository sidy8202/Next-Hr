<div class="mx-auto w-full max-w-[1800px] space-y-4">
    <p class="text-sm text-ink-muted">{{ $intro }}</p>

    @if ($flash)
        <div class="rounded-lg border border-line bg-surface-muted px-4 py-3 text-sm text-ink">{{ $flash }}</div>
    @endif

    {{-- Mode d'affichage (pôle configuration uniquement) --}}
    @if ($mode !== null)
        <div class="flex flex-wrap items-center justify-between gap-3 rounded-xl border border-line bg-surface px-5 py-4">
            <div class="min-w-0">
                <p class="text-sm font-semibold text-ink">Affichage {{ $mode === 'simple' ? 'simple' : 'avancé' }}</p>
                <p class="text-xs text-ink-muted">
                    @if ($mode === 'simple')
                        Les options rares sont masquées : calcul en pourcentage d'une autre rubrique, quantité × taux, barème d'ancienneté, plancher et plafond, net garanti. Ce que vous utilisez déjà reste visible et continue de fonctionner.
                    @else
                        Toutes les options sont visibles. Passez en affichage simple pour un formulaire plus léger.
                    @endif
                </p>
            </div>
            @if ($canSwitchMode)
                <button type="button" wire:click="setMode('{{ $mode === 'simple' ? 'advanced' : 'simple' }}')" wire:loading.attr="disabled" class="rounded-lg border border-line px-4 py-2 text-sm text-ink">
                    @if ($mode === 'simple') Afficher toutes les options @else Passer à l'affichage simple @endif
                </button>
            @endif
        </div>
    @endif

    @foreach ($sections as $s)
        <section id="{{ $s['key'] }}" wire:key="sec-{{ $s['key'] }}"
                 x-data="{
                     key: @js($s['key']),
                     keys: @js($keys),
                     open: false,
                     init() {
                         const h = location.hash.slice(1);
                         this.open = this.keys.includes(h) ? h === this.key : @js($s['first']);
                         if (this.open) {
                             this.$wire.load(this.key);
                             if (h === this.key) { this.$nextTick(() => this.$el.scrollIntoView({ block: 'start' })); }
                         }
                     },
                     toggle() {
                         this.open = ! this.open;
                         if (this.open) { this.$wire.load(this.key); }
                     },
                     fromHash() {
                         if (location.hash.slice(1) === this.key) {
                             this.open = true;
                             this.$wire.load(this.key);
                             this.$nextTick(() => this.$el.scrollIntoView({ block: 'start' }));
                         }
                     }
                 }"
                 @hashchange.window="fromHash()"
                 class="scroll-mt-20 rounded-xl border border-line bg-surface">
            <button type="button" @click="toggle()" class="flex w-full items-center justify-between gap-3 px-5 py-4 text-left">
                <span class="min-w-0">
                    <span class="block text-sm font-semibold text-ink">{{ $s['title'] }}</span>
                    <span class="block text-xs text-ink-muted">{{ $s['hint'] }}</span>
                </span>
                <svg class="size-5 shrink-0 text-ink-muted transition" :class="open && 'rotate-180'" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M6 9l6 6 6-6"/></svg>
            </button>

            <div x-show="open" x-cloak class="border-t border-line p-4 [&_h1]:hidden">
                @if ($s['is_loaded'])
                    <livewire:dynamic-component :is="$s['alias']" :key="'hub-' . $s['key']" />
                @else
                    <p class="text-sm text-ink-muted">Chargement…</p>
                @endif
            </div>
        </section>
    @endforeach
</div>
