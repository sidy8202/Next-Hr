@php
    $fmt = fn ($v) => number_format((int) $v, 0, ',', ' ').' '.$currency;
    $methods = config('platform.payment_methods');
@endphp
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <title>Facture {{ $payment->invoice_number }}</title>
    @include('pdf.letter-styles')
</head>
<body>
<div class="letter">
    <x-letterhead-header :letterhead="$profile" />

    <table class="meta">
        <tr>
            <td>Facture n° <strong>{{ $payment->invoice_number }}</strong></td>
            <td class="right">{{ $profile->city ? $profile->city.', le ' : 'Le ' }}{{ $payment->paid_at->translatedFormat('d F Y') }}</td>
        </tr>
    </table>

    <div class="banner">Facture acquittée</div>

    <div class="to">
        <div class="label">Facturé à</div>
        <div style="font-weight: bold;">{{ $company?->name }}</div>
        @if ($company?->email)<div>{{ $company->email }}</div>@endif
        @if ($company?->phone)<div>{{ $company->phone }}</div>@endif
        @if ($company?->country)<div>{{ config('platform.countries.'.$company->country, $company->country) }}</div>@endif
    </div>

    <table style="width: 100%; border-collapse: collapse; margin-top: 14px; font-size: 11.5px;">
        <thead>
            <tr style="background: #f3f4f6;">
                <th style="border: 1px solid #d1d5db; padding: 6px; text-align: left;">Description</th>
                <th style="border: 1px solid #d1d5db; padding: 6px; text-align: left;">Période couverte</th>
                <th style="border: 1px solid #d1d5db; padding: 6px; text-align: right;">Montant</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td style="border: 1px solid #d1d5db; padding: 8px;">
                    @if ($payment->period_start)
                        Abonnement Next HR {{ $plan?->name }} ({{ strtolower(config('platform.periods.'.$sub?->period_months, '')) }})
                    @else
                        Acompte (tranche {{ $payment->tranche }} sur {{ config('platform.max_tranches', 3) }}) sur l’abonnement Next HR {{ $plan?->name }}
                    @endif
                </td>
                <td style="border: 1px solid #d1d5db; padding: 8px;">
                    @if ($payment->period_start && $payment->period_end)Du {{ $payment->period_start->format('d/m/Y') }} au {{ $payment->period_end->format('d/m/Y') }}@else — @endif
                </td>
                <td style="border: 1px solid #d1d5db; padding: 8px; text-align: right;">{{ $fmt($payment->amount) }}</td>
            </tr>
        </tbody>
        <tfoot>
            <tr>
                <td colspan="2" style="padding: 8px; text-align: right;"><strong>Total payé</strong></td>
                <td style="padding: 8px; text-align: right;"><strong>{{ $fmt($payment->amount) }}</strong></td>
            </tr>
        </tfoot>
    </table>

    @if ((int) $payment->balance_after > 0)
        <p style="margin-top: 12px;"><strong>Reste à payer sur cet abonnement : {{ $fmt($payment->balance_after) }}</strong> (montant de la période : {{ $fmt($sub?->price) }}).</p>
    @elseif ($payment->tranche > 1)
        <p style="margin-top: 12px;">Cet abonnement est intégralement réglé : merci.</p>
    @endif

    <p style="margin-top: 14px;">Payée le <strong>{{ $payment->paid_at->format('d/m/Y') }}</strong> par {{ strtolower($methods[$payment->method] ?? $payment->method) }}@if ($payment->reference) (référence : {{ $payment->reference }})@endif. Nous vous remercions de votre confiance.</p>

    @if ($profile->tax_note)<p style="font-size: 10.5px; color: #6b7280;">{{ $profile->tax_note }}</p>@endif

    @if ($profile->bank_details)
        <div style="margin-top: 16px; padding: 8px 10px; border: 1px solid #d1d5db; border-radius: 4px; font-size: 10.5px;">
            <strong>Pour vos prochains règlements :</strong><br>{!! nl2br(e($profile->bank_details)) !!}
        </div>
    @endif

    <div class="footer">
        {{ $profile->footer_text }}
        @if ($profile->rccm || $profile->nif)<div>{{ collect([$profile->rccm ? 'RCCM : '.$profile->rccm : null, $profile->nif ? 'NIF : '.$profile->nif : null])->filter()->implode(' · ') }}</div>@endif
    </div>
</div>
</body>
</html>
