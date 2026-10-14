@props(['selectedCount' => 0])

<div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between bg-surface p-4 rounded-2xl border border-line shadow-sm">
    <div class="flex items-center gap-3">
        @if ($selectedCount > 0)
            <span class="inline-flex items-center rounded-xl bg-brand-50 px-3 py-1.5 text-xs font-semibold text-brand-700 border border-brand-200">
                {{ $selectedCount }} sélectionné(s)
            </span>
        @endif
        @if (isset($actions))
            <div class="flex items-center gap-2">
                {{ $actions }}
            </div>
        @endif
    </div>

    @if (isset($filters))
        <div class="flex items-center gap-3">
            {{ $filters }}
        </div>
    @endif
</div>
