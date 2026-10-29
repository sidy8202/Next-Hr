<!DOCTYPE html>
<html lang="fr">
<body style="font-family: Arial, Helvetica, sans-serif; color: #1f2937; line-height: 1.5;">
    <p>Bonjour,</p>
    @if ($forSelf)
        <p>Votre ordre de mission signé (réf. <strong>{{ $document->reference }}</strong>) est disponible : {{ $mission->destination_zone }}, du {{ $mission->start_date->format('d/m/Y') }} au {{ $mission->end_date->format('d/m/Y') }}.</p>
        <p>Conservez-le avec vous pendant tout le déplacement.</p>
    @else
        <p>L’ordre de mission signé de <strong>{{ $participantName }}</strong> (réf. <strong>{{ $document->reference }}</strong>) est disponible : {{ $mission->destination_zone }}, du {{ $mission->start_date->format('d/m/Y') }} au {{ $mission->end_date->format('d/m/Y') }}.</p>
    @endif
    <p>Le document est joint à ce message. Il est confidentiel.</p>
    <p>Cordialement,<br>{{ $company }}</p>
</body>
</html>
