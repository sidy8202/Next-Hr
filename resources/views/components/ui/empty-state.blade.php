@props(['title', 'text' => null, 'icon' => 'inbox'])

<div class="flex flex-col items-center justify-center px-6 py-12 text-center">
    <span class="flex size-12 items-center justify-center rounded-2xl bg-surface-muted text-ink-muted">
        <x-dynamic-component :component="'heroicon-o-'.$icon" class="size-6" />
    </span>
    <p class="mt-4 text-sm font-semibold">{{ $title }}</p>
    @if ($text)<p class="mt-1 max-w-sm text-sm text-ink-muted">{{ $text }}</p>@endif
    @if (trim((string) $slot) !== '')<div class="mt-4">{{ $slot }}</div>@endif
</div>
