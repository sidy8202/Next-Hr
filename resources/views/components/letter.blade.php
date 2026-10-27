@props([
    'letterhead', 'reference', 'date', 'subject', 'body', 'signatoryName', 'signatoryTitle' => null,
    'recipient' => [], 'sender' => null, 'copies' => null, 'deadline' => null, 'deadlineDays' => null,
    'showReceipt' => false, 'banner' => null, 'details' => [], 'toLabel' => 'À l’attention de',
])

{{-- Lettre officielle : même rendu dans l'aperçu à l'écran et dans le PDF. --}}
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

    @if ($sender)
        <div class="from"><div class="label" style="font-size: 9.5px; text-transform: uppercase; color: #6b7280;">De la part de</div>
            @foreach ($sender as $i => $line)<div @if ($i === 0) style="font-weight: bold;" @endif>{{ $line }}</div>@endforeach
        </div>
    @endif

    <div class="to">
        <div class="label">{{ $toLabel }}</div>
        @foreach ($recipient as $i => $line)<div @if ($i === 0) style="font-weight: bold;" @endif>{{ $line }}</div>@endforeach
    </div>

    @if ($banner)<div class="banner">{{ $banner }}</div>@endif

    <div class="subject">Objet : <span>{{ $subject }}</span></div>

    @foreach (preg_split("/\n{2,}/", trim((string) $body)) as $paragraph)
        <p>{!! nl2br(e(trim($paragraph))) !!}</p>
    @endforeach

    @if ($details)
        <table class="details" style="margin: 6px 0 12px;">
            @foreach ($details as $label => $value)<tr><td class="k">{{ $label }}</td><td>{{ $value }}</td></tr>@endforeach
        </table>
    @endif

    @if ($deadline)
        <p>Vous disposez d’un délai de <strong>{{ $deadlineDays }} jour(s) ouvré(s)</strong> à compter de la réception de la présente, soit jusqu’au <strong>{{ $deadline->translatedFormat('l d F Y') }}</strong> inclus, pour nous faire parvenir vos explications écrites. À défaut de réponse dans ce délai, la suite sera décidée au vu des éléments dont nous disposons.</p>
    @endif

    <p>Nous vous prions d’agréer l’expression de nos salutations distinguées.</p>

    <div class="sign">
        <div>{{ $letterhead->city ?: '' }}</div>
        <div class="name">{{ $signatoryName }}</div>
        @if ($signatoryTitle)<div class="title">{{ $signatoryTitle }}</div>@endif
    </div>

    @if ($copies)<div class="copies"><strong>Copie à :</strong> {{ $copies }}</div>@endif

    @if ($showReceipt)
        <div class="receipt">Reçu en main propre le ____ / ____ / ________ &nbsp;&nbsp; Nom et signature de l’employé : ________________________________</div>
    @endif

    <div class="footer">
        {{ $letterhead->footer_text }}
        @if ($letterhead->rccm || $letterhead->nif)<div>{{ collect([$letterhead->rccm ? 'RCCM : '.$letterhead->rccm : null, $letterhead->nif ? 'NIF : '.$letterhead->nif : null])->filter()->implode(' · ') }}</div>@endif
    </div>
</div>
