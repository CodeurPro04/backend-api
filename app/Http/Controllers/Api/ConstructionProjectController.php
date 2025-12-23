<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\ConstructionProject;
use App\Models\ConstructionRequest;
use App\Models\Quote;
use Illuminate\Support\Str;

class ConstructionProjectController extends Controller
{
    // Liste publique des projets de construction
    public function publicIndex()
    {
        $projects = ConstructionProject::where('status', 'active')->paginate(10);
        return response()->json($projects);
    }

    // VISITEUR - Soumettre une demande de projet construction
    public function submitRequest(Request $request)
    {
        $user = $request->user();

        $validated = $request->validate([
            'project_id' => 'required|exists:construction_projects,id',
            'description' => 'required|string',
            'budget' => 'nullable|numeric',
            'deadline' => 'nullable|date',
        ]);

        $requestProject = ConstructionRequest::create([
            'uuid' => (string) Str::uuid(),
            'user_id' => $user->id,
            'project_id' => $validated['project_id'],
            'description' => $validated['description'],
            'budget' => $validated['budget'] ?? null,
            'deadline' => $validated['deadline'] ?? null,
            'status' => 'pending',
        ]);

        return response()->json($requestProject, 201);
    }

    // VISITEUR - Liste de ses demandes de projet
    public function myRequests(Request $request)
    {
        $user = $request->user();

        $requests = ConstructionRequest::where('user_id', $user->id)->paginate(10);

        return response()->json($requests);
    }

    // AGENT - Projets assignés
    public function assignedProjects(Request $request)
    {
        $user = $request->user();

        $projects = ConstructionProject::whereHas('assignedAgents', fn($q) => $q->where('user_id', $user->id))
            ->paginate(10);

        return response()->json($projects);
    }

    // AGENT - Créer un devis pour un projet construction
    public function createQuote(Request $request, $uuid)
    {
        $user = $request->user();

        $project = ConstructionProject::where('uuid', $uuid)->firstOrFail();

        $validated = $request->validate([
            'construction_request_id' => 'required|exists:construction_requests,id',
            'amount' => 'required|numeric|min:1',
            'details' => 'nullable|string',
            'valid_until' => 'nullable|date',
        ]);

        $quote = Quote::create([
            'uuid' => (string) Str::uuid(),
            'agent_id' => $user->id,
            'construction_project_id' => $project->id,
            'construction_request_id' => $validated['construction_request_id'],
            'amount' => $validated['amount'],
            'details' => $validated['details'] ?? null,
            'valid_until' => $validated['valid_until'] ?? null,
            'status' => 'pending',
        ]);

        return response()->json($quote, 201);
    }

    // AGENT - Liste de ses devis
    public function myQuotes(Request $request)
    {
        $user = $request->user();

        $quotes = Quote::where('agent_id', $user->id)->paginate(10);

        return response()->json($quotes);
    }

    // GESTIONNAIRE - Liste projets en attente
    public function pending()
    {
        $projects = ConstructionProject::where('status', 'pending')->paginate(10);
        return response()->json($projects);
    }

    // GESTIONNAIRE - Assigner un agent à un projet
    public function assign(Request $request, $uuid)
    {
        $request->validate([
            'agent_id' => 'required|exists:users,id',
        ]);

        $project = ConstructionProject::where('uuid', $uuid)->firstOrFail();

        // Relation many-to-many entre projets et agents (ex: assignedAgents)
        $project->assignedAgents()->syncWithoutDetaching([$request->agent_id]);

        $project->status = 'assigned';
        $project->save();

        return response()->json(['message' => 'Agent assigned']);
    }
}
