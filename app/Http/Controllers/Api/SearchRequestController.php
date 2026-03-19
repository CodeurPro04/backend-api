<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Notification;
use App\Models\SearchRequest;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class SearchRequestController extends Controller
{
    private function baseRelations(): array
    {
        return [
            'user',
            'propertyType',
            'agent',
            'reports.agent',
        ];
    }

    private function notifyStaff(SearchRequest $searchRequest, string $title, string $message, array $extraData = []): void
    {
        $staffRecipients = User::whereHas('role', function ($query) {
            $query->whereIn('slug', ['gestionnaire', 'admin']);
        })->get();

        foreach ($staffRecipients as $recipient) {
            Notification::create([
                'user_id' => $recipient->id,
                'type' => 'search_request_follow_up',
                'title' => $title,
                'message' => $message,
                'data' => array_merge([
                    'request_uuid' => $searchRequest->uuid,
                    'agent_id' => $searchRequest->agent_id,
                ], $extraData),
            ]);
        }
    }

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
            Log::error('Erreur creation search request', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'payload' => $request->all(),
                'user_id' => $request->user()?->id,
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la creation de la demande.'
            ], 500);
        }
    }

    public function myRequests(Request $request)
    {
        try {
            $requests = SearchRequest::with($this->baseRelations())
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

    public function pending(Request $request)
    {
        try {
            $requests = SearchRequest::with($this->baseRelations())
                ->whereIn('status', ['pending', 'approved', 'agent_rejected'])
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
                'rejection_reason' => null,
                'rejected_at' => null,
            ]);

            try {
                Notification::create([
                    'user_id' => $request->agent_id,
                    'type' => 'search_request_assigned',
                    'title' => 'Nouvelle demande assignee',
                    'message' => 'Une demande de recherche vous a ete assignee',
                    'data' => json_encode(['request_uuid' => $searchRequest->uuid]),
                ]);
            } catch (\Throwable $notificationException) {
                Log::warning('Notification search request non envoyee apres assignation', [
                    'search_request_uuid' => $searchRequest->uuid,
                    'agent_id' => $request->agent_id,
                    'message' => $notificationException->getMessage(),
                ]);
            }

            return response()->json([
                'success' => true,
                'message' => 'Demande assignee avec succes'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de l\'assignation'
            ], 500);
        }
    }

    public function approve(Request $request, $uuid)
    {
        try {
            $searchRequest = SearchRequest::where('uuid', $uuid)->firstOrFail();
            $searchRequest->update([
                'status' => 'approved',
                'approved_at' => now(),
                'rejection_reason' => null,
                'rejected_at' => null,
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

    public function reject(Request $request, $uuid)
    {
        try {
            $searchRequest = SearchRequest::where('uuid', $uuid)->firstOrFail();
            $searchRequest->update([
                'status' => 'rejected',
                'rejected_at' => now(),
                'rejection_reason' => $request->input('rejection_reason'),
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

    public function assignedRequests(Request $request)
    {
        try {
            $requests = SearchRequest::with($this->baseRelations())
                ->where('agent_id', $request->user()->id)
                ->whereIn('status', ['assigned', 'agent_approved'])
                ->orderByDesc('assigned_at')
                ->orderByDesc('created_at')
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

    public function agentHistory(Request $request)
    {
        try {
            $requests = SearchRequest::with($this->baseRelations())
                ->where('agent_id', $request->user()->id)
                ->whereNotIn('status', ['pending', 'approved', 'assigned'])
                ->orderByDesc('updated_at')
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

    public function agentApprove(Request $request, $uuid)
    {
        $searchRequest = SearchRequest::where('uuid', $uuid)
            ->where('agent_id', $request->user()->id)
            ->where('status', 'assigned')
            ->firstOrFail();

        $searchRequest->update([
            'status' => 'agent_approved',
            'approved_at' => now(),
            'rejection_reason' => null,
            'rejected_at' => null,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Demande de recherche approuvee'
        ]);
    }

    public function agentReject(Request $request, $uuid)
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

        $searchRequest = SearchRequest::where('uuid', $uuid)
            ->where('agent_id', $request->user()->id)
            ->where('status', 'assigned')
            ->firstOrFail();

        $searchRequest->update([
            'status' => 'agent_rejected',
            'rejected_at' => now(),
            'rejection_reason' => $request->rejection_reason,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Demande de recherche refusee'
        ]);
    }

    public function addAgentReport(Request $request, $uuid)
    {
        $validator = Validator::make($request->all(), [
            'content' => 'required|string',
            'summary' => 'nullable|string',
            'client_feedback' => 'nullable|string',
            'next_step' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $searchRequest = SearchRequest::with($this->baseRelations())
            ->where('uuid', $uuid)
            ->where('agent_id', $request->user()->id)
            ->whereIn('status', ['assigned', 'agent_approved'])
            ->firstOrFail();

        $report = $searchRequest->reports()->create([
            'agent_id' => $request->user()->id,
            'report_type' => 'progress_report',
            'content' => $request->content,
            'summary' => $request->summary,
            'client_feedback' => $request->client_feedback,
            'next_step' => $request->next_step,
        ]);

        $searchRequest->touch();
        $this->notifyStaff(
            $searchRequest,
            'Nouveau rapport recherche',
            'Un agent a envoye un rapport sur une demande de recherche.',
            ['report_type' => 'progress_report']
        );

        return response()->json([
            'success' => true,
            'message' => 'Rapport enregistre',
            'data' => [
                'report' => $report->load('agent'),
                'request' => $searchRequest->fresh($this->baseRelations()),
            ],
        ], 201);
    }

    public function concludeDeal(Request $request, $uuid)
    {
        $validator = Validator::make($request->all(), [
            'content' => 'required|string',
            'closure_note' => 'required|string',
            'sale_price' => 'nullable|string|max:255',
            'next_step' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $searchRequest = SearchRequest::with($this->baseRelations())
            ->where('uuid', $uuid)
            ->where('agent_id', $request->user()->id)
            ->whereIn('status', ['assigned', 'agent_approved'])
            ->firstOrFail();

        $report = $searchRequest->reports()->create([
            'agent_id' => $request->user()->id,
            'report_type' => 'final_report',
            'content' => $request->content,
            'next_step' => $request->next_step,
            'sale_price' => $request->sale_price,
            'closure_note' => $request->closure_note,
            'concluded_at' => now(),
        ]);

        $searchRequest->update([
            'status' => 'deal_concluded',
            'deal_status' => 'deal_concluded',
            'deal_concluded_at' => now(),
            'deal_sale_price' => $request->sale_price,
            'deal_closure_note' => $request->closure_note,
            'approved_at' => $searchRequest->approved_at ?: now(),
            'fulfilled_at' => now(),
        ]);

        $this->notifyStaff(
            $searchRequest,
            'Recherche conclue',
            'Un agent a finalise une demande de recherche.',
            ['report_type' => 'final_report']
        );

        return response()->json([
            'success' => true,
            'message' => 'Demande conclue',
            'data' => [
                'report' => $report->load('agent'),
                'request' => $searchRequest->fresh($this->baseRelations()),
            ],
        ], 201);
    }

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

            Notification::create([
                'user_id' => $searchRequest->user_id,
                'type' => 'search_request_fulfilled',
                'title' => 'Demande traitee',
                'message' => 'Votre demande de recherche a ete traitee',
                'data' => json_encode(['request_uuid' => $searchRequest->uuid]),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Demande marquee comme remplie'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur'
            ], 500);
        }
    }

    public function managerHistory(Request $request)
    {
        try {
            $requests = SearchRequest::with($this->baseRelations())
                ->whereIn('status', ['approved', 'rejected', 'assigned', 'agent_approved', 'in_progress', 'fulfilled', 'deal_concluded'])
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
