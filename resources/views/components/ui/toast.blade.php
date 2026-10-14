<div x-data="{ items: [], add(d) { const id = Date.now() + Math.random(); this.items.push({ id, ...d }); setTimeout(() => this.items = this.items.filter(i => i.id !== id), 4500) } }"
     @notify.window="add($event.detail)" aria-live="polite"
     class="pointer-events-none fixed bottom-4 right-4 z-[60] flex w-80 flex-col gap-2">
    <template x-for="t in items" :key="t.id">
        <div class="pointer-events-auto rounded-xl border px-4 py-3 text-sm shadow-lg font-medium transition"
             :class="t.type === 'error' ? 'border-rose-300 bg-rose-50 text-rose-900 dark:bg-rose-950 dark:border-rose-800 dark:text-rose-200' : 'border-emerald-300 bg-emerald-50 text-emerald-900 dark:bg-emerald-950 dark:border-emerald-800 dark:text-emerald-200'"
             x-text="t.message"></div>
    </template>
</div>
