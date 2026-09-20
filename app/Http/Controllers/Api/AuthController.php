<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Role;
use App\Models\ActivityLog;
use App\Support\CountryContext;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Password as PasswordBroker;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;

class AuthController extends Controller
{
    private function serializeUser(User $user): array
    {
        $user->loadMissing(['role', 'latestPartnership', 'country']);

        return [
            'id' => $user->id,
            'uuid' => $user->uuid,
            'first_name' => $user->first_name,
            'last_name' => $user->last_name,
            'full_name' => $user->full_name,
            'email' => $user->email,
            'phone' => $user->phone,
            'role' => $user->role?->slug,
            'role_name' => $user->role?->name,
            'agent_type' => $user->agent_type,
            'partner_type' => $user->latestPartnership?->company_type,
            'partner_application_status' => $user->latestPartnership?->status,
            'country_id' => $user->country_id,
            'country' => $user->country ? [
                'id' => $user->country->id,
                'name' => $user->country->name,
                'code' => $user->country->code,
                'flag' => $user->country->flag,
            ] : null,
            'avatar' => $user->avatar,
            'interests' => $user->interests ?? [],
            'email_verified_at' => $user->email_verified_at,
            'is_active' => $user->is_active,
            'last_login_at' => $user->last_login_at,
            'created_at' => $user->created_at,
        ];
    }

    /**
     * Inscription d'un nouvel utilisateur
     */
    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'first_name'  => 'required|string|max:100',
            'last_name'   => 'required|string|max:100',
            'email'       => 'required|email|unique:users,email',
            'phone'       => 'nullable|string|max:20',
            'password'    => ['required', 'confirmed', Password::min(8)->mixedCase()->numbers()],
            // "gestionnaire" et "admin" sont volontairement exclus : ce sont des roles
            // de staff qui ne doivent jamais pouvoir etre auto-attribues via
            // l'inscription publique, uniquement crees par un administrateur
            // depuis le backoffice (Admin/UserManagementController).
            'role'        => 'required|in:visiteur,proprietaire,agent,investisseur,entreprise',
            'agent_type'  => 'nullable|in:constructeur,immobilier,investissement',
            'country_id'  => 'nullable|exists:countries,id',
            'country_code'=> 'nullable|exists:countries,code',
            'interests'   => 'nullable|array',
            'interests.*' => 'nullable|string|in:immobilier,construction,investissement',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $roleSlug = $request->input('role');
        $countryId = CountryContext::resolveIdFromRequest($request);
        if (!in_array($roleSlug, ['gestionnaire', 'administrateur', 'admin'], true) && !$countryId) {
            return response()->json([
                'success' => false,
                'errors' => [
                    'country' => ['Veuillez sélectionner un pays.']
                ]
            ], 422);
        }

        $interests = $request->input('interests', []);
        if ($roleSlug === 'visiteur' && (!is_array($interests) || count($interests) < 1)) {
            return response()->json([
                'success' => false,
                'errors' => [
                    'interests' => ['Veuillez sélectionner au moins un centre d\'intérêt.']
                ]
            ], 422);
        }

        try {
            // Recuperer le role
            $role = Role::where('slug', $request->role)->first();

            if (!$role) {
                return response()->json([
                    'success' => false,
                    'message' => 'Role invalide'
                ], 400);
            }

            // Les comptes agent, proprietaire et entreprise necessitent une activation admin.
            $requiresActivation = in_array($role->slug, ['agent', 'proprietaire', 'entreprise'], true);

            // Creer l'utilisateur
            $user = User::create([
                'first_name' => $request->first_name,
                'last_name'  => $request->last_name,
                'email'      => $request->email,
                'country_id' => $countryId,
                'phone'      => $request->phone,
                'password'   => Hash::make($request->password),
                'role_id'    => $role->id,
                'agent_type' => $request->agent_type,
                'interests'  => $request->interests ?? [],
                'is_active'  => !$requiresActivation,
            ]);

            // Log l'activite
            ActivityLog::create([
                'user_id' => $user->id,
                'action' => 'registered',
                'description' => 'Nouvel utilisateur inscrit',
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'created_at' => now(),
            ]);

            // Creer un token uniquement pour les comptes actifs
            $token = null;
            if ($user->is_active) {
                // Session unique : garantit qu'un seul jeton API est actif par compte.
                $user->tokens()->delete();
                $token = $user->createToken('auth_token')->plainTextToken;
            }

            return response()->json([
                'success' => true,
                'message' => 'Inscription reussie',
                'data' => [
                    'user' => $this->serializeUser($user),
                    'token' => $token,
                    'requires_activation' => $requiresActivation,
                ]
            ], 201);

        } catch (\Exception $e) {
            Log::error('Erreur lors de l\'inscription', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de l\'inscription'
            ], 500);
        }
    }

    /**
     * Connexion
     */
