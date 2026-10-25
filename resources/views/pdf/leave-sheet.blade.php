<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <title>Fiche de congé</title>
    <style>
        body { font-family: DejaVu Sans, Arial, sans-serif; font-size: 12px; color: #1f2937; margin: 28px; }
        h1 { font-size: 20px; margin: 0 0 4px; }
        .muted { color: #6b7280; }
        table { width: 100%; border-collapse: collapse; margin-top: 14px; }
        td, th { border: 1px solid #d1d5db; padding: 7px 9px; text-align: left; vertical-align: top; }
        th { background: #f3f4f6; width: 34%; }
        .box { border: 1px solid #c7d2fe; background: #eef2ff; padding: 12px 14px; margin-top: 18px; }
        .sign { margin-top: 26px; }
        @media print { body { margin: 12mm; } }
    </style>
</head>
<body>
    <h1>Fiche de congé</h1>
    <p class="muted">{{ $company }} · émise le {{ now()->format('d/m/Y') }}</p>

    <table>
        <tr><th>Employé</th><td>{{ $employee->full_name }} (matricule {{ $employee->matricule }})</td></tr>
        <tr><th>Département / poste</th><td>{{ $employee->department?->name }} / {{ $employee->position?->title }}</td></tr>
        <tr><th>Type de congé</th><td>{{ $leave->type?->name }}</td></tr>
        <tr><th>Date de début</th><td>{{ $leave->start_date->translatedFormat('l d/m/Y') }}@if ($leave->start_half) (à partir de l’après-midi)@endif</td></tr>
        <tr><th>Date de fin</th><td>{{ $leave->end_date->translatedFormat('l d/m/Y') }}@if ($leave->end_half) (jusqu’à midi)@endif</td></tr>
        <tr><th>Reprise du travail</th><td>{{ $resume->translatedFormat('l d/m/Y') }}</td></tr>
        <tr><th>Durée</th><td>{{ rtrim(rtrim(number_format($leave->days, 1, ',', ''), '0'), ',') }} jour(s)</td></tr>
        @if ($leave->balance_after !== null)
            <tr><th>Solde après ce congé</th><td>{{ rtrim(rtrim(number_format($leave->balance_after, 2, ',', ''), '0'), ',') ?: '0' }} jour(s)</td></tr>
        @endif
        @if ($leave->interim)
            <tr><th>Intérim pendant l’absence</th><td>{{ $leave->interim->full_name }}</td></tr>
        @endif
    </table>

    <table>
        <tr><th>Validation</th><th>Par</th><th>Le</th></tr>
        @foreach ($steps as $step)
            <tr>
                <td>{{ \App\Models\ApprovalStep::ACTED_AS[$step->acted_as] ?? $step->kindLabel() }}</td>
                <td>{{ $step->decider?->name }}</td>
                <td>{{ $step->decided_at?->format('d/m/Y') }}</td>
            </tr>
        @endforeach
    </table>

    <div class="box">
        Nous vous souhaitons une bonne jouissance de votre congé. Profitez-en pleinement, reposez-vous bien
        et revenez en pleine forme le {{ $resume->translatedFormat('l d/m/Y') }}.
    </div>

    <p class="sign muted">Document généré par l’application RH — {{ $company }}.</p>
</body>
</html>
