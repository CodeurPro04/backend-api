<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\SearchRequest;
use App\Models\Notification;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class SearchRequestController extends Controller
{
    /**
     * Créer une demande de recherche (Visiteur)
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'property_type_id' => 'nullable|exists:property_types,id',
            'transaction_type' => 'required|in:vente,location',
            'budget_min' => 'nullable|numeric|min:0',
            'budget_max' => 'nullable|numeric|min:0',
            'location_preferences' => 'nullable|array',
            'bedrooms_min' => 'nullable|integer|min:0',
            'surface_min' => 'nullable|numeric|min:0',
            'additional_requirements' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $searchRequest = SearchRequest::create([
                'user_id' => $request->user()->id,
                'property_type_id' => $request->property_type_id,
                'transaction_type' => $request->transaction_type,
                'budget_min' => $request->budget_min,
                'budget_max' => $request->budget_max,
                'location_preferences' => $request->location_preferences,
                'bedrooms_min' => $request->bedrooms_min,
                'surface_min' => $request->surface_min,
                'additional_requirements' => $request->additional_requirements,
                'status' => 'pending',
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Demande créée avec succès',
                'data' => $searchRequest
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la création'
            ], 500);
        }
    }

    /**
     * Mes demandes (Visiteur)
     */
    public function myRequests(Request $request)
    {
        try {
            $requests = SearchRequest::with(['propertyType', 'agent'])
                ->where('user_id', $request->user()->id)
                ->orderBy('created_at', 'desc')
                ->paginate(10);

            return response()->json([
                'success' => true,
                'data' => $requests
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur'
            ], 500);
        }
    }

    /**
     * Demandes en attente (Gestionnaire)
     */
    public function pending(Request $request)
    {
        try {
            $requests = SearchRequest::with(['user', 'propertyType'])
                ->whereIn('status', ['pending', 'approved'])
                ->orderBy('created_at', 'desc')
                ->paginate(15);

            return response()->json([
                'success' => true,
                'data' => $requests
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur'
            ], 500);
        }
    }

    /**
     * Assigner à un agent (Gestionnaire)
     */
    public function assignToAgent(Request $request, $uuid)
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

        try {
            $searchRequest = SearchRequest::where('uuid', $uuid)->firstOrFail();
            $agent = User::findOrFail($request->agent_id);
            if ($agent->agent_type && $agent->agent_type !== 'immobilier') {
                return response()->json([
                    'success' => false,
                    'message' => 'Seuls les agents immobiliers peuvent etre assignes.',
                ], 422);
            }

            $searchRequest->update([
                'agent_id' => $request->agent_id,
                'status' => 'assigned',
                'assigned_at' => now(),
            ]);

            // Notification agent
            Notification::create([
                'user_id' => $request->agent_id,
                'type' => 'search_request_assigned',
                'title' => 'Nouvelle demande assignée',
                'message' => 'Une demande de recherche vous a été assignée',
                'data' => json_encode(['request_uuid' => $searchRequest->uuid]),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Demande assignée avec succès'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de l\'assignation'
            ], 500);
        }
    }

    /**
     * Approuver une demande (Gestionnaire/Admin)
     */
    public function approve(Request $request, $uuid)
    {
        try {
            $searchRequest = SearchRequest::where('uuid', $uuid)->firstOrFail();
            $searchRequest->update([
                'status' => 'approved',
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Demande approuvee'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la validation'
            ], 500);
        }
    }

    /**
     * Rejeter une demande (Gestionnaire/Admin)
     */
    public function reject(Request $request, $uuid)
    {
        try {
            $searchRequest = SearchRequest::where('uuid', $uuid)->firstOrFail();
            $searchRequest->update([
                'status' => 'rejected',
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Demande rejetee'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors du rejet'
            ], 500);
        }
    }

    /**
     * Demandes assignées (Agent)
     */
    public function assignedRequests(Request $request)
    {
        try {
            $requests = SearchRequest::with(['user', 'propertyType'])
                ->where('agent_id', $request->user()->id)
                ->whereIn('status', ['assigned', 'in_progress'])
                ->orderBy('priority', 'desc')
                ->orderBy('created_at', 'desc')
                ->paginate(15);

            return response()->json([
                'success' => true,
                'data' => $requests
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur'
            ], 500);
        }
    }

    /**
     * Marquer comme remplie (Agent)
     */
    public function fulfill(Request $request, $uuid)
    {
        try {
            $searchRequest = SearchRequest::where('uuid', $uuid)
                ->where('agent_id', $request->user()->id)
                ->firstOrFail();

            $searchRequest->update([
                'status' => 'fulfilled',
                'fulfilled_at' => now(),
            ]);

            // Notification client
            Notification::create([
                'user_id' => $searchRequest->user_id,
                'type' => 'search_request_fulfilled',
                'title' => 'Demande traitée',
                'message' => 'Votre demande de recherche a été traitée',
                'data' => json_encode(['request_uuid' => $searchRequest->uuid]),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Demande marquée comme remplie'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur'
            ], 500);
        }
    }


    /**
     * Historique des demandes (Gestionnaire)
     */
    public function managerHistory(Request $request)
    {
        try {
            $requests = SearchRequest::with(['user', 'propertyType', 'agent'])
                ->whereIn('status', ['approved', 'rejected', 'assigned', 'in_progress', 'fulfilled'])
                ->orderBy('updated_at', 'desc')
                ->paginate(15);

            return response()->json([
                'success' => true,
                'data' => $requests
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
