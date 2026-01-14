<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use Spatie\Permission\Models\Role;

class UserManagementController extends Controller
{
    public function index()
    {
        $users = User::with('role')->paginate(20);
        return response()->json($users);
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
        ]);

        $role = Role::where('slug', $validated['role'])->first();

        if (!$role) {
            return response()->json(['message' => 'Rôle invalide'], 400);
        }

        $user = User::create([
            'first_name' => $validated['first_name'],
            'last_name' => $validated['last_name'],
            'email' => $validated['email'],
            'phone' => $validated['phone'] ?? null,
            'password' => bcrypt($validated['password']),
            'role_id' => $role->id,
            'is_active' => $validated['is_active'] ?? true,
        ]);

        $user->load('role');

        return response()->json($user, 201);
    }

    public function show($id)
    {
        $user = User::with('role')->findOrFail($id);
        return response()->json($user);
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

        $user->update($validated);
        $user->load('role');

        return response()->json($user);
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
        $user->load('role');

        return response()->json($user);
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
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération des rôles',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function createRole(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|unique:roles,name',
            'permissions' => 'nullable|array',
        ]);

        $role = Role::create(['name' => $validated['name']]);

        if (!empty($validated['permissions'])) {
            $role->syncPermissions($validated['permissions']);
        }

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
        }

        $role->save();

        if (isset($validated['permissions'])) {
            $role->syncPermissions($validated['permissions']);
        }

        return response()->json($role);
    }

    // GESTIONNAIRE - Liste agents disponibles
    public function availableAgents()
    {
        $agents = User::whereHas('role', function ($query) {
            $query->where('slug', 'agent');
        })->where('is_active', true)->get();
        return response()->json($agents);
    }
}
