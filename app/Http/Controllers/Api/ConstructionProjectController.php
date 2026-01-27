<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\ConstructionProject;
use App\Models\ConstructionQuote;
use App\Models\Message;
use App\Models\Notification;
use App\Models\User;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;

class ConstructionProjectController extends Controller
{
    // Liste publique des projets de construction
    public function publicIndex()
    {
        $projects = ConstructionProject::where('status', 'published')
            ->where('is_publication', true)
            ->orderBy('created_at', 'desc')
            ->paginate(10);
        return response()->json($projects);
    }

    // Details public d'un projet
    public function publicShow($uuid)
    {
        $project = ConstructionProject::where('uuid', $uuid)
            ->where('status', 'published')
            ->where('is_publication', true)
            ->firstOrFail();

        return response()->json([
            'success' => true,
            'data' => $project
        ]);
    }

    // VISITEUR - Soumettre une demande de projet construction
    public function submitRequest(Request $request)
    {
        $user = $request->user();

        $validated = $request->validate([
            'title' => 'nullable|string|max:255',
            'description' => 'required|string',
            'budget_min' => 'nullable|numeric',
            'budget_max' => 'nullable|numeric',
            'surface_area' => 'nullable|numeric',
            'location' => 'nullable|string|max:255',
            'city' => 'nullable|string|max:100',
        ]);

        $project = ConstructionProject::create([
            'uuid' => (string) Str::uuid(),
            'user_id' => $user->id,
            'title' => $validated['title'] ?? 'Demande de construction',
            'description' => $validated['description'],
            'project_type' => 'residential',
            'budget_min' => $validated['budget_min'] ?? null,
            'budget_max' => $validated['budget_max'] ?? null,
            'surface_area' => $validated['surface_area'] ?? null,
            'location' => $validated['location'] ?? null,
            'city' => $validated['city'] ?? null,
            'status' => 'submitted',
            'is_publication' => false,
        ]);

        return response()->json($project, 201);
    }

