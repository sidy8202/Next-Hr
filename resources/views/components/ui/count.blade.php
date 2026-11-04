@props(['value' => 0, 'prefix' => '', 'suffix' => ''])

{{-- Nombre qui s'incrémente jusqu'à sa valeur. Le texte serveur reste affiché sans JavaScript. --}}
<span x-data="{ n: 0 }"
      x-init="nexthrReady(() => { const t = {{ (float) $value }}, d = 1200, s = performance.now(); const f = (now) => { const k = Math.min(1, (now - s) / d); n = t * (1 - Math.pow(1 - k, 3)); if (k < 1) requestAnimationFrame(f); }; requestAnimationFrame(f); })"
      x-text="{{ Js::from($prefix) }} + Math.round(n).toLocaleString('fr-FR') + {{ Js::from($suffix) }}">{{ $prefix }}{{ number_format((float) $value, 0, ',', ' ') }}{{ $suffix }}</span>
