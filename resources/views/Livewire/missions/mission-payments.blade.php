@php
    $fcfa = fn ($v) => number_format((int) $v, 0, ',', ' ').' FCFA';
    $isApproved = $mission->status === \App\Enums\MissionStatus::Approved;
    $states = ['unplanned' => ['Sans échéancier', 'bg-slate-100 text-slate-700'], 'unpaid' => ['Non payé', 'bg-amber-100 text-amber-900'], 'partial' => ['Partiellement payé', 'bg-sky-100 text-sky-800'], 'settled' => ['Soldé', 'bg-emerald-100 text-emerald-800']];
@endphp

<div>
@if ($rows)
<section class="space-y-4 rounded-2xl border border-line bg-surface p-5 shadow-sm">
    <div>
        <h3 class="text-sm font-semibold">Paiements des perdiems</h3>
        <p class="text-xs text-ink-muted">Un paiement enregistré n’est jamais modifié ni supprimé : en cas d’erreur, il est annulé avec un motif et le montant redevient à payer. Les montants ne figurent jamais sur l’ordre de mission.</p>
    </div>

    @error('payment')<p class="rounded-xl bg-rose-50 px-4 py-3 text-sm text-rose-800">{{ $message }}</p>@enderror
    @error('schedule')<p class="rounded-xl bg-rose-50 px-4 py-3 text-sm text-rose-800">{{ $message }}</p>@enderror

    @foreach ($rows as $row)
        @php($p = $row['participant'])
        @php($s = $row['summary'])
        <div class="space-y-3 rounded-xl border border-line p-4" wire:key="pp-{{ $p->id }}">
            <div class="flex flex-wrap items-start justify-between gap-2">
                <div>
                    <p class="text-sm font-medium">{{ $p->employee?->full_name }} <span class="text-xs font-normal text-ink-muted">· {{ $p->employee?->matricule }} · {{ $p->department?->name }}</span></p>
                    <p class="text-xs text-ink-muted">Perdiem total {{ $fcfa($p->total_amount) }} ({{ $p->days }} j × {{ $fcfa($p->daily_rate) }}) · payé {{ $fcfa($s['paid']) }} · reste {{ $fcfa($s['remaining']) }}</p>
                </div>
                <div class="flex items-center gap-2">
                    <span class="rounded-full px-2 py-0.5 text-xs {{ $states[$s['state']][1] }}">{{ $states[$s['state']][0] }}</span>
                    <x-document-actions :view="route('missions.perdiem-sheet', [$mission->id, $p->id])" :download="route('missions.perdiem-sheet', [$mission->id, $p->id, 'dl' => 1])" ext="pdf" :name="'Fiche de perdiem '.$p->employee?->full_name" />
                </div>
            </div>

            @if (! $isApproved && $s['paid'] > 0)
                <p class="rounded-lg bg-amber-50 px-3 py-2 text-xs text-amber-900">Mission annulée alors que {{ $fcfa($s['paid']) }} ont été versés : le remboursement est à traiter avec le bénéficiaire.</p>
            @endif

            @if ($row['payments']->isNotEmpty())
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm">
                        <thead class="text-xs text-ink-muted"><tr><th class="py-1 pr-3">Tranche</th><th class="py-1 pr-3 text-right">Montant</th><th class="py-1 pr-3">Échéance</th><th class="py-1 pr-3">Statut</th><th class="py-1"></th></tr></thead>
                        <tbody class="divide-y divide-line">
                            @foreach ($row['payments'] as $tr)
                                <tr wire:key="pay-{{ $tr->id }}" class="{{ $tr->status === 'voided' ? 'text-ink-muted line-through' : '' }}">
                                    <td class="py-2 pr-3">{{ $tr->label }}</td>
                                    <td class="py-2 pr-3 text-right">{{ $fcfa($tr->status === 'paid' ? $tr->paid_amount : $tr->planned_amount) }}</td>
                                    <td class="py-2 pr-3">{{ $tr->planned_date?->format('d/m/Y') ?? '—' }}</td>
                                    <td class="py-2 pr-3 no-underline">
                                        @if ($tr->status === 'paid')
                                            <span class="rounded-full bg-emerald-100 px-2 py-0.5 text-xs text-emerald-800">Payé le {{ $tr->paid_at?->format('d/m/Y') }}</span>
                                            <span class="block text-xs text-ink-muted">{{ $methods[$tr->method] ?? $tr->method }}@if ($tr->reference) · {{ $tr->reference }}@endif</span>
                                        @elseif ($tr->status === 'voided')
                                            <span class="text-xs no-underline">Annulé : {{ $tr->void_reason }}</span>
                                        @elseif ($tr->isOverdue())
                                            <span class="rounded-full bg-rose-100 px-2 py-0.5 text-xs text-rose-800">En retard</span>
                                        @else
                                            <span class="rounded-full bg-amber-100 px-2 py-0.5 text-xs text-amber-900">À payer</span>
                                        @endif
                                    </td>
                                    <td class="py-2 text-right">
                                        <div class="flex flex-wrap items-center justify-end gap-2">
                                            @if ($tr->status === 'planned' && $canRecord && $isApproved)
                                                <button type="button" wire:click="openPay({{ $tr->id }})" class="rounded-lg bg-indigo-600 px-3 py-1 text-xs font-semibold text-white hover:bg-indigo-500">Enregistrer le paiement</button>
                                            @endif
                                            @if ($tr->status === 'paid')
                                                @if ($tr->receipt_path)
                                                    <span class="text-xs text-ink-muted">Reçu</span>
                                                    <x-document-actions :view="route('missions.payment.receipt.view', [$mission->id, $tr->id])" :download="route('missions.payment.receipt', [$mission->id, $tr->id])" :ext="$tr->receiptExtension()" :name="'Reçu '.$tr->label" />
                                                @elseif ($canRecord)
                                                    <input type="file" wire:model="lateReceipts.{{ $tr->id }}" accept=".pdf,.jpg,.jpeg,.png" class="block w-40 text-xs">
                                                    <button type="button" wire:click="attachReceipt({{ $tr->id }})" wire:loading.attr="disabled" wire:target="lateReceipts.{{ $tr->id }},attachReceipt" class="rounded border border-line px-2 py-1 text-xs hover:bg-surface-muted disabled:opacity-60">Joindre le reçu</button>
                                                    @error("lateReceipts.$tr->id")<span class="text-xs text-rose-600">{{ $message }}</span>@enderror
                                                @endif
                                                @if ($canVoid)
                                                    <button type="button" wire:click="openVoid({{ $tr->id }})" class="rounded border border-rose-300 px-2 py-1 text-xs text-rose-700 hover:bg-rose-50">Annuler</button>
                                                @endif
                                            @endif
                                        </div>
                                    </td>
                                </tr>

                                {{-- Formulaire d'enregistrement du paiement --}}
                                @if ($payingId === $tr->id)
                                    <tr wire:key="payform-{{ $tr->id }}"><td colspan="5" class="bg-surface-muted p-3">
                                        <div class="grid gap-3 sm:grid-cols-4">
                                            <div><label class="mb-1 block text-xs font-medium">Montant payé (FCFA)</label><input type="number" min="1" max="{{ $tr->planned_amount }}" wire:model="pay.amount" class="input py-1"><p class="mt-0.5 text-xs text-ink-muted">Prévu : {{ $fcfa($tr->planned_amount) }}. Un montant inférieur crée un reliquat à payer.</p></div>
                                            <div><label class="mb-1 block text-xs font-medium">Date du paiement</label><input type="date" max="{{ now()->toDateString() }}" wire:model="pay.date" class="input py-1"></div>
                                            <div><label class="mb-1 block text-xs font-medium">Mode</label><select wire:model.live="pay.method" class="input py-1">@foreach ($methods as $key => $label)<option value="{{ $key }}">{{ $label }}</option>@endforeach</select></div>
                                            <div>@if (($pay['method'] ?? 'cash') !== 'cash')<label class="mb-1 block text-xs font-medium">Référence de la transaction</label><input type="text" wire:model="pay.reference" class="input py-1">@endif</div>
                                            <div class="sm:col-span-2"><label class="mb-1 block text-xs font-medium">Note (facultatif)</label><input type="text" wire:model="pay.note" class="input py-1"></div>
                                            <div class="sm:col-span-2"><label class="mb-1 block text-xs font-medium">Reçu signé (facultatif, PDF ou image)</label><input type="file" wire:model="receipt" accept=".pdf,.jpg,.jpeg,.png" class="block w-full text-xs">@error('receipt')<p class="text-xs text-rose-600">{{ $message }}</p>@enderror</div>
                                        </div>
                                        <div class="mt-3 flex justify-end gap-2">
                                            <button type="button" wire:click="cancelForm" class="rounded-lg border border-line px-3 py-1.5 text-sm hover:bg-surface">Annuler</button>
                                            <button type="button" wire:click="recordPayment" wire:loading.attr="disabled" wire:target="recordPayment,receipt" wire:confirm="Enregistrer ce paiement ? Il ne pourra plus être modifié, seulement annulé avec un motif." class="rounded-lg bg-indigo-600 px-3 py-1.5 text-sm font-semibold text-white hover:bg-indigo-500 disabled:opacity-60">Confirmer le paiement</button>
                                        </div>
                                    </td></tr>
                                @endif

                                {{-- Formulaire d'annulation --}}
                                @if ($voidingId === $tr->id)
                                    <tr wire:key="voidform-{{ $tr->id }}"><td colspan="5" class="bg-rose-50 p-3">
                                        <label class="mb-1 block text-xs font-medium">Motif de l’annulation (obligatoire)</label>
                                        <input type="text" wire:model="voidReason" class="input py-1">
                                        <div class="mt-3 flex justify-end gap-2">
                                            <button type="button" wire:click="cancelForm" class="rounded-lg border border-line px-3 py-1.5 text-sm hover:bg-surface">Fermer</button>
                                            <button type="button" wire:click="confirmVoid" wire:confirm="Annuler ce paiement ? Le montant redeviendra à payer." class="rounded-lg border border-rose-300 px-3 py-1.5 text-sm text-rose-700 hover:bg-rose-100">Annuler le paiement</button>
                                        </div>
                                    </td></tr>
                                @endif
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <p class="text-xs text-ink-muted">Aucun échéancier pour ce participant.</p>
            @endif

            {{-- Échéancier (RH) --}}
            @if ($isHr && $isApproved && ! $row['hasPaid'] && $schedulingFor !== $p->id)
                <button type="button" wire:click="openSchedule({{ $p->id }})" class="rounded-lg border border-line px-3 py-1 text-xs hover:bg-surface-muted">{{ $row['payments']->isEmpty() ? 'Définir l’échéancier' : 'Modifier l’échéancier' }}</button>
            @endif

            @if ($schedulingFor === $p->id)
                <div class="space-y-2 rounded-xl bg-surface-muted p-3">
                    <p class="text-xs text-ink-muted">Découpez le perdiem de {{ $fcfa($p->total_amount) }} en tranches. Le total des tranches doit être exactement égal au perdiem.</p>
                    @foreach ($scheduleRows as $i => $r)
                        <div class="grid gap-2 sm:grid-cols-4" wire:key="sr-{{ $i }}">
                            <input type="text" wire:model="scheduleRows.{{ $i }}.label" class="input py-1 sm:col-span-2" placeholder="Libellé (ex. Avance)">
                            <input type="number" min="1" wire:model.live.debounce.300ms="scheduleRows.{{ $i }}.amount" class="input py-1" placeholder="Montant">
                            <div class="flex gap-2"><input type="date" wire:model="scheduleRows.{{ $i }}.date" class="input py-1"><button type="button" wire:click="removeScheduleRow({{ $i }})" class="text-xs text-rose-600 hover:underline">Retirer</button></div>
                        </div>
                    @endforeach
                    @php($sum = collect($scheduleRows)->sum(fn ($r) => (int) ($r['amount'] ?? 0)))
                    <p class="text-xs {{ $sum === (int) $p->total_amount ? 'text-emerald-700' : 'text-rose-600' }}">Total saisi : {{ $fcfa($sum) }} sur {{ $fcfa($p->total_amount) }}</p>
                    <div class="flex flex-wrap justify-end gap-2">
                        <button type="button" wire:click="addScheduleRow" class="rounded-lg border border-line px-3 py-1.5 text-sm hover:bg-surface">Ajouter une tranche</button>
                        <button type="button" wire:click="cancelForm" class="rounded-lg border border-line px-3 py-1.5 text-sm hover:bg-surface">Fermer</button>
                        <button type="button" wire:click="saveSchedule" class="rounded-lg bg-indigo-600 px-3 py-1.5 text-sm font-semibold text-white hover:bg-indigo-500">Enregistrer l’échéancier</button>
                    </div>
                </div>
            @endif
        </div>
    @endforeach
</section>
@endif
</div>
