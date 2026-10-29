@php
    $styles = ['draft' => 'bg-slate-100 text-slate-700', 'submitted' => 'bg-amber-100 text-amber-900', 'returned' => 'bg-rose-100 text-rose-800', 'hr_approved' => 'bg-sky-100 text-sky-800', 'approved' => 'bg-emerald-100 text-emerald-800', 'rejected' => 'bg-rose-100 text-rose-800', 'cancelled' => 'bg-slate-100 text-slate-700'];
    $decisions = ['approved' => 'Approuvé', 'returned' => 'Renvoyé pour correction', 'rejected' => 'Rejeté'];
    $fcfa = fn ($v) => $v === null ? '—' : number_format($v, 0, ',', ' ').' FCFA';
    $status = $mission->status->value;
@endphp

<div class="space-y-6">
    <div class="flex flex-wrap items-start justify-between gap-3">
        <div>
            <h2 class="text-2xl font-bold">{{ $mission->purpose }}</h2>
            <p class="text-sm text-ink-muted">{{ $mission->reference ?? 'Brouillon' }} · {{ $mission->destination_zone }} · du {{ $mission->start_date->format('d/m/Y') }} au {{ $mission->end_date->format('d/m/Y') }} · {{ $mission->department?->name }} · par {{ $mission->requester?->name }}</p>
            @if ($mission->lodging)<p class="text-xs text-ink-muted">Hébergement : {{ $mission->lodging }}</p>@endif
        </div>
        <span class="rounded-full px-3 py-1 text-sm font-medium {{ $styles[$status] ?? '' }}">{{ $mission->status->label() }}</span>
    </div>

    @if (session('status'))<p class="rounded-xl bg-emerald-50 px-4 py-3 text-sm text-emerald-900 dark:bg-emerald-500/10 dark:text-emerald-200">{{ session('status') }}</p>@endif
    @error('mission')<p class="rounded-xl bg-rose-50 px-4 py-3 text-sm text-rose-800">{{ $message }}</p>@enderror
    @error('review')<p class="rounded-xl bg-rose-50 px-4 py-3 text-sm text-rose-800">{{ $message }}</p>@enderror
    @error('comment')<p class="rounded-xl bg-rose-50 px-4 py-3 text-sm text-rose-800">{{ $message }}</p>@enderror

    @if ($mission->program)
        <section class="rounded-2xl border border-line bg-surface p-5 text-sm shadow-sm"><h3 class="mb-1 text-sm font-semibold">Programme</h3><p class="whitespace-pre-line text-ink-muted">{{ $mission->program }}</p></section>
    @endif

    @if ($mission->cancellation_reason)
        <p class="rounded-xl bg-slate-100 px-4 py-3 text-sm">Mission annulée le {{ $mission->cancelled_at?->format('d/m/Y') }} : {{ $mission->cancellation_reason }}</p>
    @endif

    {{-- Participants (lecture) --}}
    <section class="rounded-2xl border border-line bg-surface p-5 shadow-sm">
        <h3 class="mb-3 text-sm font-semibold">Participants</h3>
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead class="text-xs text-ink-muted"><tr><th class="py-2 pr-3">Participant</th><th class="py-2 pr-3">Rôle</th><th class="py-2 pr-3">Jours</th><th class="py-2 pr-3">Perdiem / jour</th><th class="py-2 pr-3">Total</th><th class="py-2">Paiement</th></tr></thead>
                <tbody class="divide-y divide-line">
                    @foreach ($mission->participants as $p)
                        <tr wire:key="sp-{{ $p->id }}">
                            <td class="py-2 pr-3">{{ $p->employee?->full_name }}<span class="block text-xs text-ink-muted">{{ $p->employee?->matricule }} · {{ $p->employee?->position?->title }} · {{ $p->department?->name }}</span></td>
                            <td class="py-2 pr-3">{{ $p->role->label() }}</td>
                            <td class="py-2 pr-3">{{ $p->days }}</td>
                            @if ($visible[$p->id])
                                <td class="py-2 pr-3">{{ $p->daily_rate ? $fcfa($p->daily_rate) : $p->perdiem_mode->label() }}</td>
                                <td class="py-2 pr-3">{{ $fcfa($p->total_amount) }}</td>
                            @else
                                <td class="py-2 pr-3 text-xs italic text-ink-muted" colspan="2">Confidentiel</td>
                            @endif
                            <td class="py-2">{{ config('missions.payment_modes.'.$p->payment_mode) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </section>

    {{-- Historique --}}
    @if ($mission->approvals->isNotEmpty())
        <section class="rounded-2xl border border-line bg-surface p-5 shadow-sm">
            <h3 class="mb-3 text-sm font-semibold">Historique des décisions</h3>
            <ul class="space-y-2 text-sm">
                @foreach ($mission->approvals as $a)
                    <li><span class="font-medium">{{ $a->step === 'hr' ? 'RH' : 'Direction' }} — {{ $decisions[$a->decision] ?? $a->decision }}</span> <span class="text-xs text-ink-muted">· {{ $a->user?->name }} · {{ $a->decided_at->format('d/m/Y H:i') }}</span>@if ($a->comment)<p class="text-xs text-ink-muted">{{ $a->comment }}</p>@endif</li>
                @endforeach
            </ul>
        </section>
    @endif

    {{-- Vérification RH --}}
    @if ($isHr && $status === 'submitted')
        <section class="space-y-4 rounded-2xl border border-indigo-300 bg-surface p-5 shadow-sm">
            <h3 class="text-sm font-semibold">Vérification RH : perdiems et modalités</h3>
            <p class="text-xs text-ink-muted">Pour chaque participant, fixez le perdiem (montant manuel ou salaire journalier), ajustez les jours si besoin et confirmez la modalité de paiement. Les montants sont figés à la validation.</p>
            @foreach ($mission->participants as $p)
                <div class="grid gap-3 rounded-xl border border-line p-3 sm:grid-cols-5" wire:key="rv-{{ $p->id }}">
                    <div class="sm:col-span-5 text-sm font-medium">{{ $p->employee?->full_name }} <span class="text-xs font-normal text-ink-muted">· {{ $p->department?->name }} · {{ $p->role->label() }}</span></div>
                    <div><label class="mb-1 block text-xs font-medium">Mode</label>
                        <select wire:model.live="review.{{ $p->id }}.perdiem_mode" class="input py-1"><option value="manual">Montant manuel</option><option value="daily_salary">Salaire journalier</option></select></div>
                    <div><label class="mb-1 block text-xs font-medium">Jours</label><input type="number" min="1" max="366" wire:model="review.{{ $p->id }}.days" class="input py-1"></div>
                    <div>
                        @if (($review[$p->id]['perdiem_mode'] ?? 'manual') === 'manual')
                            <label class="mb-1 block text-xs font-medium">Perdiem / jour (FCFA)</label><input type="number" min="0" wire:model="review.{{ $p->id }}.daily_rate" class="input py-1">
                        @else
                            <label class="mb-1 block text-xs font-medium">Diviseur du salaire</label>
                            <select wire:model="review.{{ $p->id }}.salary_divisor" class="input py-1">@foreach (\App\Services\PerdiemCalculator::ALLOWED_DIVISORS as $d)<option value="{{ $d }}">{{ $d }} jours</option>@endforeach</select>
                        @endif
                    </div>
                    <div class="sm:col-span-2"><label class="mb-1 block text-xs font-medium">Paiement</label>
                        <select wire:model="review.{{ $p->id }}.payment_mode" class="input py-1">@foreach (config('missions.payment_modes') as $key => $label)<option value="{{ $key }}">{{ $label }}</option>@endforeach</select></div>
                </div>
            @endforeach
            <div><label class="mb-1 block text-xs font-medium">Commentaire (obligatoire pour un retour ou un rejet)</label><textarea rows="2" wire:model="comment" class="input"></textarea></div>
            <div class="flex flex-wrap justify-end gap-2">
                <button type="button" wire:click="hrDecide('rejected')" wire:confirm="Rejeter cette mission ?" class="rounded-xl border border-rose-300 px-4 py-2 text-sm text-rose-700 hover:bg-rose-50">Rejeter</button>
                <button type="button" wire:click="hrDecide('returned')" class="rounded-xl border border-line px-4 py-2 text-sm hover:bg-surface-muted">Renvoyer pour correction</button>
                <button type="button" wire:click="hrDecide('approved')" wire:confirm="Valider et transmettre au DG ? Les perdiems seront figés." class="rounded-xl bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-500">Valider et transmettre au DG</button>
            </div>
        </section>
    @endif

    {{-- Approbation DG --}}
    @if ($isDg && $status === 'hr_approved')
        <section class="space-y-3 rounded-2xl border border-indigo-300 bg-surface p-5 shadow-sm">
            <h3 class="text-sm font-semibold">Approbation de la direction</h3>
            <p class="text-sm text-ink-muted">Budget total estimé : <strong>{{ $fcfa($mission->participants->sum('total_amount')) }}</strong> pour {{ $mission->participants->count() }} participant(s).</p>
            <div><label class="mb-1 block text-xs font-medium">Commentaire (obligatoire pour un retour ou un rejet)</label><textarea rows="2" wire:model="comment" class="input"></textarea></div>
            <div class="flex flex-wrap justify-end gap-2">
                <button type="button" wire:click="dgDecide('rejected')" wire:confirm="Rejeter cette mission ?" class="rounded-xl border border-rose-300 px-4 py-2 text-sm text-rose-700 hover:bg-rose-50">Rejeter</button>
                <button type="button" wire:click="dgDecide('returned')" class="rounded-xl border border-line px-4 py-2 text-sm hover:bg-surface-muted">Renvoyer pour correction</button>
                <button type="button" wire:click="dgDecide('approved')" wire:confirm="Approuver cette mission ?" class="rounded-xl bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-500">Approuver</button>
            </div>
        </section>
    @endif

    {{-- Ordres de mission individuels --}}
    @if ($status === 'approved')
        <section class="space-y-3 rounded-2xl border border-line bg-surface p-5 shadow-sm">
            <div class="flex flex-wrap items-center justify-between gap-2">
                <h3 class="text-sm font-semibold">Ordres de mission individuels</h3>
                <span class="rounded-full bg-surface-muted px-2 py-0.5 text-xs">{{ $signedCount }} signé(s) sur {{ $mission->participants->count() }}</span>
            </div>
            @if ($isHr)
                <p class="text-xs text-ink-muted">Téléchargez chaque ordre, imprimez-le, faites-le signer (RH puis DG), puis déposez le scan signé. Aucun montant ne figure sur l’ordre : il peut être présenté aux autorités.</p>
                @if (collect($docs)->contains(fn ($d) => ! $d['hasOrder']))
                    <button type="button" wire:click="regenerateOrders" wire:loading.attr="disabled" class="rounded-lg border border-line px-3 py-1.5 text-sm hover:bg-surface-muted">Générer les ordres manquants</button>
                @endif
            @endif

            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead class="text-xs text-ink-muted"><tr><th class="py-2 pr-3">Participant</th>@if ($isHr || $isDg)<th class="py-2 pr-3">Ordre à signer</th>@endif<th class="py-2 pr-3">Ordre signé</th>@if ($isHr)<th class="py-2">Déposer le scan signé</th>@endif</tr></thead>
                    <tbody class="divide-y divide-line">
                        @foreach ($mission->participants as $p)
                            @php($d = $docs[$p->id])
                            <tr wire:key="doc-{{ $p->id }}">
                                <td class="py-2 pr-3">{{ $p->employee?->full_name }}<span class="block text-xs text-ink-muted">{{ $p->employee?->matricule }}</span></td>
                                @if ($isHr || $isDg)
                                    <td class="py-2 pr-3">
                                        @if ($d['order'])
                                            <span class="mr-1 text-xs">{{ $d['order']->reference }} (v{{ $d['order']->version }})</span>
                                            <x-document-actions :view="route('missions.document.view', [$mission->id, $d['order']->id])" :download="route('missions.document', [$mission->id, $d['order']->id])" :ext="$d['order']->extension()" :name="'Ordre '.$d['order']->reference" />
                                        @else<span class="text-xs text-ink-muted">Non généré</span>@endif
                                    </td>
                                @endif
                                <td class="py-2 pr-3">
                                    @if ($d['signed'])
                                        <span class="mr-1 rounded-full bg-emerald-100 px-2 py-0.5 text-xs text-emerald-800">Signé</span>
                                        <span class="mr-1 text-xs text-ink-muted">{{ $d['signed']->created_at->format('d/m/Y') }}</span>
                                        <x-document-actions :view="route('missions.document.view', [$mission->id, $d['signed']->id])" :download="route('missions.document', [$mission->id, $d['signed']->id])" :ext="$d['signed']->extension()" :name="'Ordre signé '.$d['signed']->reference" />
                                    @elseif ($d['hasSigned'])
                                        <span class="rounded-full bg-emerald-100 px-2 py-0.5 text-xs text-emerald-800">Signé</span>
                                    @else
                                        <span class="rounded-full bg-amber-100 px-2 py-0.5 text-xs text-amber-900">En attente de signature</span>
                                    @endif
                                </td>
                                @if ($isHr)
                                    <td class="py-2">
                                        <div class="flex flex-wrap items-center gap-2">
                                            <input type="file" wire:model="uploads.{{ $p->id }}" accept=".pdf,.jpg,.jpeg,.png" class="block w-48 text-xs">
                                            <button type="button" wire:click="uploadSigned({{ $p->id }})" wire:loading.attr="disabled" wire:target="uploads.{{ $p->id }},uploadSigned" wire:confirm="Déposer ce document signé ? Il sera envoyé au participant, au demandeur et au chef du participant." class="rounded-lg bg-indigo-600 px-3 py-1 text-xs font-semibold text-white hover:bg-indigo-500 disabled:opacity-60">{{ $d['hasSigned'] ? 'Remplacer' : 'Déposer' }}</button>
                                        </div>
                                        @error("uploads.$p->id")<p class="mt-1 text-xs text-rose-600">{{ $message }}</p>@enderror
                                    </td>
                                @endif
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </section>
    @endif

    {{-- Annulation --}}
    @if ($canCancel)
        <form wire:submit="cancel" class="flex flex-wrap items-end gap-3 rounded-2xl border border-line bg-surface p-4 shadow-sm">
            <div class="grow"><label class="mb-1 block text-xs font-medium">Annuler cette mission (motif obligatoire)</label><input type="text" wire:model="cancel_reason" class="input py-1">@error('cancel_reason')<p class="mt-1 text-xs text-rose-600">{{ $message }}</p>@enderror</div>
            <button type="submit" wire:confirm="Annuler cette mission ?" class="rounded-lg border border-rose-300 px-3 py-1.5 text-sm text-rose-700 hover:bg-rose-50">Annuler la mission</button>
        </form>
    @endif

    <div class="flex flex-wrap gap-3">
        <a href="{{ route('missions.index') }}" wire:navigate class="inline-flex items-center rounded-xl border border-line px-4 py-2 text-sm hover:bg-surface-muted">Retour à la liste</a>
        @if ($canEdit)<a href="{{ route('missions.edit', $mission->id) }}" wire:navigate class="inline-flex items-center rounded-xl bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-500">Modifier</a>@endif
    </div>
</div>
