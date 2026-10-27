<!DOCTYPE html>
<html lang="fr">
<body style="font-family: Arial, Helvetica, sans-serif; color: #1f2937; line-height: 1.5;">
    <p>Bonjour,</p>
    @if ($kind === 'request')
        <p>Une demande d’explication (réf. <strong>{{ $case->reference }}</strong>) a été adressée à <strong>{{ $employee->full_name }}</strong> (matricule {{ $employee->matricule }}).
        L’employé dispose d’un délai de {{ $case->response_days }} jour(s) ouvré(s), jusqu’au {{ $case->response_deadline->translatedFormat('l d F Y') }}, pour répondre.</p>
    @elseif ($kind === 'response')
        <p><strong>{{ $employee->full_name }}</strong> (matricule {{ $employee->matricule }}) a répondu à la demande d’explication réf. <strong>{{ $case->reference }}</strong>.</p>
    @else
        <p>Une décision a été rendue dans le dossier réf. <strong>{{ $case->reference }}</strong> concernant <strong>{{ $employee->full_name }}</strong> (matricule {{ $employee->matricule }}) : <strong>{{ $case->decisionLabel() }}</strong>.</p>
    @endif
    <p>La lettre est jointe à ce message. Ce document est confidentiel.</p>
    <p>Cordialement,<br>{{ $company }}</p>
</body>
</html>
