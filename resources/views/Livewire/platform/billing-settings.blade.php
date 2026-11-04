<div class="space-y-6">
    <div>
        <h2 class="text-2xl font-bold">Facturation et e-mails</h2>
        <p class="text-sm text-ink-muted">L’identité qui apparaît sur vos factures et dans les e-mails envoyés aux entreprises (factures après paiement, rappels d’échéance).</p>
    </div>

    @error('test_to')<p class="rounded-xl bg-rose-50 px-4 py-3 text-sm text-rose-800">{{ $message }}</p>@enderror

    <div class="grid gap-6 xl:grid-cols-3">
        <form wire:submit="save" class="space-y-4 rounded-2xl border border-line bg-surface p-5 shadow-sm xl:col-span-2">
            <h3 class="text-sm font-semibold">Identité de l’éditeur</h3>
            <div class="grid gap-4 sm:grid-cols-2">
                <div class="sm:col-span-2"><label class="mb-1 block text-sm font-medium">Nom officiel</label><input type="text" wire:model="legal_name" class="input">@error('legal_name')<p class="mt-1 text-xs text-rose-600">{{ $message }}</p>@enderror</div>
                <div class="sm:col-span-2"><label class="mb-1 block text-sm font-medium">Adresse</label><input type="text" wire:model="address" class="input"></div>
                <div><label class="mb-1 block text-sm font-medium">Ville (lieu de la date)</label><input type="text" wire:model="city" class="input" placeholder="Bamako"></div>
                <div><label class="mb-1 block text-sm font-medium">Téléphone</label><input type="text" wire:model="phone" class="input"></div>
                <div><label class="mb-1 block text-sm font-medium">E-mail de contact</label><input type="email" wire:model="email" class="input">@error('email')<p class="mt-1 text-xs text-rose-600">{{ $message }}</p>@enderror</div>
                <div><label class="mb-1 block text-sm font-medium">Site web</label><input type="text" wire:model="website" class="input"></div>
                <div><label class="mb-1 block text-sm font-medium">RCCM</label><input type="text" wire:model="rccm" class="input"></div>
                <div><label class="mb-1 block text-sm font-medium">NIF</label><input type="text" wire:model="nif" class="input"></div>
                <div class="sm:col-span-2"><label class="mb-1 block text-sm font-medium">Coordonnées de règlement</label><textarea rows="3" wire:model="bank_details" class="input" placeholder="Ex. Orange Money : 00 00 00 00 · Banque : … · IBAN : …"></textarea><p class="mt-1 text-xs text-ink-muted">Affichées sur les factures et dans les rappels d’échéance.</p></div>
                <div class="sm:col-span-2"><label class="mb-1 block text-sm font-medium">Mention fiscale</label><textarea rows="2" wire:model="tax_note" class="input" placeholder="Ex. mention de TVA ou d’exonération, à valider avec votre comptable"></textarea></div>
                <div class="sm:col-span-2"><label class="mb-1 block text-sm font-medium">Pied de page</label><input type="text" wire:model="footer_text" class="input"></div>
                <div class="sm:col-span-2">
                    <label class="mb-1 block text-sm font-medium">Logo (PNG ou JPG, 1 Mo max)</label>
                    <input type="file" wire:model="logo" accept=".png,.jpg,.jpeg" class="block w-full text-sm">
                    @error('logo')<p class="mt-1 text-xs text-rose-600">{{ $message }}</p>@enderror
                    @if ($profile->logo_path)<label class="mt-1 flex items-center gap-2 text-xs"><input type="checkbox" wire:model="remove_logo"> Retirer le logo actuel</label>@endif
                </div>
            </div>
            <div class="flex justify-end"><button type="submit" wire:loading.attr="disabled" wire:target="save,logo" class="rounded-xl bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-500 disabled:opacity-60">Enregistrer</button></div>
        </form>

        <section class="space-y-4 rounded-2xl border border-line bg-surface p-5 shadow-sm">
            <h3 class="text-sm font-semibold">Test d’envoi d’e-mail</h3>
            <p class="text-xs text-ink-muted">Vérifie que l’envoi fonctionne avant le premier vrai rappel. Mode d’envoi du serveur : <strong>{{ $mailer }}</strong>@if ($mailer === 'log') (les e-mails sont écrits dans le fichier de log, pas envoyés)@endif.</p>
            <input type="email" wire:model="test_to" class="input" placeholder="Adresse de test">
            <button type="button" wire:click="sendTest" wire:loading.attr="disabled" wire:target="sendTest" class="w-full rounded-xl border border-line px-4 py-2 text-sm hover:bg-surface-muted disabled:opacity-60">Envoyer un e-mail de test</button>
        </section>
    </div>

    <section class="rou    {{-- Factures émises --}}
    <section class="space-y-3 rounded-2xl border border-line bg-surface p-5 shadow-sm">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div><h3 class="text-sm font-semibold">Factures émises</h3><p class="text-xs text-ink-muted">Cliquez sur une ligne pour voir le détail, la consulter ou la renvoyer.</p></div>
            <input type="search" wire:model.live.debounce.300ms="invoiceSearch" class="input max-w-xs py-1.5 text-sm" placeholder="N° de facture ou entreprise…">
        </div>

        @if ($invoice)
            <div class="space-y-3 rounded-xl border border-indigo-300 bg-surface-muted p-4" wire:key="inv-detail-{{ $invoice->id }}">
                <div class="flex flex-wrap items-start justify-between gap-2">
                    <div><p class="font-mono text-sm font-semibold">{{ $invoice->invoice_number }}</p><p class="text-xs text-ink-muted">Enregistrée le {{ $invoice->created_at->format('d/m/Y H:i') }} par {{ $invoice->recorder?->name ?? '—' }}</p></div>
                    <button type="button" wire:click="closeDetail" class="rounded-lg border border-line px-3 py-1 text-xs hover:bg-surface">Fermer</button>
                </div>
                <dl class="grid gap-3 text-sm sm:grid-cols-2 lg:grid-cols-4">
                    <div><dt class="text-xs text-ink-muted">Entreprise</dt><dd class="font-medium"><a href="{{ route('platform.companies.show', $invoice->company_id) }}" wire:navigate class="hover:underline">{{ $invoice->company?->name }}</a></dd></div>
                    <div><dt class="text-xs text-ink-muted">Formule</dt><dd class="font-medium">{{ $invoice->subscription?->plan?->name }} · {{ strtolower(config('platform.periods.'.$invoice->subscription?->period_months, '')) }}</dd></div>
                    <div><dt class="text-xs text-ink-muted">Période couverte</dt><dd class="font-medium">{{ $invoice->period_start && $invoice->period_end ? $invoice->period_start->format('d/m/Y').' → '.$invoice->period_end->format('d/m/Y') : 'Acompte (aucune période couverte)' }}</dd></div>
                    <div><dt class="text-xs text-ink-muted">Montant payé</dt><dd class="font-semibold">{{ number_format((int) $invoice->amount, 0, ',', ' ') }} FCFA</dd></div>
                    <div><dt class="text-xs text-ink-muted">Date du paiement</dt><dd class="font-medium">{{ $invoice->paid_at->format('d/m/Y') }}</dd></div>
                    <div><dt class="text-xs text-ink-muted">Mode de paiement</dt><dd class="font-medium">{{ config('platform.payment_methods.'.$invoice->method, $invoice->method) }}</dd></div>
                    <div><dt class="text-xs text-ink-muted">Référence</dt><dd class="font-medium">{{ $invoice->reference ?: '—' }}</dd></div>
                    <div><dt class="text-xs text-ink-muted">Tranche</dt><dd class="font-medium">{{ $invoice->tranche ? $invoice->tranche.' sur '.config('platform.max_tranches', 3) : '—' }}</dd></div>
                    <div><dt class="text-xs text-ink-muted">Reste à payer après ce paiement</dt><dd class="font-medium {{ (int) $invoice->balance_after > 0 ? 'text-amber-700' : 'text-emerald-700' }}">{{ (int) $invoice->balance_after > 0 ? number_format((int) $invoice->balance_after, 0, ',', ' ').' FCFA' : 'Soldé' }}</dd></div>
                    <div><dt class="text-xs text-ink-muted">Envoi par e-mail</dt><dd class="font-medium">@if ($invoice->invoice_sent_at)<span class="text-emerald-700">Envoyée le {{ $invoice->invoice_sent_at->format('d/m/Y H:i') }}</span><span class="block text-xs text-ink-muted">à {{ $invoice->invoice_sent_to }}</span>@else<span class="text-amber-700">Non envoyée</span>@endif</dd></div>
                </dl>
                @if ($invoice->note)<p class="text-xs text-ink-muted">Note : {{ $invoice->note }}</p>@endif
                <div class="flex flex-wrap items-center gap-2">
                    <x-document-actions :view="route('platform.invoice', $invoice->id)" :download="route('platform.invoice', [$invoice->id, 'dl' => 1])" ext="pdf" :name="'Facture '.$invoice->invoice_number" />
                    @if ($canResend)<button type="button" wire:click="resendInvoice({{ $invoice->id }})" wire:loading.attr="disabled" wire:target="resendInvoice({{ $invoice->id }})" class="rounded-lg border border-line px-3 py-1.5 text-xs hover:bg-surface disabled:opacity-60">{{ $invoice->invoice_sent_at ? 'Renvoyer par e-mail' : 'Envoyer par e-mail' }}</button>@endif
                </div>
            </div>
        @endif

        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead class="text-xs text-ink-muted"><tr class="border-b border-line"><th class="py-2 pr-3 font-medium">Facture</th><th class="py-2 pr-3 font-medium">Entreprise</th><th class="py-2 pr-3 font-medium">Date</th><th class="py-2 pr-3 text-right font-medium">Montant</th><th class="py-2 pr-3 font-medium">E-mail</th><th class="w-6"></th></tr></thead>
                <tbody class="divide-y divide-line">
                    @forelse ($invoices as $i)
                        <tr wire:key="iv-{{ $i->id }}" wire:click="openInvoice({{ $i->id }})" class="cursor-pointer transition hover:bg-surface-muted {{ $invoiceId === $i->id ? 'bg-surface-muted' : '' }}">
                            <td class="py-2 pr-3 font-mono text-xs">{{ $i->invoice_number }}</td>
                            <td class="py-2 pr-3">{{ $i->company?->name }}</td>
                            <td class="py-2 pr-3 text-xs">{{ $i->paid_at->format('d/m/Y') }}</td>
                            <td class="py-2 pr-3 text-right">{{ number_format((int) $i->amount, 0, ',', ' ') }} FCFA</td>
                            <td class="py-2 pr-3">@if ($i->invoice_sent_at)<span class="rounded-full bg-emerald-100 px-2 py-0.5 text-xs text-emerald-800">Envoyée</span>@else<span class="rounded-full bg-amber-100 px-2 py-0.5 text-xs text-amber-900">Non envoyée</span>@endif</td>
                            <td class="text-ink-muted">›</td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="py-8 text-center text-ink-muted">Aucune facture.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>

    {{-- Journal des e-mails --}}
    <section class="space-y-3 rounded-2xl border border-line bg-surface p-5 shadow-sm">
        <div><h3 class="text-sm font-semibold">E-mails envoyés</h3><p class="text-xs text-ink-muted">Cliquez sur une ligne pour voir le détail (destinataires, objet, erreur complète, facture liée).</p></div>

        @if ($log)
            <div class="space-y-3 rounded-xl border border-indigo-300 bg-surface-muted p-4" wire:key="log-detail-{{ $log->id }}">
                <div class="flex flex-wrap items-start justify-between gap-2">
                    <div><p class="text-sm font-semibold">{{ $log->typeLabel() }}</p><p class="text-xs text-ink-muted">{{ $log->created_at->format('d/m/Y à H:i') }} · {{ $log->triggered_by ? 'envoyé par '.$log->triggeredBy?->name : 'envoi automatique' }}</p></div>
                    <div class="flex items-center gap-2"><span class="rounded-full px-2 py-0.5 text-xs {{ $log->status === 'sent' ? 'bg-emerald-100 text-emerald-800' : 'bg-rose-100 text-rose-800' }}">{{ $log->status === 'sent' ? 'Envoyé' : 'Échec' }}</span><button type="button" wire:click="closeDetail" class="rounded-lg border border-line px-3 py-1 text-xs hover:bg-surface">Fermer</button></div>
                </div>
                <dl class="grid gap-3 text-sm sm:grid-cols-2">
                    <div><dt class="text-xs text-ink-muted">Destinataires</dt><dd class="font-medium break-all">{{ $log->recipients ?: '—' }}</dd></div>
                    <div><dt class="text-xs text-ink-muted">Objet</dt><dd class="font-medium">{{ $log->subject }}</dd></div>
                    <div><dt class="text-xs text-ink-muted">Entreprise</dt><dd class="font-medium">@if ($logCompany)<a href="{{ route('platform.companies.show', $logCompany->id) }}" wire:navigate class="hover:underline">{{ $logCompany->name }}</a>@else — @endif</dd></div>
                    <div><dt class="text-xs text-ink-muted">Échéance concernée</dt><dd class="font-medium">{{ $log->period_end?->format('d/m/Y') ?? '—' }}</dd></div>
                </dl>
                @if ($log->status === 'failed' && $log->error)<div class="rounded-lg bg-rose-50 px-3 py-2 text-xs text-rose-800"><p class="font-semibold">Raison de l’échec</p><p class="mt-1 break-words">{{ $log->error }}</p></div>@endif
                @if ($logPayment)
                    <div class="flex flex-wrap items-center gap-2 border-t border-line pt-3 text-sm">
                        <span>Facture <span class="font-mono text-xs">{{ $logPayment->invoice_number }}</span> · {{ number_format((int) $logPayment->amount, 0, ',', ' ') }} FCFA</span>
                        <x-document-actions :view="route('platform.invoice', $logPayment->id)" :download="route('platform.invoice', [$logPayment->id, 'dl' => 1])" ext="pdf" :name="'Facture '.$logPayment->invoice_number" />
                        @if ($canResend)<button type="button" wire:click="resendInvoice({{ $logPayment->id }})" wire:loading.attr="disabled" wire:target="resendInvoice({{ $logPayment->id }})" class="rounded-lg border border-line px-3 py-1.5 text-xs hover:bg-surface disabled:opacity-60">Renvoyer par e-mail</button>@endif
                    </div>
                @endif
            </div>
        @endif

        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead class="text-xs text-ink-muted"><tr class="border-b border-line"><th class="py-2 pr-3 font-medium">Date</th><th class="py-2 pr-3 font-medium">Type</th><th class="py-2 pr-3 font-medium">Destinataires</th><th class="py-2 pr-3 font-medium">État</th><th class="w-6"></th></tr></thead>
                <tbody class="divide-y divide-line">
                    @forelse ($logs as $l)
                        <tr wire:key="ml-{{ $l->id }}" wire:click="openLog({{ $l->id }})" class="cursor-pointer transition hover:bg-surface-muted {{ $logId === $l->id ? 'bg-surface-muted' : '' }}">
                            <td class="whitespace-nowrap py-2 pr-3 text-xs">{{ $l->created_at->format('d/m/Y H:i') }}</td>
                            <td class="py-2 pr-3">{{ $l->typeLabel() }}<span class="block text-xs text-ink-muted">{{ $l->triggered_by ? 'par '.$l->triggeredBy?->name : 'envoi automatique' }}</span></td>
                            <td class="py-2 pr-3 text-xs">{{ $l->recipients ?: '—' }}</td>
                            <td class="py-2 pr-3">@if ($l->status === 'sent')<span class="rounded-full bg-emerald-100 px-2 py-0.5 text-xs text-emerald-800">Envoyé</span>@else<span class="rounded-full bg-rose-100 px-2 py-0.5 text-xs text-rose-800">Échec</span>@endif</td>
                            <td class="text-ink-muted">›</td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="py-8 text-center text-ink-muted">Aucun e-mail envoyé pour le moment.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>
