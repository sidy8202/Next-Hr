{{-- Notifications à l'écran. Dans le layout : <x-ui.toast :flash="session('notify')" /> --}}
@props(['flash' => null])

<div x-data="{
        items: [],
        add(d) { const id = Date.now() + Math.random(); this.items.push({ id, ...d }); setTimeout(() => this.items = this.items.filter(i => i.id !== id), 5000) },
        init() { @if ($flash) this.add(@js($flash)); @endif }
     }"
     @notify.window="add($event.detail)" aria-live="polite"
     class="pointer-events-none fixed bottom-4 right-4 z-[60] flex w-80 flex-col gap-2">
    <template x-for="t in items" :key="t.id">
        <div class="pointer-events-auto rounded-xl border px-4 py-3 text-sm shadow-lg"
             :class="t.type === 'error' ? 'border-rose-300 bg-rose-50 text-rose-900' : 'border-emerald-300 bg-emerald-50 text-emerald-900'"
             x-text="t.message"></div>
    </template>
</div>
