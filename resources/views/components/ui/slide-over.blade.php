@props(['title' => '', 'width' => 'max-w-xl'])

<div x-data="{ show: false }" x-init="$nextTick(() => show = true)"
     @keydown.escape.window="$wire.closeForm()" class="fixed inset-0 z-50">

    <div x-show="show" x-transition.opacity wire:click="closeForm" class="absolute inset-0 bg-black/40"></div>

    <section x-show="show"
             x-transition:enter="transition duration-200" x-transition:enter-start="translate-x-full" x-transition:enter-end="translate-x-0"
             class="absolute inset-y-0 right-0 flex w-full {{ $width }} flex-col bg-surface shadow-2xl"
             role="dialog" aria-modal="true" aria-label="{{ $title }}">

        <header class="flex items-center justify-between border-b border-line px-6 py-4">
            <h2 class="text-lg font-semibold text-ink">{{ $title }}</h2>
            <button type="button" wire:click="closeForm" aria-label="Fermer"
                    class="rounded-lg p-2 text-ink-muted hover:bg-surface-muted">
                <x-heroicon-o-x-mark class="size-5" />
            </button>
        </header>

        <div class="flex-1 space-y-5 overflow-y-auto px-6 py-5">{{ $slot }}</div>

        @isset($footer)
            <footer class="flex items-center justify-end gap-2 border-t border-line px-6 py-4">{{ $footer }}</footer>
        @endisset
    </section>
</div>