public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'password' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $user = User::where('email', $request->email)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            return response()->json([
                'success' => false,
                'message' => 'Email ou mot de passe incorrect'
            ], 401);
        }

        // Un compte en attente d'activation (agent/proprietaire/entreprise fraichement
        // inscrit) ne doit recevoir aucun jeton tant qu'un admin ne l'a pas active :
        // sinon le controle d'activation est totalement contournable via /login.
        if (!$user->is_active) {
            return response()->json([
                'success' => false,
                'message' => "Votre compte est en attente d'activation par un administrateur.",
                'requires_activation' => true,
            ], 403);
        }

        // Mettre à jour le dernier login
        $user->update(['last_login_at' => now()]);

        // Log l'activité
        ActivityLog::create([
            'user_id' => $user->id,
            'action' => 'logged_in',
            'description' => 'Utilisateur connecté',
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'created_at' => now(),
        ]);

        // Session unique : toute nouvelle connexion invalide les sessions
        // precedentes de ce compte (un seul appareil/onglet connecte a la fois).
        $user->tokens()->delete();

        // Créer un token
        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'success' => true,
            'message' => 'Connexion réussie',
            'data' => [
                'user' => $this->serializeUser($user),
                'token' => $token,
            ]
        ]);
    }

    /**
     * Demande de reinitialisation de mot de passe : envoie un email contenant
     * un lien signe vers le site public si l'adresse correspond a un compte.
     * La reponse est volontairement identique que l'email existe ou non,
     * pour ne pas permettre l'enumeration de comptes.
     */
    public function forgotPassword(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        PasswordBroker::sendResetLink($request->only('email'));

        return response()->json([
            'success' => true,
            'message' => "Si un compte existe avec cette adresse, un email de reinitialisation vient d'etre envoye.",
        ]);
    }

    /**
     * Reinitialisation effective du mot de passe a partir du jeton recu par email.
     */
    public function resetPassword(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'token' => 'required|string',
            'email' => 'required|email',
            'password' => ['required', 'confirmed', Password::min(8)->mixedCase()->numbers()->symbols()],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $status = PasswordBroker::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function (User $user, string $password) {
                $user->update(['password' => Hash::make($password)]);
                // Reinitialiser le mot de passe invalide toutes les sessions actives.
                $user->tokens()->delete();
            }
        );

        if ($status !== PasswordBroker::PASSWORD_RESET) {
            return response()->json([
                'success' => false,
                'message' => 'Ce lien de reinitialisation est invalide ou a expire.',
            ], 422);
        }

        return response()->json([
            'success' => true,
            'message' => 'Mot de passe reinitialise avec succes. Vous pouvez vous connecter.',
        ]);
    }

    /**
     * Déconnexion
     */
    public function logout(Request $request)
    {
        try {
            // Log l'activité
            ActivityLog::create([
                'user_id' => $request->user()->id,
                'action' => 'logged_out',
                'description' => 'Utilisateur déconnecté',
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'created_at' => now(),
            ]);

            // Supprimer le token actuel
            $request->user()->currentAccessToken()->delete();

            return response()->json([
                'success' => true,
                'message' => 'Déconnexion réussie'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la déconnexion'
            ], 500);
        }
    }

    /**
     * Profil utilisateur
     */
    public function profile(Request $request)
    {
        $user = $request->user()->load(['role', 'latestPartnership', 'country']);

        return response()->json([
            'success' => true,
            'data' => [
                'user' => $this->serializeUser($user)
            ]
        ]);
    }

    /**
     * Mise à jour du profil
     */
    public function updateProfile(Request $request)
    {
        $user = $request->user();

        $validator = Validator::make($request->all(), [
            'first_name'  => 'sometimes|string|max:100',
            'last_name'   => 'sometimes|string|max:100',
            'phone'       => 'sometimes|nullable|string|max:20',
            'avatar'      => 'sometimes|nullable|image|max:2048',
            'country_id'  => 'sometimes|nullable|exists:countries,id',
            'country_code'=> 'sometimes|nullable|exists:countries,code',
            'interests'   => 'sometimes|nullable|array',
            'interests.*' => 'string|in:immobilier,construction,investissement',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $data = $request->only(['first_name', 'last_name', 'phone']);
            if ($request->has('interests')) {
                $data['interests'] = $request->interests ?? [];
            }
            if ($request->has('country_id') || $request->has('country_code')) {
                $data['country_id'] = CountryContext::resolveIdFromRequest($request);
            }

            // Gestion de l'avatar
            if ($request->hasFile('avatar')) {
                $avatar = $request->file('avatar');
                $path = $avatar->store('avatars', 'public');
                $data['avatar'] = $path;

                // Supprimer l'ancien avatar
                if ($user->avatar) {
                    \Storage::disk('public')->delete($user->avatar);
                }
            }

            $user->update($data);

            return response()->json([
                'success' => true,
                'message' => 'Profil mis à jour avec succès',
                'data' => [
                    'user' => $this->serializeUser($user)
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Erreur lors de la mise à jour du profil', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la mise à jour'
            ], 500);
        }
    }

    /**
     * Changement de mot de passe
     */
    public function changePassword(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'current_password' => 'required',
            'new_password' => ['required', 'confirmed', Password::min(8)->mixedCase()->numbers()],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $user = $request->user();

        if (!Hash::check($request->current_password, $user->password)) {
            return response()->json([
                'success' => false,
                'message' => 'Mot de passe actuel incorrect'
            ], 400);
        }

        try {
            $user->update([
                'password' => Hash::make($request->new_password)
            ]);

            // Invalide toutes les autres sessions actives (garde uniquement celle
            // en cours) : si un jeton avait fuite, changer le mot de passe le
            // revoque immediatement.
            $currentToken = $user->currentAccessToken();
            if ($currentToken) {
                $user->tokens()->where('id', '!=', $currentToken->id)->delete();
            }

            // Log l'activité
            ActivityLog::create([
                'user_id' => $user->id,
                'action' => 'password_changed',
                'description' => 'Mot de passe modifié',
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'created_at' => now(),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Mot de passe modifié avec succès'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors du changement de mot de passe'
            ], 500);
        }
    }
}

