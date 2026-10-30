@props(['letterhead'])

{{-- En-tête de l'entreprise (logo + coordonnées) : même rendu que les lettres officielles. --}}
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
