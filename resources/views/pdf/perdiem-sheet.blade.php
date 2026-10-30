@php
    $fcfa = fn ($v) => $v === null ? '—' : number_format((int) $v, 0, ',', ' ').' FCFA';
    $methods = config('missions.payment_methods');
    $e = $participant->employee;
@endphp
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <title>Fiche de perdiem {{ $mission->reference }}</title>
    @include('pdf.letter-styles')
</head>
<body>
<div class="letter">
    <x-letterhead-header :letterhead="$letterhead" />

    <table class="meta">
        <tr>
            <td>Mission : <strong>{{ $mission->reference }}</strong></td>
            <td class="right">{{ $letterhead->city ? $letterhead->city.', le ' : 'Le ' }}{{ now()->translatedFormat('d F Y') }}</td>
        </tr>
    </table>

    <div class="banner">Fiche de perdiem : document financier confidentiel, à ne pas joindre à l’ordre de mission</div>

    <table class="details" style="margin: 10px 0 14px;">
        <tr><td class="k">Bénéficiaire</td><td>{{ $e->full_name }} (matricule {{ $e->matricule }})</td></tr>
        <tr><td class="k">Poste / département</td><td>{{ $e->position?->title }} · {{ $participant->department?->name }}</td></tr>
        <tr><td class="k">Mission</td><td>{{ $mission->purpose }} : {{ $mission->destination_zone }}</td></tr>
        <tr><td class="k">Période</td><td>Du {{ $mission->start_date->format('d/m/Y') }} au {{ $mission->end_date->format('d/m/Y') }} ({{ $participant->days }} jour(s) indemnisable(s))</td></tr>
        <tr><td class="k">Perdiem journalier</td><td>{{ $fcfa($participant->daily_rate) }}</td></tr>
        <tr><td class="k">Perdiem total</td><td><strong>{{ $fcfa($participant->total_amount) }}</strong></td></tr>
        <tr><td class="k">Modalité</td><td>{{ config('missions.payment_modes.'.$participant->payment_mode) }}</td></tr>
    </table>

    <table style="width: 100%; border-collapse: collapse; font-size: 11px;">
        <thead>
            <tr style="background: #f3f4f6;">
                <th style="border: 1px solid #d1d5db; padding: 5px; text-align: left;">Tranche</th>
                <th style="border: 1px solid #d1d5db; padding: 5px; text-align: right;">Prévu</th>
                <th style="border: 1px solid #d1d5db; padding: 5px; text-align: left;">Date prévue</th>
                <th style="border: 1px solid #d1d5db; padding: 5px; text-align: right;">Payé</th>
                <th style="border: 1px solid #d1d5db; padding: 5px; text-align: left;">Date / mode / référence</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($payments as $p)
                <tr>
                    <td style="border: 1px solid #d1d5db; padding: 5px;">{{ $p->label }}</td>
                    <td style="border: 1px solid #d1d5db; padding: 5px; text-align: right;">{{ $fcfa($p->planned_amount) }}</td>
                    <td style="border: 1px solid #d1d5db; padding: 5px;">{{ $p->planned_date?->format('d/m/Y') ?? '—' }}</td>
                    <td style="border: 1px solid #d1d5db; padding: 5px; text-align: right;">{{ $p->status === 'paid' ? $fcfa($p->paid_amount) : '—' }}</td>
                    <td style="border: 1px solid #d1d5db; padding: 5px;">@if ($p->status === 'paid'){{ $p->paid_at?->format('d/m/Y') }} · {{ $methods[$p->method] ?? $p->method }}@if ($p->reference) · {{ $p->reference }}@endif @else À payer @endif</td>
                </tr>
            @empty
                <tr><td colspan="5" style="border: 1px solid #d1d5db; padding: 8px; text-align: center;">Aucun échéancier défini.</td></tr>
            @endforelse
        </tbody>
        <tfoot>
            <tr>
                <td style="padding: 5px;"><strong>Total payé</strong></td>
                <td></td><td></td>
                <td style="padding: 5px; text-align: right;"><strong>{{ $fcfa($summary['paid']) }}</strong></td>
                <td style="padding: 5px;">Reste à payer : <strong>{{ $fcfa($summary['remaining']) }}</strong></td>
            </tr>
        </tfoot>
    </table>

    <table style="width: 100%; margin-top: 30px; border-collapse: collapse;">
        <tr>
            <td style="width: 50%; text-align: center; vertical-align: top; padding: 0 12px;">
                <div style="font-weight: bold;">Le bénéficiaire</div>
                <div style="font-size: 10px;">Reçu la somme de ______________ FCFA le ____ / ____ / ________</div>
                <div style="height: 60px;"></div>
                <div>{{ $e->full_name }}</div>
            </td>
            <td style="width: 50%; text-align: center; vertical-align: top; padding: 0 12px;">
                <div style="font-weight: bold;">La Finance</div>
                <div style="height: 76px;"></div>
                <div>Nom et signature</div>
            </td>
        </tr>
    </table>

    <div class="footer">
        {{ $letterhead->footer_text }}
        @if ($letterhead->rccm || $letterhead->nif)<div>{{ collect([$letterhead->rccm ? 'RCCM : '.$letterhead->rccm : null, $letterhead->nif ? 'NIF : '.$letterhead->nif : null])->filter()->implode(' · ') }}</div>@endif
    </div>
</div>
</body>
</html>
