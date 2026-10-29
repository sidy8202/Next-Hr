<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <title>Ordre de mission {{ $reference }}</title>
    @include('pdf.letter-styles')
</head>
<body>
    <x-mission-order :letterhead="$letterhead" :reference="$reference" :date="$date" :recipient="$recipient" :subject="$subject"
                     :paragraphs="$paragraphs" :details="$details" :signatories="$signatories" />
</body>
</html>
