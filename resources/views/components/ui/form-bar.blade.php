{{-- Barre d'actions toujours visible en bas de page. À placer DANS le <form>. --}}
@props(['cancel', 'target' => 'save', 'label' => 'Enregistrer'])

<div class="sticky bottom-0 z-10 -mx-4 mt-8 flex items-center justify-end gap-2 border-t border-line bg-surface-muted/90 px-4 py-3 backdrop-blur sm:-mx-6 sm:px-6 lg:-mx-8 lg:px-8">
    {{ $slot }}
    <x-ui.button variant="secondary" :href="$cancel" wire:navigate>Annuler</x-ui.button>
    <x-ui.button type="submit" wire:loading.attr="disabled" wire:target="{{ $target }}">
        <span wire:loading.remove wire:target="{{ $target }}">{{ $label }}</span>
        <span wire:loading wire:target="{{ $target }}">Enregistrement…</span>
    </x-ui.button>
</div>
