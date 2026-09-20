<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

/**
 * Envoye au propriétaire reel de l'adresse email lorsqu'un compte visiteur
 * est cree automatiquement suite a une demande publique (fiche bien,
 * construction, investissement). Le mot de passe temporaire n'est JAMAIS
 * renvoye dans la reponse API : seul le destinataire de cet email peut le
 * recuperer, ce qui empeche quiconque de creer un compte au nom de l'email
 * de quelqu'un d'autre et d'en recuperer les identifiants.
 */
class VisitorAccountCreated extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public User $user,
        public string $temporaryPassword,
    ) {
    }

    public function build(): self
    {
        return $this
            ->subject('Votre compte Africa Build Invest')
            ->view('emails.visitor-account-created');
    }
}
