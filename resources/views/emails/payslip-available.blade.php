<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Votre bulletin de paie</title>
</head>
<body style="margin:0; padding:0; background:#f3f4f6; font-family: Arial, Helvetica, sans-serif; color:#1f2937;">
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#f3f4f6; padding:24px 0;">
        <tr>
            <td align="center">
                <table role="presentation" width="560" cellpadding="0" cellspacing="0" style="max-width:560px; width:100%; background:#ffffff; border-radius:12px; overflow:hidden; border:1px solid #e5e7eb;">
                    <tr>
                        <td style="background:#0f766e; padding:20px 28px; color:#ffffff;">
                            <div style="font-size:12px; letter-spacing:1px; text-transform:uppercase; opacity:.85;">{{ $company }}</div>
                            <div style="font-size:20px; font-weight:bold; margin-top:4px;">Votre bulletin de paie</div>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:28px;">
                            <p style="margin:0 0 14px; font-size:15px;">Bonjour {{ $name }},</p>
                            <p style="margin:0 0 14px; font-size:15px; line-height:1.55;">
                                Votre bulletin de paie de <strong>{{ $periodLabel }}</strong> est disponible. Vous le trouverez en pièce jointe de ce message.
                            </p>
                            <p style="margin:0 0 22px; font-size:15px; line-height:1.55;">
                                Vous pouvez aussi le consulter et le télécharger à tout moment dans l'application, rubrique « Mes bulletins ».
                            </p>
                            <p style="margin:0 0 24px;">
                                <a href="{{ $appUrl }}" style="display:inline-block; background:#0f766e; color:#ffffff; text-decoration:none; font-size:14px; font-weight:bold; padding:11px 20px; border-radius:8px;">Ouvrir mes bulletins</a>
                            </p>
                            <p style="margin:0; font-size:12px; line-height:1.5; color:#6b7280;">
                                Ce document est confidentiel et personnel. Si vous constatez une erreur, contactez le service des ressources humaines ou de la paie de {{ $company }}.
                            </p>
                        </td>
                    </tr>
                    <tr>
                        <td style="background:#f9fafb; padding:14px 28px; font-size:11px; color:#9ca3af; border-top:1px solid #e5e7eb;">
                            Message automatique envoyé par {{ $company }}. Merci de ne pas y répondre.
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
    <img src="{{ $trackUrl }}" width="1" height="1" alt="" style="display:block; width:1px; height:1px; border:0;">
</body>
</html>
