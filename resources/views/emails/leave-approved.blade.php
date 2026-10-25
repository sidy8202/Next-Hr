<!DOCTYPE html>
<html lang="fr">
<body style="font-family: Arial, Helvetica, sans-serif; color: #1f2937; line-height: 1.5;">
    <p>Bonjour {{ $name }},</p>
    <p>Votre demande de congé a été validée par votre hiérarchie et par les ressources humaines.</p>
    <table cellpadding="4" style="border-collapse: collapse;">
        <tr><td><strong>Type</strong></td><td>{{ $leave->type?->name }}</td></tr>
        <tr><td><strong>Début</strong></td><td>{{ $leave->start_date->translatedFormat('l d/m/Y') }}</td></tr>
        <tr><td><strong>Fin</strong></td><td>{{ $leave->end_date->translatedFormat('l d/m/Y') }}</td></tr>
        <tr><td><strong>Reprise du travail</strong></td><td>{{ $resume->translatedFormat('l d/m/Y') }}</td></tr>
        <tr><td><strong>Durée</strong></td><td>{{ rtrim(rtrim(number_format($leave->days, 1, ',', ''), '0'), ',') }} jour(s)</td></tr>
    </table>
    <p>Votre fiche de congé est jointe à ce message et disponible dans l’application.</p>
    <p>Nous vous souhaitons une bonne jouissance de votre congé : profitez-en pleinement et reposez-vous bien !</p>
    <p>Cordialement,<br>{{ $company }}</p>
</body>
</html>
