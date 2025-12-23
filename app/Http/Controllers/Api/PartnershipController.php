<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Partnership;
use Illuminate\Support\Str;

class PartnershipController extends Controller
{
    // ENTREPRISE - Postuler à un partenariat
    public function apply(Request $request)
    {
        $user = $request->user();

        $validated = $request->validate([
            'company_name' => 'required|string|max:255',
            'contact_email' => 'required|email',
            'contact_phone' => 'nullable|string',
            'message' => 'nullable|string',
            'documents' => 'nullable|array',
            'documents.*' => 'file|mimes:pdf,doc,docx|max:5120',
        ]);

        // Stocker les documents et récupérer les chemins
        $paths = [];
        if ($request->hasFile('documents')) {
            foreach ($request->file('documents') as $doc) {
                $paths[] = $doc->store('partnerships', 'public');
            }
        }

        $application = Partnership::create([
            'uuid' => (string) Str::uuid(),
            'user_id' => $user->id,
            'company_name' => $validated['company_name'],
            'contact_email' => $validated['contact_email'],
            'contact_phone' => $validated['contact_phone'] ?? null,
            'message' => $validated['message'] ?? null,
            'documents_paths' => json_encode($paths),
            'status' => 'pending',
        ]);

        return response()->json($application, 201);
    }

    // ENTREPRISE - Voir sa demande
    public function myApplication(Request $request)
    {
        $user = $request->user();
        $application = Partnership::where('user_id', $user->id)->latest()->first();

        return response()->json($application);
    }

    // ENTREPRISE - Mettre à jour sa demande
    public function update(Request $request)
    {
        $user = $request->user();
        $application = Partnership::where('user_id', $user->id)->latest()->firstOrFail();

        $validated = $request->validate([
            'company_name' => 'sometimes|string|max:255',
            'contact_email' => 'sometimes|email',
            'contact_phone' => 'nullable|string',
            'message' => 'nullable|string',
            'documents' => 'nullable|array',
            'documents.*' => 'file|mimes:pdf,doc,docx|max:5120',
        ]);

        if ($request->hasFile('documents')) {
            $paths = json_decode($application->documents_paths, true) ?? [];
            foreach ($request->file('documents') as $doc) {
                $paths[] = $doc->store('partnerships', 'public');
            }
            $validated['documents_paths'] = json_encode($paths);
        }

        $application->update($validated);

        return response()->json($application);
    }

    // ADMIN - Liste demandes en attente
    public function pending()
    {
        $applications = Partnership::where('status', 'pending')->paginate(15);
        return response()->json($applications);
    }

    // ADMIN - Approuver demande
    public function approve($uuid)
    {
        $application = Partnership::where('uuid', $uuid)->firstOrFail();
        $application->status = 'approved';
        $application->reviewed_by = auth()->id();
        $application->reviewed_at = now();
        $application->save();

        return response()->json(['message' => 'Application approved']);
    }

    // ADMIN - Rejeter demande
    public function reject(Request $request, $uuid)
    {
        $application = Partnership::where('uuid', $uuid)->firstOrFail();

        $request->validate([
            'rejection_reason' => 'required|string',
        ]);

        $application->status = 'rejected';
        $application->reviewed_by = auth()->id();
        $application->reviewed_at = now();
        $application->rejection_reason = $request->rejection_reason;
        $application->save();

        return response()->json(['message' => 'Application rejected']);
    }

    // ADMIN - Liste toutes les demandes
    public function all()
    {
        $applications = Partnership::paginate(20);
        return response()->json($applications);
    }
}
