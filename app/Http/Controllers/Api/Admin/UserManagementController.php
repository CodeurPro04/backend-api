<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Role;
use App\Support\CountryContext;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class UserManagementController extends Controller
{
    private function appendPartnerType(User $user): User
    {
        $user->setAttribute('partner_type', $user->latestPartnership?->company_type);
        $user->setAttribute('partner_application_status', $user->latestPartnership?->status);

        return $user;
    }

    public function index(Request $request)
    {
        $query = User::with(['role', 'latestPartnership', 'country']);

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('first_name', 'like', "%{$search}%")
                    ->orWhere('last_name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        if ($request->filled('role')) {
            $query->whereHas('role', fn ($q) => $q->where('slug', $request->input('role')));
        }

        if ($request->filled('status')) {
            $query->where('is_active', $request->input('status') === 'active');
        }

        $perPage = min((int) $request->input('per_page', 20), 200) ?: 20;
        $users = $query->orderByDesc('created_at')->paginate($perPage);
        $users->setCollection($users->getCollection()->map(fn (User $user) => $this->appendPartnerType($user)));

        $stats = [
            'total' => User::count(),
            'active' => User::where('is_active', true)->count(),
            'pending' => User::where('is_active', false)->count(),
            'agents' => User::whereHas('role', fn ($q) => $q->where('slug', 'agent'))->count(),
            'by_role' => Role::withCount('users')
                ->orderByDesc('users_count')
                ->get()
                ->map(fn ($role) => ['label' => $role->name, 'value' => $role->users_count])
                ->values(),
        ];

        return response()->json([
            'success' => true,
            'data' => $users,
            'stats' => $stats,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'first_name' => 'required|string|max:100',
            'last_name' => 'required|string|max:100',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:6',
            'role' => 'required|string|exists:roles,slug',
            'phone' => 'nullable|string|max:20',
            'is_active' => 'boolean',
            'agent_type' => 'nullable|in:constructeur,immobilier,investissement',
            'country_id' => 'nullable|exists:countries,id',
            'country_code' => 'nullable|exists:countries,code',
        ]);

        $role = Role::where('slug', $validated['role'])->first();

        if (!$role) {
            return response()->json(['message' => 'Rôle invalide'], 400);
        }

        $countryId = CountryContext::resolveIdFromRequest($request);
        if (!in_array($role->slug, ['gestionnaire', 'admin', 'administrateur'], true) && !$countryId) {
            return response()->json(['message' => 'Veuillez sélectionner un pays.'], 422);
        }

        $user = User::create([
            'first_name' => $validated['first_name'],
            'last_name' => $validated['last_name'],
            'email' => $validated['email'],
            'country_id' => $countryId,
            'phone' => $validated['phone'] ?? null,
            'password' => bcrypt($validated['password']),
            'role_id' => $role->id,
            'agent_type' => $validated['agent_type'] ?? null,
            'is_active' => $validated['is_active'] ?? true,
        ]);

        $user->load(['role', 'latestPartnership', 'country']);

        return response()->json($this->appendPartnerType($user), 201);
    }

    public function show($id)
    {
        $user = User::with(['role', 'latestPartnership', 'country'])->findOrFail($id);

        return response()->json($this->appendPartnerType($user));
    }


    public function update(Request $request, $id)
    {
        $user = User::findOrFail($id);

        $validated = $request->validate([
            'first_name' => 'sometimes|string|max:100',
            'last_name' => 'sometimes|string|max:100',
            'email' => 'sometimes|email|unique:users,email,' . $id,
            'password' => 'nullable|string|min:6',
            'role' => 'sometimes|string|exists:roles,slug',
            'phone' => 'nullable|string|max:20',
            'is_active' => 'boolean',
            'agent_type' => 'nullable|in:constructeur,immobilier,investissement',
            'country_id' => 'nullable|exists:countries,id',
            'country_code' => 'nullable|exists:countries,code',
        ]);

        if (isset($validated['password'])) {
            $validated['password'] = bcrypt($validated['password']);
        } else {
            unset($validated['password']);
        }

        // Gere la mise à jour du rôle
        if (isset($validated['role'])) {
            $role = Role::where('slug', $validated['role'])->first();
            if ($role) {
                $validated['role_id'] = $role->id;
            }
            unset($validated['role']);
        }
        unset($validated['country_code']);
        if ($request->has('country_id') || $request->has('country_code')) {
            $validated['country_id'] = CountryContext::resolveIdFromRequest($request);
        }

        $user->update($validated);
        $user->load(['role', 'latestPartnership', 'country']);

        return response()->json($this->appendPartnerType($user));
    }

    public function destroy($id)
    {
        $user = User::findOrFail($id);
        $user->delete();
        return response()->json(['message' => 'User deleted']);
    }

    public function toggleStatus($id)
    {
        $user = User::findOrFail($id);
        $user->is_active = !$user->is_active;
        $user->save();

        return response()->json(['is_active' => $user->is_active]);
    }

    public function assignRole(Request $request, $id)
    {
        $validated = $request->validate([
            'role' => 'required|string|exists:roles,slug',
        ]);

        $user = User::findOrFail($id);
        $role = Role::where('slug', $validated['role'])->first();
        if (!$role) {
            return response()->json(['message' => 'RÔle invalide'], 400);
        }

        $user->role_id = $role->id;
        $user->save();
        $user->load(['role', 'latestPartnership', 'country']);

        return response()->json($this->appendPartnerType($user));
    }

    public function checkroles()
    {
        try {
            $roles = Role::all();

            return response()->json([
                'success' => true,
                'data' => $roles
            ]);
        } catch (\Exception $e) {
            Log::error('Erreur lors de la récupération des rôles', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération des rôles'
            ], 500);
        }
    }

    public function createRole(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|unique:roles,name',
            'permissions' => 'nullable|array',
        ]);

        $role = Role::create([
            'name' => $validated['name'],
            'slug' => Str::slug($validated['name']),
            'permissions' => $validated['permissions'] ?? [],
        ]);

        return response()->json($role, 201);
    }

    public function updateRole(Request $request, $id)
    {
        $role = Role::findOrFail($id);

        $validated = $request->validate([
            'name' => 'sometimes|string|unique:roles,name,' . $id,
            'permissions' => 'nullable|array',
        ]);

        if (isset($validated['name'])) {
            $role->name = $validated['name'];
            $role->slug = Str::slug($validated['name']);
        }

        if (array_key_exists('permissions', $validated)) {
            $role->permissions = $validated['permissions'] ?? [];
        }

        $role->save();

        return response()->json($role);
    }

    // GESTIONNAIRE - Liste agents disponibles
    public function availableAgents()
    {
        $agents = User::whereHas('role', function ($query) {
            $query->where('slug', 'agent');
        })->with('country')->where('is_active', true)->get();
        return response()->json($agents);
    }

    // PUBLIC - Liste des agents actifs (page d'accueil)
    public function publicAgents(Request $request)
    {
        $query = User::whereHas('role', function ($query) {
            $query->where('slug', 'agent');
        })
        ->where('is_active', true)
        ->with('country')
        ->select(['id', 'country_id', 'uuid', 'first_name', 'last_name', 'avatar', 'phone', 'agent_type']);

        CountryContext::applyPriority($query, $request, 'users');

        $agents = $query
        ->orderBy('created_at', 'desc')
        ->get()
        ->map(function ($agent) {
            return [
                'uuid'       => $agent->uuid,
                'first_name' => $agent->first_name,
                'last_name'  => $agent->last_name,
                'full_name'  => $agent->first_name . ' ' . $agent->last_name,
                'avatar'     => $agent->avatar,
                'phone'      => $agent->phone,
                'agent_type' => $agent->agent_type,
                'country'    => $agent->country,
            ];
        });

        return response()->json(['success' => true, 'data' => $agents]);
    }
}
