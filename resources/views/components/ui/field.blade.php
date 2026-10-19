@props(['label', 'name' => null, 'hint' => null, 'required' => false])

<div {{ $attributes->only('class') }}>
    <label @if ($name) for="{{ $name }}" @endif class="mb-1 block text-sm font-medium">
        {{ $label }}@if ($required)<span class="text-rose-600"> *</span>@endif
    </label>
    {{ $slot }}
    @if ($hint)<p class="mt-1 text-xs text-ink-muted">{{ $hint }}</p>@endif
    @if ($name)
        @error($name)<p class="mt-1 text-sm text-rose-600">{{ $message }}</p>@enderror
    @endif
</div>