    // VISITEUR - Liste de ses demandes de projet
    public function myRequests(Request $request)
    {
        $user = $request->user();

        $projects = ConstructionProject::with(['agent'])
            ->where('user_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->paginate(10);

        return response()->json($projects);
    }

    // AGENT - Projets assignés
    public function assignedProjects(Request $request)
    {
        $user = $request->user();

        $projects = ConstructionProject::with(['user'])
            ->where('agent_id', $user->id)
            ->paginate(10);

        return response()->json($projects);
    }

    // AGENT - Créer un devis pour un projet construction
    public function createQuote(Request $request, $uuid)
    {
        $user = $request->user();

        $project = ConstructionProject::where('uuid', $uuid)->firstOrFail();

        $validated = $request->validate([
            'amount' => 'required|numeric|min:1',
            'currency' => 'nullable|string|max:10',
            'details' => 'nullable|string',
            'valid_until' => 'nullable|date',
            'validity_days' => 'nullable|integer|min:1',
        ]);

        $validityDays = $validated['validity_days'] ?? null;
        if (!$validityDays && !empty($validated['valid_until'])) {
            $validityDays = now()->diffInDays($validated['valid_until']);
        }
        if (!$validityDays || $validityDays < 1) {
            $validityDays = 30;
        }

        $quote = ConstructionQuote::create([
            'construction_project_id' => $project->id,
            'agent_id' => $user->id,
            'total_amount' => $validated['amount'],
            'currency' => $validated['currency'] ?? 'XOF',
            'notes' => $validated['details'] ?? null,
            'validity_days' => $validityDays,
            'status' => 'sent',
            'sent_at' => now(),
        ]);

        $project->loadMissing('user');

        $recipients = collect();
        if ($project->user) {
            $recipients->push($project->user);
        }

        $staffRecipients = User::whereHas('role', function ($query) {
            $query->whereIn('slug', ['gestionnaire', 'admin']);
        })->get();
        $recipients = $recipients->merge($staffRecipients)->unique('id');

        $subject = 'Nouveau devis de construction';
        $amountLabel = number_format((float) $quote->total_amount, 0, '.', ' ');
        $projectTitle = $project->title ?: 'Projet de construction';
        $agentName = $user->full_name ?? trim("{$user->first_name} {$user->last_name}");
        $messageBody = "Un devis a ete envoye pour {$projectTitle}. Montant: {$amountLabel} {$quote->currency}. Reference: {$quote->quote_number}.";

        foreach ($recipients as $recipient) {
            Message::create([
                'sender_id' => $user->id,
                'recipient_id' => $recipient->id,
                'property_id' => null,
                'subject' => $subject,
                'message' => $messageBody,
            ]);

            Notification::create([
                'user_id' => $recipient->id,
                'type' => 'construction_quote_sent',
                'title' => $subject,
                'message' => $agentName
                    ? "Devis envoye par {$agentName} pour {$projectTitle}."
                    : "Devis envoye pour {$projectTitle}.",
                'data' => [
                    'quote_id' => $quote->id,
                    'project_uuid' => $project->uuid,
                    'agent_id' => $user->id,
                ],
            ]);
        }

        return response()->json($quote, 201);
    }

    // AGENT - Liste de ses devis
    public function myQuotes(Request $request)
    {
        $user = $request->user();

        $quotes = ConstructionQuote::where('agent_id', $user->id)->paginate(10);

        return response()->json($quotes);
    }

    // GESTIONNAIRE - Liste projets en attente
    public function pending()
    {
        $projects = ConstructionProject::with(['user'])
            ->where('status', 'submitted')
            ->paginate(10);
        return response()->json($projects);
    }

    // GESTIONNAIRE - Assigner un agent à un projet
    public function assign(Request $request, $uuid)
    {
        $request->validate([
            'agent_id' => 'required|exists:users,id',
        ]);

        $project = ConstructionProject::where('uuid', $uuid)->firstOrFail();
        $agent = User::findOrFail($request->agent_id);
        if ($agent->agent_type && $agent->agent_type !== 'constructeur') {
            return response()->json([
                'success' => false,
                'message' => 'Seuls les agents constructeurs peuvent etre assignes.',
            ], 422);
        }

        $project->agent_id = $request->agent_id;
        $project->status = 'in_study';
        $project->save();

        return response()->json(['message' => 'Agent assigned']);
    }


    // GESTIONNAIRE - Historique des projets assignes
    public function managerHistory()
    {
        $projects = ConstructionProject::with(['user', 'agent'])
            ->whereIn('status', ['published', 'in_study', 'quoted', 'approved', 'rejected', 'in_progress', 'completed'])
            ->orderBy('updated_at', 'desc')
            ->paginate(10);

        return response()->json([
            'success' => true,
            'data' => $projects
        ]);
    }

    // GESTIONNAIRE/ADMIN - Approuver une demande
    public function approve(Request $request, $uuid)
    {
        $project = ConstructionProject::where('uuid', $uuid)->firstOrFail();
        $project->status = 'approved';
        $project->rejection_reason = null;
        $project->save();

        return response()->json(['message' => 'Demande approuvee']);
    }

    // GESTIONNAIRE/ADMIN - Rejeter une demande
    public function reject(Request $request, $uuid)
    {
        $validated = $request->validate([
            'rejection_reason' => 'required|string',
        ]);
        $project = ConstructionProject::where('uuid', $uuid)->firstOrFail();
        $project->status = 'rejected';
        $project->rejection_reason = $validated['rejection_reason'];
        $project->save();

        return response()->json(['message' => 'Demande rejetee']);
    }

    // ADMIN/GESTIONNAIRE - Publier un projet
    public function staffCreate(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'budget_min' => 'nullable|numeric',
            'budget_max' => 'nullable|numeric',
            'surface_area' => 'nullable|numeric',
            'location' => 'nullable|string|max:255',
            'city' => 'nullable|string|max:100',
            'images_path' => 'nullable|array',
            'images_path.*' => 'nullable|string',
            'images' => 'nullable|array',
            'images.*' => 'image|mimes:jpg,jpeg,png,webp',
            'plans_path' => 'nullable|array',
            'plans_path.*' => 'nullable|string',
            'plans' => 'nullable|array',
            'plans.*' => 'file|mimes:jpg,jpeg,png,webp,pdf',
        ]);

        $project = ConstructionProject::create([
            'uuid' => (string) Str::uuid(),
            'user_id' => $request->user()->id,
            'title' => $validated['title'],
            'description' => $validated['description'],
            'project_type' => 'residential',
            'budget_min' => $validated['budget_min'] ?? null,
            'budget_max' => $validated['budget_max'] ?? null,
            'surface_area' => $validated['surface_area'] ?? null,
            'location' => $validated['location'] ?? null,
            'city' => $validated['city'] ?? null,
            'status' => 'published',
            'is_publication' => true,
            'images_path' => $validated['images_path'] ?? null,
            'plans_path' => $validated['plans_path'] ?? null,
        ]);

        $imagePaths = $validated['images_path'] ?? [];
        if ($request->hasFile('images')) {
            foreach ($request->file('images') as $file) {
                $imagePaths[] = $file->store("construction/{$project->uuid}/images", 'public');
            }
        }

        if (!empty($imagePaths)) {
            $project->update([
                'images_path' => $imagePaths,
            ]);
        }

        $planPaths = $validated['plans_path'] ?? [];
        if ($request->hasFile('plans')) {
            foreach ($request->file('plans') as $file) {
                $planPaths[] = $file->store("construction/{$project->uuid}/plans", 'public');
            }
        }

