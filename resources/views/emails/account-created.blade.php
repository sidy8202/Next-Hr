<!DOCTYPE html>
<html lang="fr">
<body style="margin:0;background:#f5f6fb;font-family:Arial,Helvetica,sans-serif;color:#121826;">
    <div style="max-width:560px;margin:0 auto;padding:24px;">
        <div style="background:#ffffff;border-radius:16px;padding:32px;border:1px solid #e5e7f0;">
            <h1 style="font-size:20px;margin:0 0 16px;">Bienvenue, {{ $name }} !</h1>
            <p style="line-height:1.6;margin:0 0 16px;">
                Un accès à l'application RH de <strong>{{ $companyName }}</strong> vient d'être créé pour vous.
            </p>
            <table style="width:100%;border-collapse:collapse;margin:0 0 20px;">
                <tr><td style="padding:8px 0;color:#5b6477;">Identifiant</td><td style="padding:8px 0;"><strong>{{ $loginEmail }}</strong></td></tr>
                <tr><td style="padding:8px 0;color:#5b6477;">Mot de passe provisoire</td><td style="padding:8px 0;font-family:monospace;font-size:16px;"><strong>{{ $password }}</strong></td></tr>
            </table>
            <p style="margin:0 0 24px;">
                <a href="{{ $loginUrl }}" style="background:#4f46e5;color:#ffffff;text-decoration:none;padding:12px 20px;border-radius:10px;display:inline-block;">Me connecter</a>
            </p>
            <p style="line-height:1.6;color:#5b6477;font-size:14px;margin:0;">
                Pour votre sécurité, vous devrez choisir un nouveau mot de passe dès votre première connexion.
                Ne partagez jamais ce message.
            </p>
        </div>
    </div>
</body>
</html>
