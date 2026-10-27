<!DOCTYPE html>
<html lang="fr">
<body style="font-family: Arial, Helvetica, sans-serif; color: #1f2937; line-height: 1.5;">
    <p>Bonjour {{ $name }},</p>
    <p>Votre demande de permission a été validée par votre hiérarchie et par les ressources humaines.</p>
    <table cellpadding="4" style="border-collapse: collapse;">
        <tr><td><strong>Type</strong></td><td>{{ $request->type?->name }}</td></tr>
        @if ($request->isHours())
            <tr><td><strong>Date</strong></td><td>{{ $request->start_date->translatedFormat('l d/m/Y') }}, de {{ substr((string) $request->start_time, 0, 5) }} à {{ substr((string) $request->end_time, 0, 5) }}</td></tr>
        @else
            <tr><td><strong>Début</strong></td><td>{{ $request->start_date->translatedFormat('l d/m/Y') }}</td></tr>
            <tr><td><strong>Fin</strong></td><td>{{ $request->end_date->translatedFormat('l d/m/Y') }}</td></tr>
            @if ($resume)<tr><td><strong>Reprise du travail</strong></td><td>{{ $resume->translatedFormat('l d/m/Y') }}</td></tr>@endif
        @endif
        @if ($request->deducted_days > 0)<tr><td><strong>Retiré de votre congé</strong></td><td>{{ rtrim(rtrim(number_format($request->deducted_days, 2, ',', ''), '0'), ',') }} jour(s)</td></tr>@endif
    </table>
    <p>{{ $messageText }}</p>
    <p>Votre fiche de permission est jointe à ce message.</p>
    <p>Cordialement,<br>{{ $company }}</p>
</body>
</html>
