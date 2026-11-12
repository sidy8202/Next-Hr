<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <title>Bulletin de paie {{ $periodLabel }} : {{ $payslip->employee_name }}</title>
    <style>
        @@page { margin: 22px 28px 54px 28px; }
        * { box-sizing: border-box; }
        body { font-family: DejaVu Sans, sans-serif; font-size: 9px; color: #1f2937; line-height: 1.35; margin: 0; }
        table { border-collapse: collapse; width: 100%; }
        .num { text-align: right; white-space: nowrap; }
        .muted { color: #6b7280; }
        .small { font-size: 7.5px; }

        /* En-tête */
        .head td { vertical-align: top; }
        .head .logo { width: 24%; }
        .head .company { width: 40%; font-size: 8.5px; color: #374151; }
        .head .company .name { font-size: 12px; font-weight: bold; color: #111827; margin-bottom: 2px; }
        .head .title { width: 36%; text-align: right; }
        .head .title .doc { font-size: 17px; font-weight: bold; color: {{ $accent }}; letter-spacing: 1px; }
        .head .title .period { font-size: 12px; font-weight: bold; color: #111827; margin-top: 2px; }
        .head .title .ref { font-size: 7.5px; color: #6b7280; margin-top: 3px; }
        .bar { height: 3px; background: {{ $accent }}; margin: 8px 0 10px; }
        .draft { background: #fef3c7; border: 1px solid #f59e0b; color: #92400e; font-weight: bold; text-align: center; padding: 5px; margin-bottom: 8px; font-size: 8.5px; }

        /* Cartes d'information */
        .cards td { vertical-align: top; }
        .cards td.col { width: 49%; }
        .cards td.gap { width: 2%; }
        .card { border: 1px solid #d1d5db; border-radius: 4px; }
        .card .cap { background: #f3f4f6; border-bottom: 1px solid #d1d5db; padding: 4px 8px; font-size: 8px; font-weight: bold; text-transform: uppercase; letter-spacing: .6px; color: #374151; }
        .card table td { padding: 2.5px 8px; font-size: 8.5px; }
        .card table td.k { width: 38%; color: #6b7280; }
        .card table td.v { font-weight: bold; color: #111827; }
        .gap { width: 10px; }

        /* Lignes du bulletin */
        .lines { margin-top: 10px; border: 1px solid #d1d5db; }
        .lines th { background: {{ $accent }}; color: #ffffff; font-size: 8px; text-transform: uppercase; letter-spacing: .4px; padding: 5px 6px; text-align: left; }
        .lines th.num { text-align: right; }
        .lines td { padding: 3.5px 6px; border-top: 1px solid #eef0f3; vertical-align: top; font-size: 8.5px; }
        .lines td.code { color: #6b7280; width: 46px; font-size: 7.5px; }
        .lines tr.section td { background: #ecfdf5; color: #065f46; font-weight: bold; text-transform: uppercase; font-size: 7.5px; letter-spacing: .6px; border-top: 1px solid #a7f3d0; padding: 4px 6px; }
        .lines tr.subtotal td { background: #f9fafb; font-weight: bold; border-top: 1px solid #d1d5db; }
        .lines .note { color: #6b7280; font-size: 7.5px; }
        .neg { color: #b91c1c; }

        /* Récapitulatif et net à payer */
        .sum td { vertical-align: top; }
        .recap { border: 1px solid #d1d5db; }
        .recap td { padding: 3.5px 8px; font-size: 8.5px; border-top: 1px solid #eef0f3; }
        .recap tr:first-child td { border-top: 0; }
        .recap td.v { text-align: right; white-space: nowrap; font-weight: bold; }
        .net { background: {{ $accent }}; color: #ffffff; border-radius: 6px; padding: 12px 14px; text-align: center; }
        .net .lab { font-size: 8px; letter-spacing: 1.2px; text-transform: uppercase; opacity: .9; }
        .net .amt { font-size: 21px; font-weight: bold; margin-top: 3px; }
        .net .cur { font-size: 9px; opacity: .9; }
        .words { margin-top: 6px; font-style: italic; color: #374151; font-size: 8.5px; }

        /* Cumuls et part patronale */
        h3 { font-size: 8.5px; text-transform: uppercase; letter-spacing: .6px; color: {{ $accent }}; margin: 12px 0 4px; }
        .grid { border: 1px solid #d1d5db; }
        .grid th { background: #f3f4f6; font-size: 7.5px; text-transform: uppercase; color: #374151; padding: 4px 6px; text-align: right; border-bottom: 1px solid #d1d5db; }
        .grid td { padding: 4px 6px; text-align: right; font-weight: bold; font-size: 8.5px; }
        .grid td.l, .grid th.l { text-align: left; }

        .legal { margin-top: 12px; font-size: 7.5px; color: #6b7280; border-top: 1px solid #e5e7eb; padding-top: 6px; }
        .footer { position: fixed; bottom: -36px; left: 0; right: 0; font-size: 7px; color: #9ca3af; border-top: 1px solid #e5e7eb; padding-top: 4px; }
        .footer td { font-size: 7px; color: #9ca3af; }
        .pn:after { content: "Page " counter(page) " / " counter(pages); }
    </style>
</head>
<body>

    <div class="footer">
        <table><tr>
            <td>{{ $identity['legal_name'] }} · Bulletin de paie {{ $periodLabel }} · {{ $reference }}</td>
            <td class="num"><span class="pn"></span></td>
        </tr></table>
    </div>

    {{-- En-tête de l'entreprise --}}
    <table class="head">
        <tr>
            <td class="logo">@if ($identity['logo'])<img src="{{ $identity['logo'] }}" alt="" style="max-height: 62px; max-width: 150px;">@endif</td>
            <td class="company">
                <div class="name">{{ $identity['legal_name'] }}</div>
                @if ($identity['address'])<div>{{ $identity['address'] }}@if ($identity['city']), {{ $identity['city'] }}@endif</div>@endif
                @if ($identity['phone'] || $identity['email'])<div>{{ collect([$identity['phone'], $identity['email']])->filter()->implode(' · ') }}</div>@endif
                @if ($identity['website'])<div>{{ $identity['website'] }}</div>@endif
                @if ($identity['rccm'] || $identity['nif'])<div class="muted">{{ collect([$identity['rccm'] ? 'RCCM : ' . $identity['rccm'] : null, $identity['nif'] ? 'NIF : ' . $identity['nif'] : null])->filter()->implode(' · ') }}</div>@endif
            </td>
            <td class="title">
                <div class="doc">BULLETIN DE PAIE</div>
                <div class="period">{{ $periodLabel }}</div>
                <div class="ref">Réf. {{ $reference }}</div>
            </td>
        </tr>
    </table>
    <div class="bar"></div>

    @if ($draft)
        <div class="draft">BULLETIN PROVISOIRE : non définitif, il sera figé à la clôture de la paie</div>
    @endif

    {{-- Salarié et période --}}
    <table class="cards">
        <tr>
            <td class="col">
                <div class="card">
                    <div class="cap">Salarié</div>
                    <table>
                        <tr><td class="k">Nom et prénoms</td><td class="v">{{ $payslip->employee_name }}</td></tr>
                        <tr><td class="k">Matricule</td><td class="v">{{ $payslip->matricule ?: '—' }}</td></tr>
                        <tr><td class="k">Poste</td><td class="v">{{ $meta['position'] ?? $employee?->position?->title ?? '—' }}</td></tr>
                        <tr><td class="k">Département</td><td class="v">{{ $payslip->department_name ?: ($employee?->department?->name ?? '—') }}</td></tr>
                        @if (! empty($meta['grade']))
                            <tr><td class="k">Catégorie</td><td class="v">{{ $meta['grade'] }}{{ ! empty($meta['step']) ? ' · ' . $meta['step'] : '' }}</td></tr>
                        @endif
                        <tr><td class="k">Date d'embauche</td><td class="v">{{ ! empty($meta['hire_date']) ? \Carbon\Carbon::parse($meta['hire_date'])->format('d/m/Y') : '—' }}</td></tr>
                        <tr><td class="k">Ancienneté</td><td class="v">{{ (int) ($meta['seniority_years'] ?? 0) }} an(s)</td></tr>
                        <tr><td class="k">Situation de famille</td><td class="v">{{ ($family['marital_status'] ?? null) ?: '—' }}@if ((int) ($family['children'] ?? 0) > 0), {{ (int) $family['children'] }} enfant(s) à charge @endif</td></tr>
                    </table>
                </div>
            </td>
            <td class="gap"></td>
            <td class="col">
                <div class="card">
                    <div class="cap">Période et paiement</div>
                    <table>
                        <tr><td class="k">Période</td><td class="v">du {{ $start->format('d/m/Y') }} au {{ $end->format('d/m/Y') }}</td></tr>
                        @if ($presence !== null)
                            <tr><td class="k">Présence</td><td class="v">{{ $presence }} jours sur 30 (prorata)</td></tr>
                        @endif
                        <tr><td class="k">Brut du mois</td><td class="v">{{ $money($totals['gross']) }} {{ $currencyLabel }}</td></tr>
                        <tr><td class="k">Net à payer</td><td class="v">{{ $money($totals['net_to_pay']) }} {{ $currencyLabel }}</td></tr>
                        @if ($template['convention'])
                            <tr><td class="k">Convention</td><td class="v">{{ $template['convention'] }}</td></tr>
                        @endif
                        <tr><td class="k">Édité le</td><td class="v">{{ $issuedAt->format('d/m/Y') }}</td></tr>
                    </table>
                </div>
            </td>
        </tr>
    </table>

    {{-- Lignes du bulletin --}}
    <table class="lines">
        <thead>
            <tr>
                @if ($template['show_item_codes'])<th>Code</th>@endif
                <th>Désignation</th>
                <th class="num">Base</th>
                <th class="num">Taux</th>
                <th class="num">Gains</th>
                <th class="num">Retenues</th>
            </tr>
        </thead>
        <tbody>
            @if ($template['group_lines'])<tr class="section"><td colspan="{{ $cols }}">Rémunération</td></tr>@endif

            @foreach ($earnings as $l)
                <tr>
                    @if ($template['show_item_codes'])<td class="code">{{ $l['code'] }}</td>@endif
                    <td>{{ $l['label'] }}@if (! empty($l['note']))<div class="note">{{ $l['note'] }}</div>@endif</td>
                    <td class="num">{{ ! empty($l['base']) ? $money($l['base']) : '' }}</td>
                    <td class="num">{{ isset($l['rate']) && $l['rate'] !== null ? rtrim(rtrim(number_format($l['rate'], 3, ',', ''), '0'), ',') . ' %' : '' }}</td>
                    <td class="num">{{ $money($l['amount']) }}</td>
                    <td></td>
                </tr>
            @endforeach

            @foreach ($grossDeductions as $l)
                <tr>
                    @if ($template['show_item_codes'])<td class="code">{{ $l['code'] }}</td>@endif
                    <td>{{ $l['label'] }}@if (! empty($l['note']))<div class="note">{{ $l['note'] }}</div>@endif</td>
                    <td></td><td></td><td></td>
                    <td class="num neg">{{ $money($l['amount']) }}</td>
                </tr>
            @endforeach

            @if ($template['group_lines'])
                <tr class="subtotal"><td colspan="{{ $cols - 2 }}">Salaire brut</td><td class="num">{{ $money($totals['gross']) }}</td><td></td></tr>
                <tr class="section"><td colspan="{{ $cols }}">Cotisations salariales et impôt</td></tr>
            @endif

            @foreach ($employeeLegal as $l)
                <tr>
                    @if ($template['show_item_codes'])<td class="code">{{ $l['code'] }}</td>@endif
                    <td>{{ $l['label'] }}</td>
                    <td class="num">{{ ! empty($l['base']) ? $money($l['base']) : '' }}</td>
                    <td class="num">{{ isset($l['rate']) && $l['rate'] !== null ? rtrim(rtrim(number_format($l['rate'], 3, ',', ''), '0'), ',') . ' %' : 'barème' }}</td>
                    <td></td>
                    <td class="num neg">{{ $money($l['amount']) }}</td>
                </tr>
            @endforeach

            @if ($template['group_lines'])
                <tr class="subtotal"><td colspan="{{ $cols - 2 }}">Total des retenues salariales</td><td></td><td class="num">{{ $money($totals['employee_contributions']) }}</td></tr>
            @endif

            @if (count($netDeductions) > 0)
                @if ($template['group_lines'])<tr class="section"><td colspan="{{ $cols }}">Avantages en nature et autres retenues</td></tr>@endif
                @foreach ($netDeductions as $l)
                    <tr>
                        @if ($template['show_item_codes'])<td class="code">{{ $l['code'] ?? '' }}</td>@endif
                        <td>{{ $l['label'] }}@if (! empty($l['note']))<div class="note">{{ $l['note'] }}</div>@endif</td>
                        <td></td><td></td><td></td>
                        <td class="num neg">{{ $money($l['amount']) }}</td>
                    </tr>
                @endforeach
            @endif
        </tbody>
    </table>

    {{-- Récapitulatif et net à payer --}}
    <table class="sum" style="margin-top: 10px;">
        <tr>
            <td style="width: 52%;">
                <table class="recap">
                    <tr><td>Salaire brut</td><td class="v">{{ $money($totals['gross']) }}</td></tr>
                    <tr><td>Retenues salariales (cotisations et impôt)</td><td class="v neg">− {{ $money($totals['employee_contributions']) }}</td></tr>
                    <tr><td>Net avant autres retenues</td><td class="v">{{ $money($totals['net_before_other']) }}</td></tr>
                    @if ($totals['benefits'] > 0)<tr><td>Avantages en nature (déjà inclus dans le brut)</td><td class="v neg">− {{ $money($totals['benefits']) }}</td></tr>@endif
                    @if ($totals['internal'] > 0)<tr><td>Retenues internes</td><td class="v neg">− {{ $money($totals['internal']) }}</td></tr>@endif
                    @if ($totals['individual'] > 0)<tr><td>Prêts, avances et retenues individuelles</td><td class="v neg">− {{ $money($totals['individual']) }}</td></tr>@endif
                </table>
            </td>
            <td class="gap"></td>
            <td style="vertical-align: middle;">
                <div class="net">
                    <div class="lab">Net à payer</div>
                    <div class="amt">{{ $money($totals['net_to_pay']) }}</div>
                    <div class="cur">{{ $currencyLabel }}</div>
                </div>
            </td>
        </tr>
    </table>
    <div class="words">Arrêté le présent bulletin à la somme de : <strong>{{ $words }} {{ $currencyLabel }}</strong>.</div>

    {{-- Cumuls de l'année --}}
    <h3>Cumuls depuis janvier {{ $year }} ({{ $cumuls['months'] }} mois)</h3>
    <table class="grid">
        <tr>
            <th class="l">&nbsp;</th><th>Brut</th><th>Base imposable</th><th>Retenues salariales</th><th>dont ITS</th><th>Net payé</th>
        </tr>
        <tr>
            <td class="l muted">Cumul</td>
            <td>{{ $money($cumuls['gross']) }}</td>
            <td>{{ $money($cumuls['taxable_base']) }}</td>
            <td>{{ $money($cumuls['contributions']) }}</td>
            <td>{{ $money($cumuls['its']) }}</td>
            <td>{{ $money($cumuls['net']) }}</td>
        </tr>
    </table>

    {{-- Part patronale (information), selon le modèle de bulletin --}}
    @if ($template['show_employer_part'] && count($employerLegal) > 0)
        <h3>Part patronale (information)</h3>
        <table class="grid">
            <tr><th class="l">Cotisation</th><th>Base</th><th>Taux</th><th>Montant</th></tr>
            @foreach ($employerLegal as $l)
                <tr>
                    <td class="l" style="font-weight: normal;">{{ $l['label'] }}</td>
                    <td style="font-weight: normal;">{{ ! empty($l['base']) ? $money($l['base']) : '' }}</td>
                    <td style="font-weight: normal;">{{ isset($l['rate']) && $l['rate'] !== null ? rtrim(rtrim(number_format($l['rate'], 3, ',', ''), '0'), ',') . ' %' : '' }}</td>
                    <td>{{ $money($l['amount']) }}</td>
                </tr>
            @endforeach
            <tr><td class="l" colspan="3">Total des cotisations patronales</td><td>{{ $money($totals['employer_total']) }}</td></tr>
            <tr><td class="l" colspan="3">Coût total pour l'employeur</td><td>{{ $money($totals['employer_cost']) }}</td></tr>
        </table>
    @endif

    {{-- Mentions --}}
    <div class="legal">
        @if ($template['footer_text'])<div>{{ $template['footer_text'] }}</div>@endif
        @if ($identity['footer_text'])<div>{{ $identity['footer_text'] }}</div>@endif
        <div>Document confidentiel, à conserver sans limitation de durée. Empreinte d'intégrité : {{ $checksum }}.</div>
    </div>

</body>
</html>
