<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckRole
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        // Vérifier que l'utilisateur est authentifié
        if (!$request->user()) {
            return response()->json([
                'success' => false,
                'message' => 'Non authentifié'
            ], 401);
        }

        // Récupérer le rôle de l'utilisateur
        $user = $request->user();
        
        // Si role est un objet (relation), récupérer le slug
        if (is_object($user->role)) {
            $userRole = $user->role->slug;
        } 
        // Si role est une chaîne de caractères
        elseif (is_string($user->role)) {
            $userRole = $user->role;
        }
        // Si role_id existe, charger la relation
        elseif ($user->role_id) {
            $userRole = $user->role()->first()?->slug;
        }
        else {
            return response()->json([
                'success' => false,
                'message' => 'Rôle utilisateur non défini'
            ], 403);
        }

        // Vérifier si l'utilisateur a l'un des rôles requis
        if (!in_array($userRole, $roles)) {
            return response()->json([
                'success' => false,
                'message' => 'Accès refusé. Rôle requis: ' . implode(', ', $roles),
                'user_role' => $userRole
            ], 403);
        }

        return $next($request);
    }
}