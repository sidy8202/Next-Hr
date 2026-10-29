@props([
    'letterhead', 'reference', 'date', 'recipient' => [], 'subject', 'paragraphs' => [], 'details' => [], 'signatories' => [],
])

{{-- Ordre de mission : même en-tête que les lettres officielles. AUCUN montant ni perdiem sur ce document. --}}
<div class="letter">
    <table class="head">
        <tr>
            <td class="logo">@if ($logo = $letterhead->logoDataUri())<img src="{{ $logo }}" alt="" style="max-height: 70px; max-width: 170px;">@endif</td>
            <td class="company">
                <div class="name">{{ $letterhead->legal_name }}</div>
                @if ($letterhead->address)<div>{{ $letterhead->address }}@if ($letterhead->city), {{ $letterhead->city }}@endif</div>@endif
                @if ($letterhead->phone || $letterhead->email)<div>{{ collect([$letterhead->phone, $letterhead->email])->filter()->implode(' · ') }}</div>@endif
                @if ($letterhead->website)<div>{{ $letterhead->website }}</div>@endif
            </td>
        </tr>
    </table>
    <div class="bar"></div>

    <table class="meta">
        <tr>
            <td>Réf. : <strong>{{ $reference }}</strong></td>
            <td class="right">{{ $letterhead->city ? $letterhead->city.', le ' : 'Le ' }}{{ $date->translatedFormat('d F Y') }}</td>
        </tr>
    </table>

    <div class="to">
        <div class="label">Délivré à</div>
        @foreach ($recipient as $i => $line)<div @if ($i === 0) style="font-weight: bold;" @endif>{{ $line }}</div>@endforeach
    </div>

    <div class="banner">Ordre de mission</div>
    <div class="subject">Objet : <span>{{ $subject }}</span></div>

    @foreach ($paragraphs as $paragraph)<p>{{ $paragraph }}</p>@endforeach

    @if ($details)
        <table class="details" style="margin: 6px 0 12px;">
            @foreach ($details as $label => $value)<tr><td class="k">{{ $label }}</td><td>{{ $value }}</td></tr>@endforeach
        </table>
    @endif

    <table style="width: 100%; margin-top: 26px; border-collapse: collapse;">
        <tr>
            @foreach ($signatories as $s)
                <td style="width: 50%; text-align: center; vertical-align: top; padding: 0 12px;">
                    <div style="font-weight: bold;">{{ $s['label'] }}</div>
                    <div style="height: 70px;"></div>
                    <div style="font-weight: bold;">{{ $s['name'] }}</div>
                    @if ($s['title'])<div>{{ $s['title'] }}</div>@endif
                </td>
            @endforeach
        </tr>
    </table>

    <div class="footer">
        {{ $letterhead->footer_text }}
        @if ($letterhead->rccm || $letterhead->nif)<div>{{ collect([$letterhead->rccm ? 'RCCM : '.$letterhead->rccm : null, $letterhead->nif ? 'NIF : '.$letterhead->nif : null])->filter()->implode(' · ') }}</div>@endif
    </div>
</div>
