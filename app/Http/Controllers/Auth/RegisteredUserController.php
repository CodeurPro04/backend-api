<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Role;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Str;

class RegisteredUserController extends Controller
{
    /**
     * Handle an incoming registration request.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function store(Request $request): Response
    {
        // Validation des données reçues
        $validated = $request->validate([
            'first_name'            => ['required', 'string', 'max:255'],
            'last_name'             => ['required', 'string', 'max:255'],
            'email'                 => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'phone'                 => ['required', 'string', 'max:20'],
            'password'              => ['required', 'confirmed', Rules\Password::defaults()],
            'role'                  => ['required', 'string', 'exists:roles,slug'], // Assure que le rôle existe en base
        ]);

        // Récupérer l'id du rôle à partir du slug
        $role = Role::where('slug', $validated['role'])->first();

        // Création de l'utilisateur
        $user = User::create([
            'first_name'    => $validated['first_name'],
            'last_name'     => $validated['last_name'],
            'email'         => $validated['email'],
            'phone'         => $validated['phone'],
            'role_id'       => $role->id,
            'password'      => Hash::make($validated['password']),
            'is_active'     => true, // ou false selon ta logique
        ]);

        // Évènement de nouvel enregistrement
        event(new Registered($user));

        // Connexion automatique de l'utilisateur
        Auth::login($user);

        // Retourne la réponse avec code 201 (Created)
        return response()->json([
            'success' => true,
            'message' => 'Inscription réussie',
            'data' => [
                'user' => $user,
                'token' => $user->createToken('auth_token')->plainTextToken,
            ]
        ], 201);
    }
}
