<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <title>{{ $subject }}</title>
    @include('pdf.letter-styles')
    <style>@page { margin: 22mm 18mm; } body { margin: 0; }</style>
</head>
<body>
    <x-letter :letterhead="$letterhead" :reference="$reference" :date="$date" :subject="$subject" :body="$body"
              :signatory-name="$signatoryName" :signatory-title="$signatoryTitle" :recipient="$recipient" :sender="$sender"
              :copies="$copies" :deadline="$deadline" :deadline-days="$deadlineDays" :show-receipt="$showReceipt"
              :banner="$banner" :details="$details" :to-label="$toLabel" />
</body>
</html>
