<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\ClientRequest;
use App\Models\ClientRequestReport;
use App\Models\ConstructionProject;
use App\Models\InvestmentProject;
use App\Models\Notification;
use App\Models\Property;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class ClientRequestController extends Controller
{
    private function baseRelations(): array
    {
        return [
            'user',
            'property',
            'constructionProject',
            'investmentProject',
            'agent',
            'reports.agent',
        ];
    }

    private function notifyStaff(ClientRequest $requestItem, string $title, string $message, array $extraData = []): void
    {
        $staffRecipients = User::whereHas('role', function ($query) {
            $query->whereIn('slug', ['gestionnaire', 'admin']);
        })->get();

        foreach ($staffRecipients as $recipient) {
            Notification::create([
                'user_id' => $recipient->id,
                'type' => 'client_request_follow_up',
                'title' => $title,
                'message' => $message,
                'data' => array_merge([
                    'request_uuid' => $requestItem->uuid,
                    'request_type' => $requestItem->request_type,
                    'agent_id' => $requestItem->agent_id,
                ], $extraData),
            ]);
        }
    }

    private function generateVisitorPassword(): string
    {
        return 'Abi@' . strtoupper(Str::random(2)) . random_int(100000, 999999);
    }

    private function splitName(string $fullName): array
    {
        $normalized = trim(preg_replace('/\s+/', ' ', $fullName));
        if ($normalized === '') {
            return ['first_name' => 'Client', 'last_name' => 'Visiteur'];
        }

        if (str_contains($normalized, '@')) {
            $normalized = str_replace(['.', '_', '-'], ' ', Str::before($normalized, '@'));
            $normalized = trim(preg_replace('/\s+/', ' ', $normalized));
        }

        $parts = explode(' ', $normalized, 2);

        return [
            'first_name' => $parts[0] ?? 'Client',
            'last_name' => $parts[1] ?? 'Visiteur',
        ];
    }

    private function createVisitorAccount(Request $request, string $name, string $email, ?string $phone): array
    {
        $existingUser = User::where('email', $email)->first();
        if ($existingUser) {
            return [
                'user' => $existingUser,
                'account' => null,
                'message' => 'Votre demande a bien ete envoyee. Cet email est deja associe a un compte, vous pouvez donc vous connecter avec vos identifiants habituels.',
            ];
        }

        $role = Role::where('slug', 'visiteur')->first();
        if (!$role) {
            abort(response()->json([
                'success' => false,
                'message' => 'Le role visiteur est introuvable.',
            ], 500));
        }

        $defaultPassword = $this->generateVisitorPassword();
        $nameParts = $this->splitName($name);

        $user = User::create([
            'first_name' => $nameParts['first_name'],
            'last_name' => $nameParts['last_name'],
            'email' => $email,
            'phone' => $phone,
            'password' => Hash::make($defaultPassword),
            'role_id' => $role->id,
            'is_active' => true,
        ]);

        ActivityLog::create([
            'user_id' => $user->id,
            'action' => 'registered',
            'description' => 'Compte visiteur cree automatiquement depuis un formulaire de demande publique',
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'created_at' => now(),
        ]);

        return [
            'user' => $user,
            'account' => [
                'email' => $user->email,
                'default_password' => $defaultPassword,
                'requires_activation' => false,
            ],
            'message' => 'Votre demande a bien ete envoyee et votre compte visiteur a ete cree.',
        ];
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'property_id' => 'nullable|exists:properties,id',
            'property_uuid' => 'nullable|exists:properties,uuid',
            'construction_project_id' => 'nullable|exists:construction_projects,id',
            'investment_project_id' => 'nullable|exists:investment_projects,id',
            'construction_uuid' => 'nullable|exists:construction_projects,uuid',
            'investment_uuid' => 'nullable|exists:investment_projects,uuid',
            'request_type' => 'nullable|in:immobilier,construction,investissement',
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
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

        $propertyId = $request->property_id;
        if (!$propertyId && $request->filled('property_uuid')) {
            $propertyId = Property::where('uuid', $request->property_uuid)->value('id');
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

        DB::beginTransaction();

        try {
            $user = $request->user();
            $createdAccount = null;
            $responseMessage = 'Demande envoyee';

            if (!$user) {
                $account = $this->createVisitorAccount(
                    $request,
                    $request->name,
                    $request->email,
                    $request->phone
                );

                $user = $account['user'];
                $createdAccount = $account['account'];
                $responseMessage = $account['message'] ?? $responseMessage;
            }

            $clientRequest = ClientRequest::create([
                'user_id' => $user?->id,
                'property_id' => $propertyId,
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

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => $responseMessage,
                'data' => $clientRequest,
                'account' => $createdAccount,
            ], 201);
        } catch (\Symfony\Component\HttpKernel\Exception\HttpException $exception) {
            DB::rollBack();
            throw $exception;
        } catch (\Throwable $exception) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de l\'envoi de la demande.',
                'error' => $exception->getMessage(),
            ], 500);
        }
    }

    public function pending()
    {
        $requests = ClientRequest::with($this->baseRelations())
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
        $requests = ClientRequest::with($this->baseRelations())
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

    // AGENT - approuver une demande client assignee
    public function agentApprove(Request $request, $uuid)
    {
        $requestItem = ClientRequest::where('uuid', $uuid)
            ->where('agent_id', $request->user()->id)
            ->where('status', 'assigned')
            ->firstOrFail();

        $requestItem->update([
            'status' => 'agent_approved',
            'approved_at' => now(),
            'rejection_reason' => null,
            'rejected_at' => null,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Demande client approuvee'
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

        $requestItem = ClientRequest::with($this->baseRelations())
            ->where('uuid', $uuid)
            ->where('agent_id', $request->user()->id)
            ->whereIn('status', ['assigned', 'agent_approved'])
            ->firstOrFail();

        $report = $requestItem->reports()->create([
            'agent_id' => $request->user()->id,
            'report_type' => 'progress_report',
            'content' => $request->content,
            'summary' => $request->summary,
            'client_feedback' => $request->client_feedback,
            'next_step' => $request->next_step,
        ]);

        $requestItem->touch();
        $this->notifyStaff(
            $requestItem,
            'Nouveau rapport agent',
            $requestItem->name ?: 'Un agent a envoye un rapport de suivi client.',
            ['report_type' => 'progress_report']
        );

        return response()->json([
            'success' => true,
            'message' => 'Rapport enregistre',
            'data' => [
                'report' => $report->load('agent'),
                'request' => $requestItem->fresh($this->baseRelations()),
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

        $requestItem = ClientRequest::with($this->baseRelations())
            ->where('uuid', $uuid)
            ->where('agent_id', $request->user()->id)
            ->whereIn('status', ['assigned', 'agent_approved'])
            ->firstOrFail();

        $report = $requestItem->reports()->create([
            'agent_id' => $request->user()->id,
            'report_type' => 'final_report',
            'content' => $request->content,
            'next_step' => $request->next_step,
            'sale_price' => $request->sale_price,
            'closure_note' => $request->closure_note,
            'concluded_at' => now(),
        ]);

        $requestItem->update([
            'status' => 'deal_concluded',
            'deal_status' => 'deal_concluded',
            'deal_concluded_at' => now(),
            'deal_sale_price' => $request->sale_price,
            'deal_closure_note' => $request->closure_note,
            'approved_at' => $requestItem->approved_at ?: now(),
        ]);

        $this->notifyStaff(
            $requestItem,
            'Offre client conclue',
            $requestItem->name ?: 'Un agent a conclut une offre client.',
            ['report_type' => 'final_report']
        );

        return response()->json([
            'success' => true,
            'message' => 'Offre conclue',
            'data' => [
                'report' => $report->load('agent'),
                'request' => $requestItem->fresh($this->baseRelations()),
            ],
        ], 201);
    }

    // AGENT - rejeter une demande client assignee
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

        $requestItem = ClientRequest::where('uuid', $uuid)
            ->where('agent_id', $request->user()->id)
            ->where('status', 'assigned')
            ->firstOrFail();

        $requestItem->update([
            'status' => 'agent_rejected',
            'rejected_at' => now(),
            'rejection_reason' => $request->rejection_reason,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Demande client refusee'
        ]);
    }
    // AGENT - demandes clients assignees
    public function agentAssigned(Request $request)
    {
        $requests = ClientRequest::with($this->baseRelations())
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
        $requests = ClientRequest::with($this->baseRelations())
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

