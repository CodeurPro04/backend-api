<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * En-tetes de durcissement de base pour une API JSON : ne remplacent pas une
 * vraie CSP (peu pertinente pour une API sans HTML rendu par le serveur),
 * mais couvrent le MIME-sniffing, l'embarquement dans une iframe tierce et
 * la fuite du referrer.
 */
class SecurityHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('X-Frame-Options', 'DENY');
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');

        return $response;
    }
}
