<!DOCTYPE html>
<html lang="fr">
<body style="font-family: Arial, Helvetica, sans-serif; color: #1f2937; line-height: 1.55;">
    <p>Bonjour,</p>
    <p>Nous avons bien reçu le paiement de <strong>{{ number_format((int) $payment->amount, 0, ',', ' ') }} {{ in_array($company->currency, ['XOF', 'XAF'], true) ? 'FCFA' : $company->currency }}</strong>
       pour l’abonnement de <strong>{{ $company->name }}</strong> le {{ $payment->paid_at->format('d/m/Y') }}. Merci de votre confiance.</p>
    @if ($payment->period_end)
        <p>Votre abonnement est soldé et actif jusqu’au <strong>{{ $payment->period_end->format('d/m/Y') }}</strong>.</p>
    @elseif ((int) $payment->balance_after > 0)
        <p>Il reste <strong>{{ number_format((int) $payment->balance_after, 0, ',', ' ') }} {{ in_array($company->currency, ['XOF', 'XAF'], true) ? 'FCFA' : $company->currency }}</strong> à régler pour solder cet abonnement (tranche {{ $payment->tranche }} sur {{ config('platform.max_tranches', 3) }}).</p>
    @endif
    <p>Votre facture <strong>{{ $payment->invoice_number }}</strong> est jointe à ce message.</p>
    <p>Cordialement,<br>{{ $profile->legal_name }}@if ($profile->phone)<br>{{ $profile->phone }}@endif @if ($profile->email)<br>{{ $profile->email }}@endif</p>
</body>
</html>
