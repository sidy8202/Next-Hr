@props(['delay' => 0])

{{-- Apparition en fondu et glissement, déclenchée après l'écran de chargement. --}}
<div x-data="{ on: false }" x-init="nexthrReady(() => setTimeout(() => on = true, {{ (int) $delay }}))"
     :class="on ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-4'"
     {{ $attributes->class('transition duration-700 ease-out motion-reduce:transition-none') }}>{{ $slot }}</div>
