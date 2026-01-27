<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PropertyRequest;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class PropertyRequestController extends Controller
{
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'description' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $propertyRequest = PropertyRequest::create([
            'user_id' => $request->user()->id,
            'description' => $request->description,
            'status' => 'pending',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Demande envoyee.',
            'data' => $propertyRequest,
        ], 201);
    }

    public function myRequests(Request $request)
    {
        $requests = PropertyRequest::with(['agent', 'property'])
            ->where('user_id', $request->user()->id)
            ->orderBy('created_at', 'desc')
            ->paginate(10);

        return response()->json([
            'success' => true,
            'data' => $requests,
        ]);
    }

    public function pending()
    {
        $requests = PropertyRequest::with(['user', 'agent', 'property'])
            ->whereIn('status', ['pending', 'approved'])
            ->whereNull('agent_id')
            ->orderBy('created_at', 'desc')
            ->paginate(15);

        return response()->json([
            'success' => true,
            'data' => $requests,
        ]);
    }

    public function history()
    {
        $requests = PropertyRequest::with(['user', 'agent', 'property'])
            ->whereNotIn('status', ['pending', 'approved'])
            ->orderBy('updated_at', 'desc')
            ->paginate(15);

        return response()->json([
            'success' => true,
            'data' => $requests,
        ]);
    }

    public function approve(Request $request, $uuid)
    {
        $propertyRequest = PropertyRequest::where('uuid', $uuid)->firstOrFail();

        $propertyRequest->update([
            'status' => 'approved',
            'approved_at' => now(),
            'rejected_at' => null,
            'rejection_reason' => null,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Demande approuvee.',
        ]);
    }

    public function reject(Request $request, $uuid)
    {
        $validator = Validator::make($request->all(), [
            'rejection_reason' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $propertyRequest = PropertyRequest::where('uuid', $uuid)->firstOrFail();

        $propertyRequest->update([
            'status' => 'rejected',
            'rejected_at' => now(),
            'rejection_reason' => $request->rejection_reason,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Demande rejetee.',
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
                'errors' => $validator->errors(),
            ], 422);
        }

        $propertyRequest = PropertyRequest::where('uuid', $uuid)->firstOrFail();
        $agent = User::findOrFail($request->agent_id);
        if ($agent->agent_type && $agent->agent_type !== 'immobilier') {
            return response()->json([
                'success' => false,
                'message' => 'Seuls les agents immobiliers peuvent etre assignes.',
            ], 422);
        }

        $propertyRequest->update([
            'agent_id' => $request->agent_id,
            'status' => 'assigned',
            'assigned_at' => now(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Demande assignee.',
        ]);
    }

    public function assigned(Request $request)
    {
        $requests = PropertyRequest::with(['user', 'property'])
            ->where('agent_id', $request->user()->id)
            ->whereIn('status', ['assigned', 'agent_approved'])
            ->orderBy('created_at', 'desc')
            ->paginate(15);

        return response()->json([
            'success' => true,
            'data' => $requests,
        ]);
    }

    public function agentApprove(Request $request, $uuid)
    {
        $propertyRequest = PropertyRequest::where('uuid', $uuid)
            ->where('agent_id', $request->user()->id)
            ->firstOrFail();

        $propertyRequest->update([
            'status' => 'agent_approved',
            'rejection_reason' => null,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Demande validee.',
        ]);
    }

    public function agentReject(Request $request, $uuid)
    {
        $validator = Validator::make($request->all(), [
            'rejection_reason' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $propertyRequest = PropertyRequest::where('uuid', $uuid)
            ->where('agent_id', $request->user()->id)
            ->firstOrFail();

        $propertyRequest->update([
            'status' => 'agent_rejected',
            'rejection_reason' => $request->rejection_reason,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Demande rejetee.',
        ]);
    }
}
