<div id="chat-call-dock" class="fixed inset-0 z-[100] hidden items-center justify-center bg-black/60 p-4"
     data-ice-servers='@json($iceServers)'>
    <div class="w-full max-w-3xl overflow-hidden rounded-3xl bg-surface shadow-2xl">
        <div class="flex items-center justify-between border-b border-line px-5 py-4">
            <div><p id="chat-call-title" class="font-semibold">Appel</p><p id="chat-call-status" class="text-sm text-ink-muted">Connexion…</p></div>
            <button type="button" onclick="window.endChatCall?.()" class="rounded-lg px-3 py-2 text-sm text-red-600 hover:bg-red-50">Terminer</button>
        </div>
        <div class="relative grid min-h-72 gap-2 bg-slate-950 p-2 md:grid-cols-2">
            <video id="chat-call-remote" autoplay playsinline class="min-h-72 w-full rounded-2xl bg-slate-900 object-cover"></video>
            <video id="chat-call-local" autoplay muted playsinline class="absolute bottom-5 right-5 h-32 w-44 rounded-xl bg-slate-900 object-cover shadow-lg md:static md:h-full md:w-full"></video>
        </div>
        <div id="chat-call-incoming-actions" class="hidden justify-end gap-2 border-t border-line p-4">
            <button type="button" onclick="window.rejectChatCall?.()" class="rounded-xl border border-line px-4 py-2 text-sm">Refuser</button>
            <button type="button" onclick="window.acceptChatCall?.()" class="rounded-xl bg-emerald-600 px-4 py-2 text-sm font-semibold text-white">Répondre</button>
        </div>
    </div>
</div>
