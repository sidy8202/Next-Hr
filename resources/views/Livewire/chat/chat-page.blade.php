<div class="space-y-5" data-chat-conversation="{{ $conversationId ?? '' }}" wire:poll.5s="refreshChat">
    <div class="relative overflow-hidden rounded-3xl border border-line bg-gradient-to-br from-surface via-surface to-brand-50 p-5 shadow-sm dark:to-brand-950/30 sm:p-7">
        <div class="pointer-events-none absolute -right-10 -top-16 size-52 rounded-full bg-brand-200/30 blur-3xl dark:bg-brand-500/10"></div>
        <div class="relative flex flex-wrap items-center justify-between gap-4">
            <div class="flex items-center gap-4">
                <span class="flex size-14 items-center justify-center rounded-2xl bg-brand-600 text-white shadow-lg shadow-brand-600/20"><x-heroicon-o-chat-bubble-left-right class="size-7" /></span>
                <div><p class="mb-1 text-xs font-semibold uppercase tracking-[0.18em] text-brand-600 dark:text-brand-300">Communication d’équipe</p><h2 class="text-2xl font-bold tracking-tight sm:text-3xl">Messagerie</h2><p class="mt-1 text-sm text-ink-muted">Échangez simplement avec les membres de votre entreprise.</p></div>
            </div>
        <details class="relative">
            <summary class="cursor-pointer list-none rounded-xl bg-brand-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-brand-700">Nouvelle conversation</summary>
            <div class="absolute right-0 z-20 mt-2 w-80 space-y-3 rounded-2xl border border-line bg-surface p-4 shadow-xl">
                <input wire:model.live.debounce.250ms="personSearch" class="input w-full" placeholder="Chercher un collègue">
                <div class="max-h-40 space-y-1 overflow-y-auto">
                    @foreach ($this->people as $person)
                        <button type="button" wire:click="startDirect({{ $person->id }})" class="block w-full rounded-lg px-3 py-2 text-left text-sm hover:bg-surface-muted">
                            {{ $person->employee?->full_name ?: $person->name }}
                        </button>
                    @endforeach
                </div>
                <hr class="border-line">
                <form wire:submit="startGroup" class="space-y-2">
                    <input wire:model="groupTitle" class="input w-full" maxlength="120" placeholder="Nom du groupe">
                    <div class="max-h-32 space-y-1 overflow-y-auto">
                        @foreach ($this->people as $person)
                            <label class="flex items-center gap-2 text-sm">
                                <input type="checkbox" wire:model="memberIds" value="{{ $person->id }}">
                                {{ $person->employee?->full_name ?: $person->name }}
                            </label>
                        @endforeach
                    </div>
                    @error('groupTitle') <p class="text-xs text-red-600">{{ $message }}</p> @enderror
                    @error('memberIds') <p class="text-xs text-red-600">{{ $message }}</p> @enderror
                    <button class="w-full rounded-xl bg-brand-600 px-3 py-2 text-sm font-semibold text-white" type="submit">Créer le groupe</button>
                </form>
            </div>
        </details>
        </div>
    </div>
    <div class="grid min-h-[68vh] overflow-hidden rounded-3xl border border-line bg-surface shadow-lg shadow-slate-950/5 lg:grid-cols-[20rem_minmax(0,1fr)]">
        <aside class="border-b border-line lg:border-b-0 lg:border-r">
            <div class="border-b border-line bg-surface-muted/50 p-3"><input wire:model.live.debounce.250ms="search" class="input w-full rounded-xl" placeholder="Rechercher une conversation"></div>
            <div class="max-h-[68vh] overflow-y-auto">
                @forelse ($conversations as $c)
                    @php
                        $others = $c->participants->reject(fn ($p) => (int) $p->user_id === (int) auth()->id())
                            ->map(fn ($p) => $p->user?->employee?->full_name ?: $p->user?->name)->filter()->values();
                        $title = $c->type === 'group' ? $c->title : ($others->first() ?: 'Conversation');
                    @endphp
                    <button type="button" wire:click="selectConversation({{ $c->id }})" @class(['flex w-full gap-3 border-b border-line px-4 py-3 text-left hover:bg-surface-muted', 'bg-brand-50 dark:bg-brand-500/10' => $conversationId === $c->id])>
                        <span class="flex size-11 shrink-0 items-center justify-center rounded-2xl bg-gradient-to-br from-brand-500 to-indigo-600 font-semibold text-white shadow-sm">{{ mb_strtoupper(mb_substr($title, 0, 1)) }}</span>
                        <span class="min-w-0 flex-1">
                            <span class="flex items-center justify-between gap-2"><span class="truncate text-sm font-semibold">{{ $title }}</span>
                                @if (($unread[$c->id] ?? 0) > 0)<span class="rounded-full bg-brand-600 px-2 text-xs text-white">{{ $unread[$c->id] }}</span>@endif
                            </span>
                            <span class="block truncate text-xs text-ink-muted">{{ $c->latestMessage?->body ?: 'Aucun message' }}</span>
                        </span>
                    </button>
                @empty
                    <p class="p-6 text-center text-sm text-ink-muted">Aucune conversation. Cherchez un collègue pour commencer.</p>
                @endforelse
            </div>
        </aside>
        <section class="flex min-h-[68vh] min-w-0 flex-col">
            @if ($this->currentConversation)
                @php
                    $people = $this->currentConversation->participants->map(fn ($p) => $p->user?->employee?->full_name ?: $p->user?->name)->filter()->values();
                    $activeTitle = $this->currentConversation->type === 'group' ? $this->currentConversation->title
                        : ($people->reject(fn ($n) => $n === (auth()->user()->employee?->full_name ?: auth()->user()->name))->first() ?: 'Conversation');
                @endphp
                <header class="flex items-center justify-between gap-3 border-b border-line px-5 py-4">
                    <div><h3 class="font-semibold">{{ $activeTitle }}</h3><p class="text-xs text-ink-muted">{{ $people->count() }} participant(s)</p></div>
                    <div class="flex gap-2">
                        <button type="button" x-on:click="window.enableChatNotifications?.()" class="rounded-lg border border-line px-3 py-2 text-xs font-medium hover:bg-surface-muted">Activer les notifications</button>
                        @if ($this->currentConversation->type === 'direct')
                            <button type="button" onclick="window.startChatCall?.({{ $this->currentConversation->id }}, @js($activeTitle))" class="rounded-lg bg-brand-600 px-3 py-2 text-xs font-semibold text-white hover:bg-brand-700">Appeler</button>
                        @endif
                    </div>
                </header>
                <div class="flex-1 space-y-3 overflow-y-auto bg-gradient-to-b from-surface-muted/30 to-surface px-4 py-5 sm:px-6">
                    @forelse ($this->messages as $message)
                        @php($mine = (int) $message->sender_id === (int) auth()->id())
                        <div wire:key="chat-message-{{ $message->id }}" @class(['flex', 'justify-end' => $mine])>
                            <div @class(['max-w-[min(85%,42rem)] rounded-2xl px-4 py-2.5 shadow-sm', 'rounded-br-md bg-brand-600 text-white' => $mine, 'rounded-bl-md bg-surface text-ink ring-1 ring-line' => !$mine])>
                                @unless ($mine)<p class="mb-1 text-xs font-semibold">{{ $message->sender?->employee?->full_name ?: $message->sender?->name }}</p>@endunless
                                @if ($message->body)<p class="whitespace-pre-wrap break-words text-sm">{{ $message->body }}</p>@endif
                                @if ($message->attachment_path)
                                    <a href="{{ route('chat.attachments.download', $message) }}" class="mt-2 flex items-center gap-2 rounded-xl border border-current/20 px-3 py-2 text-sm underline underline-offset-2">
                                        <x-heroicon-o-paper-clip class="size-4 shrink-0" />{{ $message->attachment_name ?: 'Télécharger le fichier' }}
                                    </a>
                                @endif
                                <p class="mt-1 text-right text-[10px] opacity-70">{{ $message->created_at?->format('H:i') }}</p>
                            </div>
                        </div>
                    @empty
                        <div class="flex h-full items-center justify-center text-sm text-ink-muted">Démarrez la conversation par un message.</div>
                    @endforelse
                </div>
                <form wire:submit="sendMessage" class="border-t border-line p-3">
                    @if ($attachment)
                        <div class="mb-2 flex items-center justify-between rounded-xl bg-surface-muted px-3 py-2 text-sm">
                            <span class="truncate">{{ $attachment->getClientOriginalName() }}</span>
                            <button type="button" wire:click="$set('attachment', null)" class="ml-2 text-red-600">Retirer</button>
                        </div>
                    @endif
                    <div class="flex items-end gap-2">
                        <label for="chat-body" class="sr-only">Votre message</label>
                        <textarea id="chat-body" wire:model="body" rows="2" maxlength="5000" class="input flex-1 resize-y" placeholder="Écrire un message…" wire:keydown.enter.prevent="sendMessage"></textarea>
                        <button class="rounded-xl bg-brand-600 px-4 py-3 text-sm font-semibold text-white" type="submit">Envoyer</button>
                    </div>
                    <div class="mt-2 flex items-center gap-2">
                        <label class="cursor-pointer rounded-lg border border-line px-3 py-1.5 text-xs font-medium hover:bg-surface-muted">
                            Joindre un fichier
                            <input type="file" class="sr-only" wire:model="attachment" accept=".pdf,.png,.jpg,.jpeg,.webp,.doc,.docx,.xls,.xlsx,.txt">
                        </label>
                        <span wire:loading wire:target="attachment" class="text-xs text-ink-muted">Chargement du fichier…</span>
                        <details class="relative">
                            <summary class="cursor-pointer list-none rounded-lg border border-line px-3 py-1.5 text-xs font-medium hover:bg-surface-muted" aria-label="Choisir un emoji">😊 Emojis</summary>
                            <div class="absolute bottom-9 left-0 z-20 grid w-48 grid-cols-6 gap-1 rounded-xl border border-line bg-surface p-2 shadow-xl">
                                @foreach (['😀','😂','😊','😍','👍','👏','🎉','🙏','❤️','😢','🤔','🔥'] as $emoji)
                                    <button type="button" wire:click="addEmoji('{{ $emoji }}')" class="rounded-lg p-1.5 text-lg hover:bg-surface-muted" aria-label="Ajouter {{ $emoji }}">{{ $emoji }}</button>
                                @endforeach
                            </div>
                        </details>
                    </div>
                    @error('attachment') <p class="mt-2 text-sm text-red-600">{{ $message }}</p> @enderror
                    @error('body') <p class="mt-2 text-sm text-red-600">{{ $message }}</p> @enderror
                </form>
            @else
                <div class="flex flex-1 flex-col items-center justify-center p-8 text-center">
                    <x-heroicon-o-chat-bubble-left-right class="size-10 text-brand-600" />
                    <h3 class="mt-3 text-lg font-semibold">Votre espace de discussion</h3>
                    <p class="max-w-sm text-sm text-ink-muted">Choisissez une conversation ou commencez à échanger avec un collègue.</p>
                </div>
            @endif
        </section>
    </div>
</div>
