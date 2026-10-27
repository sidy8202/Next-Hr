@php
    $styles = ['awaiting_response' => 'bg-amber-100 text-amber-900', 'responded' => 'bg-sky-100 text-sky-800', 'decided' => 'bg-emerald-100 text-emerald-800', 'withdrawn' => 'bg-slate-100 text-slate-700'];
    $ext = fn ($d) => pathinfo($d->file_path, PATHINFO_EXTENSION);
@endphp

<div class="space-y-6">
    <div class="flex flex-wrap items-start justify-between gap-3">
        <div>
            <h2 class="text-2xl font-bold">{{ $case->subject }}</h2>
            <p class="text-sm text-ink-muted">Réf. {{ $case->reference }} · {{ $case->employee?->full_name }} ({{ $case->employee?->matricule }}) · par {{ $case->signatory_name }}</p>
        </div>
        <span class="rounded-full px-3 py-1 text-sm font-medium {{ $styles[$case->status] ?? '' }}">{{ $case->statusLabel() }}@if ($case->decisionLabel()) · {{ $case->decisionLabel() }}@endif</span>
    </div>

    @if (session('status'))<p class="rounded-xl bg-emerald-50 px-4 py-3 text-sm text-emerald-900 dark:bg-emerald-500/10 dark:text-emerald-200">{{ session('status') }}</p>@endif
    @error('explanation')<p class="rounded-xl bg-rose-50 px-4 py-3 text-sm text-rose-800 dark:bg-rose-500/10 dark:text-rose-200">{{ $message }}</p>@enderror

    {{-- Déroulé du dossier --}}
    <section class="rounded-2xl border border-line bg-surface p-5 shadow-sm">
        <h3 class="mb-3 text-sm font-semibold">Déroulé du dossier</h3>
        <ol class="space-y-3 text-sm">
            <li class="flex flex-wrap justify-between gap-2">
                <div>
                    <p class="font-medium">1. Demande d’explication envoyée le {{ $case->created_at->format('d/m/Y H:i') }}</p>
                    <p class="text-xs text-ink-muted">Réponse attendue avant le {{ $case->response_deadline->format('d/m/Y') }} ({{ $case->response_days }} jour(s) ouvré(s))
                        @if ($case->employee_viewed_at) · lue par l’employé le {{ $case->employee_viewed_at->format('d/m/Y H:i') }} @else · pas encore ouverte par l’employé @endif
                        @if ($case->isOverdue()) · <span class="font-semibold text-rose-600">délai dépassé</span>@endif</p>
                </div>
            </li>
            <li>
                <p class="font-medium">2. Réponse de l’employé @if ($case->responded_at) — {{ $case->responded_at->format('d/m/Y H:i') }}@if ($case->responded_at->gt($case->response_deadline->copy()->endOfDay())) <span class="text-rose-600">(en retard)</span>@endif @else — en attente @endif</p>
                @if ($case->response_body)
                    <p class="mt-1 whitespace-pre-line rounded-lg bg-surface-muted px-3 py-2 text-ink-muted">{{ $case->response_body }}</p>
                    @if ($case->response_attachment_path)
                        <p class="mt-1 text-xs">Pièce jointe : {{ $case->response_attachment_name }}
                            <x-document-actions :view="route('explanations.attachment.view', $case->id)" :download="route('explanations.attachment', $case->id)" :ext="pathinfo($case->response_attachment_path, PATHINFO_EXTENSION)" :name="$case->response_attachment_name" /></p>
                    @endif
                @endif
            </li>
            <li>
                <p class="font-medium">3. Décision @if ($case->decided_at) — {{ $case->decisionLabel() }}, le {{ $case->decided_at->format('d/m/Y') }} @else — pas encore rendue @endif</p>
                @if ($case->decision_type === 'suspension')<p class="text-xs text-ink-muted">Du {{ $case->suspension_start->format('d/m/Y') }} au {{ $case->suspension_end->format('d/m/Y') }}, rémunération {{ $case->suspension_paid ? 'maintenue' : 'non maintenue' }}.</p>@endif
                @if ($case->decision_type === 'dismissal')<p class="text-xs text-ink-muted">Date d’effet : {{ $case->dismissal_effective_date->format('d/m/Y') }}. Les modalités (préavis, indemnités, solde) se traitent dans les sorties d’effectif.</p>@endif
                @if ($case->withdrawn_reason)<p class="text-xs text-ink-muted">Demande retirée : {{ $case->withdrawn_reason }}</p>@endif
            </li>
        </ol>
    </section>

    {{-- Lettres du dossier (PDF) --}}
    <section class="rounded-2xl border border-line bg-surface p-5 shadow-sm">
        <h3 class="mb-3 text-sm font-semibold">Lettres du dossier</h3>
        <ul class="divide-y divide-line text-sm">
            @foreach ($case->documents as $doc)
                <li class="flex flex-wrap items-center justify-between gap-2 py-2">
                    <span>{{ $doc->kindLabel() }} <span class="text-xs text-ink-muted">· réf. {{ $doc->reference }} · {{ $doc->created_at?->format('d/m/Y H:i') }}</span></span>
                    <x-document-actions :view="route('explanations.letter.view', [$case->id, $doc->id])" :download="route('explanations.letter', [$case->id, $doc->id])" :ext="$ext($doc)" :name="$doc->kindLabel().' — '.$doc->reference" />
                </li>
            @endforeach
        </ul>
    </section>

    {{-- Réponse de l'employé --}}
    @if ($isEmployee && $case->status === 'awaiting_response')
        <form wire:submit="respond" class="space-y-3 rounded-2xl border border-indigo-300 bg-surface p-5 shadow-sm">
            <h3 class="text-sm font-semibold">Votre réponse</h3>
            <p class="text-xs text-ink-muted">Votre réponse sera mise en forme comme une lettre (vos nom, matricule et fonction), en PDF, et envoyée à votre chef, à la RH et à vous-même.</p>
            <textarea rows="7" wire:model="response_body" class="input" placeholder="Exposez vos explications…"></textarea>
            @error('response_body')<p class="text-xs text-rose-600">{{ $message }}</p>@enderror
            <div>
                <label class="mb-1 block text-xs font-medium">Pièce jointe (justificatif, facultatif)</label>
                <input type="file" wire:model="response_attachment" accept=".pdf,.jpg,.jpeg,.png" class="block w-full text-sm">
                @error('response_attachment')<p class="mt-1 text-xs text-rose-600">{{ $message }}</p>@enderror
            </div>
            <div class="flex justify-end"><button type="submit" wire:loading.attr="disabled" wire:target="respond,response_attachment" wire:confirm="Envoyer votre réponse ?" class="rounded-xl bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-500 disabled:opacity-60">Envoyer ma réponse</button></div>
        </form>
    @endif

    {{-- Décision (RH) --}}
    @if ($isHr && in_array($case->status, ['awaiting_response', 'responded'], true) && ($case->status === 'responded' || $case->isOverdue()))
        <form wire:submit="decide" class="space-y-3 rounded-2xl border border-line bg-surface p-5 shadow-sm">
            <h3 class="text-sm font-semibold">Décision de la RH</h3>
            <p class="text-xs text-ink-muted">La lettre de décision est générée en PDF, jointe à ce dossier et envoyée à l’employé, au chef et à la RH. Les textes proposés sont des points de départ : adaptez-les à votre règlement intérieur.</p>
            <div class="grid gap-3 sm:grid-cols-2">
                <div>
                    <label class="mb-1 block text-xs font-medium">Décision</label>
                    <select wire:model.live="decision_type" class="input"><option value="">Choisir…</option>@foreach (config('discipline.decisions') as $key => $t)<option value="{{ $key }}">{{ $t['label'] }}</option>@endforeach</select>
                </div>
                <div><label class="mb-1 block text-xs font-medium">Objet de la lettre</label><input type="text" wire:model="decision_subject" class="input"></div>
            </div>
            @if ($decision_type === 'suspension')
                <div class="grid gap-3 sm:grid-cols-3">
                    <div><label class="mb-1 block text-xs font-medium">Début</label><input type="date" wire:model="suspension_start" class="input"></div>
                    <div><label class="mb-1 block text-xs font-medium">Fin</label><input type="date" wire:model="suspension_end" class="input"></div>
                    <label class="flex items-center gap-2 pt-5 text-xs"><input type="checkbox" wire:model="suspension_paid"> Rémunération maintenue</label>
                </div>
            @endif
            @if ($decision_type === 'dismissal')
                <p class="rounded-lg bg-amber-50 px-3 py-2 text-xs text-amber-900">Un licenciement suit une procédure légale (préavis, indemnités, solde de tout compte) : faites relire la lettre par un juriste, puis traitez la sortie dans les sorties d’effectif.</p>
                <div class="max-w-xs"><label class="mb-1 block text-xs font-medium">Date d’effet</label><input type="date" wire:model="dismissal_effective_date" class="input"></div>
            @endif
            <textarea rows="8" wire:model="decision_body" class="input" placeholder="Corps de la lettre de décision…"></textarea>
            <div class="flex justify-end"><button type="submit" wire:confirm="Rendre cette décision ? La lettre sera envoyée." class="rounded-xl bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-500">Rendre la décision</button></div>
        </form>
    @endif

    {{-- Retrait --}}
    @if (($isIssuer || $isHr) && $case->status === 'awaiting_response')
        <form wire:submit="withdraw" class="flex flex-wrap items-end gap-3 rounded-2xl border border-line bg-surface p-4 shadow-sm">
            <div class="grow"><label class="mb-1 block text-xs font-medium">Retirer cette demande (motif obligatoire)</label><input type="text" wire:model="withdraw_reason" class="input py-1"></div>
            <button type="submit" wire:confirm="Retirer cette demande d’explication ?" class="rounded-lg border border-rose-300 px-3 py-1.5 text-sm text-rose-700 hover:bg-rose-50">Retirer</button>
        </form>
    @endif

    <a href="{{ route('explanations.index') }}" wire:navigate class="inline-flex items-center rounded-xl border border-line px-4 py-2 text-sm hover:bg-surface-muted">Retour à la liste</a>
</div>
