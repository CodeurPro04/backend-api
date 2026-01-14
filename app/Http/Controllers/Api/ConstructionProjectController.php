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

        $project->agent_id = $request->agent_id;
        $project->status = 'in_study';
        $project->save();

        return response()->json(['message' => 'Agent assigned']);
    }
}
