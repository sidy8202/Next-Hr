<!DOCTYPE html>
<html lang="fr">
<body style="font-family: Arial, Helvetica, sans-serif; color: #1f2937; line-height: 1.55;">
    <p>Bonjour,</p>

    @if ($type === 'expired')
        <p>L’abonnement <strong>{{ $plan?->name }}</strong> de <strong>{{ $company->name }}</strong> a expiré le {{ $end?->format('d/m/Y') }}. Certaines fonctions de Next HR sont désormais bloquées. Vos données sont conservées : elles seront de nouveau accessibles dès le renouvellement.</p>
    @elseif ($type === 'past_due')
        <p>L’échéance de l’abonnement <strong>{{ $plan?->name }}</strong> de <strong>{{ $company->name }}</strong> est dépassée depuis le {{ $end?->format('d/m/Y') }}. Merci de régulariser le paiement ({{ $price }}) pour éviter une interruption du service.</p>
    @elseif (str_starts_with($type, 'trial_'))
        <p>La période d’essai de <strong>{{ $company->name }}</strong> se termine {{ $days === 0 ? 'aujourd’hui' : ($days === 1 ? 'demain' : 'dans '.$days.' jours') }}, le {{ $end?->format('d/m/Y') }}. Pour continuer sans interruption, souscrivez à la formule {{ $plan?->name }} ({{ $price }}).</p>
    @else
        <p>L’abonnement <strong>{{ $plan?->name }}</strong> de <strong>{{ $company->name }}</strong> arrive à échéance {{ $days === 0 ? 'aujourd’hui' : ($days === 1 ? 'demain' : 'dans '.$days.' jours') }}, le {{ $end?->format('d/m/Y') }}. Pour le renouveler : {{ $price }} ({{ strtolower(config('platform.periods.'.$sub->period_months, '')) }}).</p>
    @endif

    @if ($profile->bank_details)
        <p style="padding: 10px 12px; background: #f3f4f6; border-radius: 6px;"><strong>Pour régler :</strong><br>{!! nl2br(e($profile->bank_details)) !!}</p>
    @endif

    <p>Pour toute question, répondez à ce message ou contactez-nous@if ($profile->phone) au {{ $profile->phone }}@endif.</p>
    <p>Cordialement,<br>{{ $profile->legal_name }}@if ($profile->email)<br>{{ $profile->email }}@endif</p>
</body>
</html>
