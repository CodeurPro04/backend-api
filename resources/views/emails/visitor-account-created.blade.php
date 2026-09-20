<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <title>Votre compte Africa Build Invest</title>
</head>
<body style="font-family: Arial, sans-serif; background-color: #f4f6f8; padding: 24px; color: #1a2530;">
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="max-width: 520px; margin: 0 auto; background: #ffffff; border-radius: 8px; overflow: hidden;">
        <tr>
            <td style="background-color: #0d63c9; padding: 20px 24px;">
                <span style="color: #ffffff; font-size: 18px; font-weight: bold;">Africa Build Invest</span>
            </td>
        </tr>
        <tr>
            <td style="padding: 24px;">
                <p>Bonjour {{ $user->first_name }},</p>
                <p>
                    Votre demande a bien été reçue. Comme vous n'aviez pas encore de compte, nous en avons créé un
                    automatiquement afin que vous puissiez suivre l'avancement de votre demande.
                </p>
                <p style="margin: 20px 0; padding: 16px; background-color: #f4f6f8; border-radius: 6px;">
                    <strong>Email :</strong> {{ $user->email }}<br>
                    <strong>Mot de passe temporaire :</strong> {{ $temporaryPassword }}
                </p>
                <p>
                    Pour votre sécurité, nous vous recommandons de vous connecter et de changer ce mot de passe dès
                    que possible depuis votre espace personnel.
                </p>
                <p style="margin-top: 24px; font-size: 12px; color: #6b7788;">
                    Si vous n'êtes pas à l'origine de cette demande, vous pouvez ignorer cet email ou nous contacter.
                </p>
            </td>
        </tr>
    </table>
</body>
</html>
