<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Partnership;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;

class PartnershipController extends Controller
{
    // ENTREPRISE - Postuler a un partenariat
    public function apply(Request $request)
    {
        $user = $request->user();

        $validated = $request->validate([
            'company_name' => 'required|string|max:255',
            'company_type' => 'required|string|max:255',
            'registration_number' => 'nullable|string|max:255',
            'tax_number' => 'nullable|string|max:255',
            'address' => 'nullable|string|max:255',
            'city' => 'nullable|string|max:100',
            'phone' => 'nullable|string|max:100',
            'email' => 'nullable|email',
            'website' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'services' => 'nullable|array',
            'services.*' => 'string',
            'certifications' => 'nullable|array',
            'certifications.*' => 'string',
            'logo' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:4096',
        ]);

        $uuid = (string) Str::uuid();
        $logoPath = null;

        if ($request->hasFile('logo')) {
            $logoPath = $request->file('logo')->store("partnerships/{$uuid}/logo", 'public');
        }

        $application = Partnership::create([
            'uuid' => $uuid,
            'user_id' => $user->id,
            'company_name' => $validated['company_name'],
            'company_type' => $validated['company_type'],
            'registration_number' => $validated['registration_number'] ?? null,
            'tax_number' => $validated['tax_number'] ?? null,
            'address' => $validated['address'] ?? null,
            'city' => $validated['city'] ?? null,
            'phone' => $validated['phone'] ?? null,
            'email' => $validated['email'] ?? null,
            'website' => $validated['website'] ?? null,
            'logo_path' => $logoPath,
            'description' => $validated['description'] ?? null,
            'services' => $validated['services'] ?? [],
            'certifications' => $validated['certifications'] ?? [],
            'status' => 'pending',
        ]);

        return response()->json([
            'success' => true,
            'data' => $application,
        ], 201);
    }

    // ENTREPRISE - Voir sa demande
    public function myApplication(Request $request)
    {
        $user = $request->user();
        $application = Partnership::where('user_id', $user->id)->latest()->first();

        return response()->json([
            'success' => true,
            'data' => $application,
        ]);
    }

    // ENTREPRISE - Mettre a jour sa demande
    public function update(Request $request)
    {
        $user = $request->user();
        $application = Partnership::where('user_id', $user->id)->latest()->firstOrFail();

        $validated = $request->validate([
            'company_name' => 'sometimes|required|string|max:255',
            'company_type' => 'sometimes|required|string|max:255',
            'registration_number' => 'nullable|string|max:255',
            'tax_number' => 'nullable|string|max:255',
            'address' => 'nullable|string|max:255',
            'city' => 'nullable|string|max:100',
            'phone' => 'nullable|string|max:100',
            'email' => 'nullable|email',
            'website' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'services' => 'nullable|array',
            'services.*' => 'string',
            'certifications' => 'nullable|array',
            'certifications.*' => 'string',
            'logo' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:4096',
        ]);

        $payload = $validated;
        unset($payload['logo']);

        if ($request->hasFile('logo')) {
            if ($application->logo_path) {
                Storage::disk('public')->delete($application->logo_path);
            }
            $payload['logo_path'] = $request->file('logo')->store("partnerships/{$application->uuid}/logo", 'public');
        }

        $application->update($payload);

        return response()->json([
            'success' => true,
            'data' => $application->fresh(),
        ]);
    }

    // ADMIN - Liste demandes en attente
    public function pending()
    {
        $applications = Partnership::with(['user', 'approver'])
            ->where('status', 'pending')
            ->orderBy('created_at', 'desc')
            ->paginate(15);
        return response()->json($applications);
    }

    // ADMIN - Approuver demande
    public function approve($uuid)
    {
        $application = Partnership::where('uuid', $uuid)->firstOrFail();
        $application->status = 'approved';
        $application->approved_by = auth()->id();
        $application->approved_at = now();
        $application->save();

        if ($application->user) {
            $application->user->update(['is_active' => true]);
        }

        return response()->json(['success' => true]);
    }

    // ADMIN - Rejeter demande
    public function reject(Request $request, $uuid)
    {
        $request->validate([
            'rejection_reason' => 'required|string',
        ]);

        $application = Partnership::where('uuid', $uuid)->firstOrFail();
        $application->status = 'rejected';
        $application->approved_by = auth()->id();
        $application->approved_at = now();
        $application->rejection_reason = $request->rejection_reason;
        $application->save();

        return response()->json(['success' => true]);
    }

    // PUBLIC - Liste des partenaires approuves
    public function publicApproved()
    {
        $partners = Partnership::where('status', 'approved')
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $partners,
        ]);
    }

    // ADMIN - Supprimer un partenariat
    public function destroy($uuid)
    {
        $application = Partnership::where('uuid', $uuid)->firstOrFail();

        if ($application->logo_path) {
            Storage::disk('public')->delete($application->logo_path);
        }

        $application->delete();

        return response()->json(['success' => true]);
    }

    // ADMIN - Liste toutes les demandes
    public function all()
    {
        $applications = Partnership::with(['user', 'approver'])
            ->orderBy('created_at', 'desc')
            ->paginate(20);
        return response()->json($applications);
    }
}
