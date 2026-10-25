<!DOCTYPE html>
<html lang="fr">
<body style="font-family: Arial, Helvetica, sans-serif; color: #1f2937; line-height: 1.5;">
    <p>Bonjour {{ $candidateName }},</p>
    <p>Nous avons bien reçu votre candidature pour le poste de <strong>{{ $jobTitle }}</strong> chez {{ $company }}. Merci de l’intérêt que vous portez à notre entreprise.</p>
    <p>Notre équipe étudie chaque dossier. Si votre profil correspond, nous vous contacterons pour la suite du processus.</p>
    <p>Vous pouvez demander la suppression de vos données à tout moment en répondant à ce message ou en contactant l’entreprise.</p>
    <p>Cordialement,<br>{{ $company }}</p>
</body>
</html>
