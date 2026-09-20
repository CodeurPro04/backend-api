<?php

namespace App\Support;

class Sanitizer
{
    /**
     * Retire toute balise HTML/JS d'un texte libre saisi par l'utilisateur
     * (description de bien, de projet, candidature partenaire...) avant
     * stockage. Le contenu est affiche en texte brut cote frontend ; ceci
     * garantit qu'aucun payload HTML/script stocke ne puisse un jour se
     * retrouver rendu tel quel si un affichage venait a changer.
     */
    public static function text(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        return trim(strip_tags($value));
    }
}