        if (!empty($planPaths)) {
            $project->update([
                'plans_path' => $planPaths,
            ]);
        }

        return response()->json([
            'success' => true,
            'data' => $project
        ], 201);
    }

    // ADMIN/GESTIONNAIRE - Mettre a jour un projet
    public function staffUpdate(Request $request, $uuid)
    {
        $project = ConstructionProject::where('uuid', $uuid)->firstOrFail();
        $validated = $request->validate([
            'title' => 'sometimes|required|string|max:255',
            'description' => 'sometimes|required|string',
            'budget_min' => 'nullable|numeric',
            'budget_max' => 'nullable|numeric',
            'surface_area' => 'nullable|numeric',
            'location' => 'nullable|string|max:255',
            'city' => 'nullable|string|max:100',
            'status' => 'nullable|in:published,in_study,quoted,approved,rejected,in_progress,completed',
            'rejection_reason' => 'nullable|string',
            'images_path' => 'nullable|array',
            'images_path.*' => 'nullable|string',
            'images' => 'nullable|array',
            'images.*' => 'image|mimes:jpg,jpeg,png,webp',
            'remove_images' => 'nullable|array',
            'remove_images.*' => 'string',
            'plans_path' => 'nullable|array',
            'plans_path.*' => 'nullable|string',
            'plans' => 'nullable|array',
            'plans.*' => 'file|mimes:jpg,jpeg,png,webp,pdf',
            'remove_plans' => 'nullable|array',
            'remove_plans.*' => 'string',
        ]);

        $payload = $validated;
        unset($payload['images'], $payload['remove_images'], $payload['plans'], $payload['remove_plans']);
        if (array_key_exists('status', $payload) && $payload['status'] !== 'rejected') {
            $payload['rejection_reason'] = null;
        }
        $project->update($payload);

        $imagePaths = $validated['images_path'] ?? ($project->images_path ?? []);
        $removeImages = $validated['remove_images'] ?? [];

        if (!empty($removeImages)) {
            foreach ($removeImages as $path) {
                Storage::disk('public')->delete($path);
            }
            $imagePaths = array_values(array_diff($imagePaths, $removeImages));
        }

        if ($request->hasFile('images')) {
            foreach ($request->file('images') as $file) {
                $imagePaths[] = $file->store("construction/{$project->uuid}/images", 'public');
            }
        }

        if (!empty($imagePaths)) {
            $project->update([
                'images_path' => $imagePaths,
            ]);
        }

        $planPaths = $validated['plans_path'] ?? ($project->plans_path ?? []);
        $removePlans = $validated['remove_plans'] ?? [];

        if (!empty($removePlans)) {
            foreach ($removePlans as $path) {
                Storage::disk('public')->delete($path);
            }
            $planPaths = array_values(array_diff($planPaths, $removePlans));
        }

        if ($request->hasFile('plans')) {
            foreach ($request->file('plans') as $file) {
                $planPaths[] = $file->store("construction/{$project->uuid}/plans", 'public');
            }
        }

        if (!empty($planPaths)) {
            $project->update([
                'plans_path' => $planPaths,
            ]);
        }

        return response()->json([
            'success' => true,
            'data' => $project
        ]);
    }

    // AGENT - publier un projet de construction (en attente)
    public function agentCreate(Request $request)
    {
        if ($request->user()?->agent_type && $request->user()->agent_type !== 'constructeur') {
            return response()->json([
                'success' => false,
                'message' => 'Acces reserve aux agents constructeurs.',
            ], 403);
        }
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'budget_min' => 'nullable|numeric',
            'budget_max' => 'nullable|numeric',
            'surface_area' => 'nullable|numeric',
            'location' => 'nullable|string|max:255',
            'city' => 'nullable|string|max:100',
            'images_path' => 'nullable|array',
            'images_path.*' => 'nullable|string',
            'images' => 'nullable|array',
            'images.*' => 'image|mimes:jpg,jpeg,png,webp',
            'plans_path' => 'nullable|array',
            'plans_path.*' => 'nullable|string',
            'plans' => 'nullable|array',
            'plans.*' => 'file|mimes:jpg,jpeg,png,webp,pdf',
        ]);

        $project = ConstructionProject::create([
            'uuid' => (string) Str::uuid(),
            'user_id' => $request->user()->id,
            'title' => $validated['title'],
            'description' => $validated['description'],
            'project_type' => 'residential',
            'budget_min' => $validated['budget_min'] ?? null,
            'budget_max' => $validated['budget_max'] ?? null,
            'surface_area' => $validated['surface_area'] ?? null,
            'location' => $validated['location'] ?? null,
            'city' => $validated['city'] ?? null,
            'status' => 'submitted',
            'is_publication' => true,
            'images_path' => $validated['images_path'] ?? null,
            'plans_path' => $validated['plans_path'] ?? null,
        ]);

        $imagePaths = $validated['images_path'] ?? [];
        if ($request->hasFile('images')) {
            foreach ($request->file('images') as $file) {
                $imagePaths[] = $file->store("construction/{$project->uuid}/images", 'public');
            }
        }

        if (!empty($imagePaths)) {
            $project->update([
                'images_path' => $imagePaths,
            ]);
        }

        $planPaths = $validated['plans_path'] ?? [];
        if ($request->hasFile('plans')) {
            foreach ($request->file('plans') as $file) {
                $planPaths[] = $file->store("construction/{$project->uuid}/plans", 'public');
            }
        }

        if (!empty($planPaths)) {
            $project->update([
                'plans_path' => $planPaths,
            ]);
        }

        return response()->json([
            'success' => true,
            'data' => $project
        ], 201);
    }

    // AGENT - mettre a jour son projet (repasse en attente)
    public function agentUpdate(Request $request, $uuid)
    {
        if ($request->user()?->agent_type && $request->user()->agent_type !== 'constructeur') {
            return response()->json([
                'success' => false,
                'message' => 'Acces reserve aux agents constructeurs.',
            ], 403);
        }
        $project = ConstructionProject::where('uuid', $uuid)
            ->where('user_id', $request->user()->id)
            ->firstOrFail();

        $validated = $request->validate([
            'title' => 'sometimes|required|string|max:255',
            'description' => 'sometimes|required|string',
            'budget_min' => 'nullable|numeric',
            'budget_max' => 'nullable|numeric',
            'surface_area' => 'nullable|numeric',
            'location' => 'nullable|string|max:255',
            'city' => 'nullable|string|max:100',
            'images_path' => 'nullable|array',
            'images_path.*' => 'nullable|string',
            'images' => 'nullable|array',
            'images.*' => 'image|mimes:jpg,jpeg,png,webp',
            'remove_images' => 'nullable|array',
            'remove_images.*' => 'string',
            'plans_path' => 'nullable|array',
            'plans_path.*' => 'nullable|string',
            'plans' => 'nullable|array',
            'plans.*' => 'file|mimes:jpg,jpeg,png,webp,pdf',
            'remove_plans' => 'nullable|array',
            'remove_plans.*' => 'string',
        ]);

        $payload = $validated;
        unset($payload['images'], $payload['remove_images'], $payload['plans'], $payload['remove_plans']);
        $payload['status'] = 'submitted';
        $payload['is_publication'] = true;
        $project->update($payload);

        $imagePaths = $validated['images_path'] ?? ($project->images_path ?? []);
        $removeImages = $validated['remove_images'] ?? [];

        if (!empty($removeImages)) {
            foreach ($removeImages as $path) {
                Storage::disk('public')->delete($path);
            }
            $imagePaths = array_values(array_diff($imagePaths, $removeImages));
        }

        if ($request->hasFile('images')) {
            foreach ($request->file('images') as $file) {
                $imagePaths[] = $file->store("construction/{$project->uuid}/images", 'public');
            }
        }

        if (!empty($imagePaths)) {
            $project->update([
                'images_path' => $imagePaths,
            ]);
        }

        $planPaths = $validated['plans_path'] ?? ($project->plans_path ?? []);
        $removePlans = $validated['remove_plans'] ?? [];

        if (!empty($removePlans)) {
            foreach ($removePlans as $path) {
                Storage::disk('public')->delete($path);
            }
            $planPaths = array_values(array_diff($planPaths, $removePlans));
        }

        if ($request->hasFile('plans')) {
            foreach ($request->file('plans') as $file) {
                $planPaths[] = $file->store("construction/{$project->uuid}/plans", 'public');
            }
        }

        if (!empty($planPaths)) {
            $project->update([
                'plans_path' => $planPaths,
            ]);
        }

        return response()->json([
            'success' => true,
            'data' => $project
        ]);
    }

    // AGENT - mes projets de construction publies/en attente
    public function agentPublications(Request $request)
    {
        if ($request->user()?->agent_type && $request->user()->agent_type !== 'constructeur') {
            return response()->json([
                'success' => false,
                'message' => 'Acces reserve aux agents constructeurs.',
            ], 403);
        }
        $projects = ConstructionProject::where('user_id', $request->user()->id)
            ->where('is_publication', true)
            ->orderBy('updated_at', 'desc')
            ->paginate(10);

        return response()->json([
            'success' => true,
            'data' => $projects
        ]);
    }

    // ADMIN/GESTIONNAIRE - Supprimer un projet
    public function staffDestroy($uuid)
    {
        $project = ConstructionProject::where('uuid', $uuid)->firstOrFail();
        $project->delete();
        return response()->json(['success' => true]);
    }
}
