@props(['view' => null, 'download', 'name' => 'Document', 'ext' => ''])

@php
    $ext = strtolower((string) $ext);
    $isImage = in_array($ext, ['jpg', 'jpeg', 'png'], true);
    $previewable = $view && ($isImage || $ext === 'pdf');
@endphp

{{-- Boutons « Visualiser » et « Télécharger ». PDF et images s'ouvrent dans une fenêtre ; les autres formats se téléchargent. --}}
<span x-data="{ open: false }" {{ $attributes->class('inline-flex items-center gap-1 align-middle') }}>
    @if ($previewable)
        <button type="button" @click="open = true" class="rounded border border-line p-1 hover:bg-surface-muted" title="Visualiser" aria-label="Visualiser {{ $name }}">
            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7S2 12 2 12z"/><circle cx="12" cy="12" r="3"/></svg>
        </button>
    @endif

    <a href="{{ $download }}" class="rounded border border-line p-1 hover:bg-surface-muted" title="{{ $previewable ? 'Télécharger' : 'Télécharger (aperçu indisponible pour ce format)' }}" aria-label="Télécharger {{ $name }}">
        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 3v12"/><path d="M7 11l5 5 5-5"/><path d="M5 21h14"/></svg>
    </a>

    @if ($previewable)
        <template x-teleport="body">
            <div x-show="open" x-transition.opacity @keydown.escape.window="open = false" @click.self="open = false"
                 class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 p-4" role="dialog" aria-modal="true" aria-label="{{ $name }}">
                <div class="flex h-[90vh] w-full max-w-5xl flex-col overflow-hidden rounded-2xl bg-surface text-ink shadow-xl">
                    <div class="flex flex-wrap items-center justify-between gap-2 border-b border-line px-4 py-2">
                        <p class="truncate text-sm font-semibold">{{ $name }}</p>
                        <div class="flex items-center gap-2 text-sm">
                            <a href="{{ $download }}" class="rounded-lg border border-line px-3 py-1 hover:bg-surface-muted">Télécharger</a>
                            <a href="{{ $view }}" target="_blank" rel="noopener" class="rounded-lg border border-line px-3 py-1 hover:bg-surface-muted">Ouvrir dans un onglet</a>
                            <button type="button" @click="open = false" class="rounded-lg bg-indigo-600 px-3 py-1 font-semibold text-white hover:bg-indigo-500">Fermer</button>
                        </div>
                    </div>
                    <div class="min-h-0 flex-1 bg-surface-muted">
                        {{-- Le fichier n'est chargé qu'à l'ouverture --}}
                        <template x-if="open">
                            @if ($isImage)
                                <div class="flex h-full items-center justify-center overflow-auto p-2"><img src="{{ $view }}" alt="{{ $name }}" class="max-h-full max-w-full object-contain"></div>
                            @else
                                <iframe src="{{ $view }}" title="{{ $name }}" class="h-full w-full border-0"></iframe>
                            @endif
                        </template>
                    </div>
                </div>
            </div>
        </template>
    @endif
</span>
