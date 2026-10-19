@if ($paginator->hasPages())
    @php($pageName = $paginator->getPageName())
    <nav role="navigation" aria-label="Pagination" class="flex flex-wrap items-center gap-1 text-sm">
        <button type="button" wire:click="previousPage('{{ $pageName }}')" @disabled($paginator->onFirstPage())
                class="rounded-lg border border-line px-3 py-1.5 hover:bg-surface-muted disabled:opacity-40">Précédent</button>

        @foreach ($elements as $element)
            @if (is_string($element))
                <span class="px-1 text-ink-muted">{{ $element }}</span>
            @endif
            @if (is_array($element))
                @foreach ($element as $page => $url)
                    <button type="button" wire:key="page-{{ $pageName }}-{{ $page }}" wire:click="gotoPage({{ $page }}, '{{ $pageName }}')"
                            @if ($page == $paginator->currentPage()) aria-current="page" @endif
                            @class([
                                'min-w-9 rounded-lg border px-3 py-1.5',
                                'border-brand-600 bg-brand-600 text-white' => $page == $paginator->currentPage(),
                                'border-line hover:bg-surface-muted' => $page != $paginator->currentPage(),
                            ])>{{ $page }}</button>
                @endforeach
            @endif
        @endforeach

        <button type="button" wire:click="nextPage('{{ $pageName }}')" @disabled(! $paginator->hasMorePages())
                class="rounded-lg border border-line px-3 py-1.5 hover:bg-surface-muted disabled:opacity-40">Suivant</button>
    </nav>
@endif
