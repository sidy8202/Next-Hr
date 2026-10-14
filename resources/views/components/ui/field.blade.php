@props([
    'label',
    'name',
    'hint' => null,
    'required' => false,
])

<div class="space-y-1.5">
    <label for="{{ $name }}" class="block text-xs font-semibold text-ink">
        {{ $label }}
        @if ($required)
            <span class="text-rose-500">*</span>
        @endif
    </label>

    {{ $slot }}

    @if ($hint)
        <p class="text-xs text-ink-muted">{{ $hint }}</p>
    @endif

    @error($name)
        <p class="text-xs font-medium text-rose-600">{{ $message }}</p>
    @enderror
</div>
