<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ClientRequest;
use App\Models\ConstructionProject;
use App\Models\InvestmentProject;
use App\Models\Notification;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ClientRequestController extends Controller
{
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'property_id' => 'nullable|exists:properties,id',
            'construction_project_id' => 'nullable|exists:construction_projects,id',
            'investment_project_id' => 'nullable|exists:investment_projects,id',
            'construction_uuid' => 'nullable|exists:construction_projects,uuid',
            'investment_uuid' => 'nullable|exists:investment_projects,uuid',
            'request_type' => 'nullable|in:immobilier,construction,investissement',
            'name' => 'required|string|max:255',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:30',
            'message' => 'required|string',
            'sector' => 'nullable|string|max:255',
            'department' => 'nullable|string|max:255',
            'project_description' => 'nullable|string',
            'consent' => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $constructionId = $request->construction_project_id;
        if (!$constructionId && $request->filled('construction_uuid')) {
            $constructionId = ConstructionProject::where('uuid', $request->construction_uuid)->value('id');
        }

        $investmentId = $request->investment_project_id;
        if (!$investmentId && $request->filled('investment_uuid')) {
            $investmentId = InvestmentProject::where('uuid', $request->investment_uuid)->value('id');
        }

        $requestType = $request->request_type;
        if (!$requestType) {
            if ($investmentId) {
                $requestType = 'investissement';
            } elseif ($constructionId) {
                $requestType = 'construction';
            } else {
                $requestType = 'immobilier';
            }
        }

        $clientRequest = ClientRequest::create([
            'user_id' => $request->user()?->id,
            'property_id' => $request->property_id,
            'construction_project_id' => $constructionId,
            'investment_project_id' => $investmentId,
            'name' => $request->name,
            'email' => $request->email,
            'phone' => $request->phone,
            'message' => $request->message,
            'sector' => $request->sector,
            'department' => $request->department,
            'project_description' => $request->project_description,
            'consent' => (bool) $request->consent,
            'request_type' => $requestType,
            'status' => 'pending',
        ]);

        $staffRecipients = User::whereHas('role', function ($query) {
            $query->whereIn('slug', ['gestionnaire', 'admin']);
        })->get();

        foreach ($staffRecipients as $recipient) {
            Notification::create([
                'user_id' => $recipient->id,
                'type' => 'client_request',
                'title' => 'Nouvelle demande client',
                'message' => $request->name,
                'data' => json_encode(['request_uuid' => $clientRequest->uuid]),
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Demande envoyee',
            'data' => $clientRequest
        ], 201);
    }

    public function pending()
    {
        $requests = ClientRequest::with(['user', 'property', 'constructionProject', 'investmentProject', 'agent'])
            ->whereIn('status', ['pending', 'approved'])
            ->orderBy('created_at', 'desc')
            ->paginate(15);

        return response()->json([
            'success' => true,
            'data' => $requests
        ]);
    }

    public function history()
    {
        $requests = ClientRequest::with(['user', 'property', 'constructionProject', 'investmentProject', 'agent'])
            ->whereNotIn('status', ['pending', 'approved'])
            ->orderBy('updated_at', 'desc')
            ->paginate(15);

        return response()->json([
            'success' => true,
            'data' => $requests
        ]);
    }

    public function approve($uuid)
    {
        $requestItem = ClientRequest::where('uuid', $uuid)->firstOrFail();
        $requestItem->update([
            'status' => 'approved',
            'approved_at' => now(),
            'rejection_reason' => null,
            'rejected_at' => null,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Demande acceptee'
        ]);
    }

    public function reject(Request $request, $uuid)
    {
        $validator = Validator::make($request->all(), [
            'rejection_reason' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $requestItem = ClientRequest::where('uuid', $uuid)->firstOrFail();
        $requestItem->update([
            'status' => 'rejected',
            'rejected_at' => now(),
            'rejection_reason' => $request->rejection_reason,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Demande refusee'
        ]);
    }

    public function assign(Request $request, $uuid)
    {
        $validator = Validator::make($request->all(), [
            'agent_id' => 'required|exists:users,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $requestItem = ClientRequest::where('uuid', $uuid)->firstOrFail();
        $agent = User::findOrFail($request->agent_id);

        $requiredType = match ($requestItem->request_type) {
            'construction' => 'constructeur',
            'investissement' => 'investissement',
            default => 'immobilier',
        };

        if ($agent->agent_type && $agent->agent_type !== $requiredType) {
            return response()->json([
                'success' => false,
                'message' => "Cet agent est de type {$agent->agent_type}. Type requis: {$requiredType}.",
            ], 422);
        }

        $requestItem->update([
            'agent_id' => $request->agent_id,
            'status' => 'assigned',
            'assigned_at' => now(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Demande assignee'
        ]);
    }

    // AGENT - demandes clients assignees
    public function agentAssigned(Request $request)
    {
        $requests = ClientRequest::with(['user', 'property', 'constructionProject', 'investmentProject', 'agent'])
            ->where('agent_id', $request->user()->id)
            ->where('status', 'assigned')
            ->orderBy('assigned_at', 'desc')
            ->paginate(15);

        return response()->json([
            'success' => true,
            'data' => $requests
        ]);
    }

    // AGENT - historique demandes clients
    public function agentHistory(Request $request)
    {
        $requests = ClientRequest::with(['user', 'property', 'constructionProject', 'investmentProject', 'agent'])
            ->where('agent_id', $request->user()->id)
            ->whereNotIn('status', ['pending', 'approved', 'assigned'])
            ->orderBy('updated_at', 'desc')
            ->paginate(15);

        return response()->json([
            'success' => true,
            'data' => $requests
        ]);
    }
}
