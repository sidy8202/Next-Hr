<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <title>Fiche de permission</title>
    <style>
        body { font-family: DejaVu Sans, Arial, sans-serif; font-size: 12px; color: #1f2937; margin: 28px; }
        h1 { font-size: 20px; margin: 0 0 4px; }
        .muted { color: #6b7280; }
        table { width: 100%; border-collapse: collapse; margin-top: 14px; }
        td, th { border: 1px solid #d1d5db; padding: 7px 9px; text-align: left; vertical-align: top; }
        th { background: #f3f4f6; width: 34%; }
        .box { border: 1px solid #c7d2fe; background: #eef2ff; padding: 12px 14px; margin-top: 18px; }
    </style>
</head>
<body>
    <h1>Fiche de permission</h1>
    <p class="muted">{{ $company }} · émise le {{ now()->format('d/m/Y') }}</p>

    <table>
        <tr><th>Employé</th><td>{{ $employee->full_name }} (matricule {{ $employee->matricule }})</td></tr>
        <tr><th>Département / poste</th><td>{{ $employee->department?->name }} / {{ $employee->position?->title }}</td></tr>
        <tr><th>Type de permission</th><td>{{ $request->type?->name }}</td></tr>
        @if ($request->isHours())
            <tr><th>Date et horaires</th><td>{{ $request->start_date->translatedFormat('l d/m/Y') }}, de {{ substr((string) $request->start_time, 0, 5) }} à {{ substr((string) $request->end_time, 0, 5) }}</td></tr>
        @else
            <tr><th>Date de début</th><td>{{ $request->start_date->translatedFormat('l d/m/Y') }}</td></tr>
            <tr><th>Date de fin</th><td>{{ $request->end_date->translatedFormat('l d/m/Y') }}</td></tr>
            @if ($resume)<tr><th>Reprise du travail</th><td>{{ $resume->translatedFormat('l d/m/Y') }}</td></tr>@endif
        @endif
        <tr><th>Durée</th><td>{{ rtrim(rtrim(number_format($request->days, 2, ',', ''), '0'), ',') }} jour(s) @if ($request->hours)({{ rtrim(rtrim(number_format($request->hours, 2, ',', ''), '0'), ',') }} h)@endif</td></tr>
        @if ($request->deducted_days > 0)<tr><th>Retiré du congé annuel</th><td>{{ rtrim(rtrim(number_format($request->deducted_days, 2, ',', ''), '0'), ',') }} jour(s)</td></tr>@endif
        @if ($request->unpaid_days > 0)<tr><th>Jours non payés</th><td>{{ rtrim(rtrim(number_format($request->unpaid_days, 2, ',', ''), '0'), ',') }} jour(s)</td></tr>@endif
    </table>

    <table>
        <tr><th>Validation</th><th>Par</th><th>Le</th></tr>
        @foreach ($steps as $step)
            <tr><td>{{ \App\Models\ApprovalStep::ACTED_AS[$step->acted_as] ?? $step->kindLabel() }}</td><td>{{ $step->decider?->name }}</td><td>{{ $step->decided_at?->format('d/m/Y') }}</td></tr>
        @endforeach
    </table>

    <div class="box">{{ $message }}</div>
</body>
</html>
