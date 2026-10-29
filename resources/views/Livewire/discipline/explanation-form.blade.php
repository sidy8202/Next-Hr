
<div class="space-y-6">
    @include('pdf.letter-styles')
    <div>
        <h2 class="text-2xl font-bold">Nouvelle demande d’explication</h2>
        <p class="text-sm text-ink-muted">Remplissez les champs de la lettre : l’aperçu à droite est exactement ce qui sera généré en PDF et envoyé à l’employé et à la RH.</p>
    </div>

    @error('explanation')<p class="rounded-xl bg-rose-50 px-4 py-3 text-sm text-rose-800 dark:bg-rose-500/10 dark:text-rose-200">{{ $message }}</p>@enderror

    <div class="grid gap-6 xl:grid-cols-2">
        <form wire:submit="save" class="space-y-5">
            <section class="space-y-4 rounded-2xl border border-line bg-surface p-5 shadow-sm">
                <div>
                    <label class="mb-1 block text-sm font-medium">Destinataire</label>
                    <select wire:model.live="employee_id" class="input">
                        <option value="">Choisir l’employé…</option>
                        @foreach ($this->employees as $e)<option value="{{ $e->id }}">{{ $e->last_name }} {{ $e->first_name }} — {{ $e->matricule }}</option>@endforeach
                    </select>
                    @error('employee_id')<p class="mt-1 text-xs text-rose-600">{{ $message }}</p>@enderror
                    <p class="mt-1 text-xs text-ink-muted">Son nom complet et son matricule figurent sur la lettre.</p>
                </div>
                <div>
                    <label class="mb-1 block text-sm font-medium">Modèle de départ (facultatif)</label>
                    <select wire:model.live="template" class="input">
                        <option value="">Aucun</option>
                        @foreach (config('discipline.request_templates') as $key => $t)<option value="{{ $key }}">{{ $t['label'] }}</option>@endforeach
                    </select>
                </div>
                <div>
                    <label class="mb-1 block text-sm font-medium">Objet</label>
                    <input type="text" wire:model.live.debounce.400ms="subject" class="input">
                    @error('subject')<p class="mt-1 text-xs text-rose-600">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="mb-1 block text-sm font-medium">Corps de la lettre</label>
                    <textarea rows="9" wire:model.live.debounce.500ms="body" class="input" placeholder="Date, lieu et faits reprochés, texte ou règle concernée…"></textarea>
                    @error('body')<p class="mt-1 text-xs text-rose-600">{{ $message }}</p>@enderror
                </div>
            </section>

            <section class="grid gap-4 rounded-2xl border border-line bg-surface p-5 shadow-sm sm:grid-cols-2">
                <div>
                    <label class="mb-1 block text-sm font-medium">Délai de réponse (jours ouvrés)</label>
                    <input type="number" min="1" max="30" wire:model.live="response_days" class="input">
                    @if ($this->deadline)<p class="mt-1 text-xs text-ink-muted">Réponse attendue au plus tard le <strong>{{ $this->deadline->translatedFormat('l d/m/Y') }}</strong>.</p>@endif
                    @error('response_days')<p class="mt-1 text-xs text-rose-600">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="mb-1 block text-sm font-medium">Copie à</label>
                    <input type="text" wire:model.live.debounce.400ms="copies" class="input">
                </div>
                <div>
                    <label class="mb-1 block text-sm font-medium">Signataire (nom)</label>
                    <input type="text" wire:model.live.debounce.400ms="signatory_name" class="input">
                    @error('signatory_name')<p class="mt-1 text-xs text-rose-600">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="mb-1 block text-sm font-medium">Fonction du signataire</label>
                    <input type="text" wire:model.live.debounce.400ms="signatory_title" class="input">
                </div>
            </section>

            <div class="flex justify-end gap-3">
                <a href="{{ route('explanations.index') }}" wire:navigate class="inline-flex items-center rounded-xl border border-line px-4 py-2 text-sm hover:bg-surface-muted">Annuler</a>
                <button type="submit" wire:loading.attr="disabled" wire:target="save" wire:confirm="Envoyer cette demande d’explication à l’employé et à la RH ?" class="inline-flex items-center rounded-xl bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-500 disabled:opacity-60">Envoyer la demande</button>
            </div>
        </form>

        <div class="overflow-x-auto rounded-2xl border border-line bg-white p-6 shadow-sm" style="color: #1f2937;">
            <x-letter :letterhead="$this->letterhead" reference="DE-{{ now()->year }}-… (attribuée à l’envoi)" :date="now()"
                      :subject="$subject ?: '…'" :body="$body ?: '…'" :signatory-name="$signatory_name" :signatory-title="$signatory_title"
                      :recipient="$this->target ? array_values(array_filter([$this->target->full_name, 'Matricule : '.$this->target->matricule, $this->target->position?->title, $this->target->department?->name])) : ['Choisissez l’employé']"
                      :copies="$copies" :deadline="$this->deadline" :deadline-days="(int) $response_days" :show-receipt="true" banner="Demande d’explication" />
        </div>
    </div>
</div>
