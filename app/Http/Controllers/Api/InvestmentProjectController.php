<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\InvestmentProject;
use App\Models\InvestmentProposal;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;

class InvestmentProjectController extends Controller
{
    // Liste publique des projets
    public function index(Request $request)
    {
        $projects = InvestmentProject::where('approval_status', 'approved')
            ->orderBy('created_at', 'desc')
            ->paginate(12);

        return response()->json([
            'success' => true,
            'data' => $projects
        ]);
    }

    // DÃ©tails d'un projet
    public function show($uuid)
    {
        $project = InvestmentProject::where('uuid', $uuid)
            ->where('approval_status', 'approved')
            ->firstOrFail();
        return response()->json([
            'success' => true,
            'data' => $project
        ]);
    }

    // Admin/Gestionnaire - liste de tous les projets
    public function staffIndex()
    {
        $projects = InvestmentProject::orderBy('updated_at', 'desc')->paginate(20);
        return response()->json([
            'success' => true,
            'data' => $projects
        ]);
    }

    // Investisseur - proposer un investissement
    public function propose(Request $request, $uuid)
    {
        $validated = $request->validate([
            'amount' => 'required|numeric|min:1',
            'message' => 'nullable|string',
        ]);

        $project = InvestmentProject::where('uuid', $uuid)->firstOrFail();

        $proposal = InvestmentProposal::create([
            'uuid' => (string) Str::uuid(),
            'user_id' => $request->user()->id,
            'investment_project_id' => $project->id,
            'amount' => $validated['amount'],
            'message' => $validated['message'] ?? null,
            'status' => 'pending',
        ]);

        return response()->json([
            'success' => true,
            'data' => $proposal
        ], 201);
    }

    // Investisseur - mes propositions
    public function myProposals(Request $request)
    {
        $proposals = InvestmentProposal::with('investmentProject')
            ->where('user_id', $request->user()->id)
            ->orderBy('created_at', 'desc')
            ->paginate(10);

        return response()->json([
            'success' => true,
            'data' => $proposals
        ]);
    }

    // Investisseur - dÃ©tails proposition
    public function proposalDetails($uuid)
    {
        $proposal = InvestmentProposal::with('investmentProject')->where('uuid', $uuid)->firstOrFail();
        return response()->json([
            'success' => true,
            'data' => $proposal
        ]);
    }

    // Admin - crÃ©er projet
    public function create(Request $request)
    {
        if ($request->has('featured')) {
            $request->merge([
                'featured' => filter_var($request->input('featured'), FILTER_VALIDATE_BOOLEAN)
            ]);
        }
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'project_type' => 'required|in:immobilier,construction,renovation',
            'location' => 'nullable|string|max:255',
            'city' => 'nullable|string|max:100',
            'reference_code' => 'nullable|string|max:50',
            'postal_code' => 'nullable|string|max:20',
            'surface_area' => 'nullable|numeric',
            'total_investment' => 'nullable|numeric',
            'min_investment' => 'nullable|numeric',
            'expected_return' => 'nullable|numeric',
            'duration_months' => 'nullable|integer',
            'status' => 'nullable|in:open,in_progress,closed,completed',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'documents_path' => 'nullable|array',
            'documents_path.*' => 'nullable|string',
            'images_path' => 'nullable|array',
            'images_path.*' => 'nullable|string',
            'plans_path' => 'nullable|array',
            'plans_path.*' => 'nullable|string',
            'render_3d_path' => 'nullable|array',
            'render_3d_path.*' => 'nullable|string',
            'documents' => 'nullable|array',
            'documents.*' => 'file|mimes:pdf,doc,docx,xls,xlsx,ppt,pptx',
            'images' => 'nullable|array',
            'images.*' => 'image|mimes:jpg,jpeg,png,webp',
            'plans' => 'nullable|array',
            'plans.*' => 'file|mimes:jpg,jpeg,png,webp,pdf',
            'render_3d' => 'nullable|array',
            'render_3d.*' => 'file|mimes:jpg,jpeg,png,webp,pdf',
            'remove_documents' => 'nullable|array',
            'remove_documents.*' => 'string',
            'remove_images' => 'nullable|array',
            'remove_images.*' => 'string',
            'remove_plans' => 'nullable|array',
            'remove_plans.*' => 'string',
            'remove_render_3d' => 'nullable|array',
            'remove_render_3d.*' => 'string',
            'featured' => 'nullable|boolean',
        ]);

        $project = InvestmentProject::create([
            'uuid' => (string) Str::uuid(),
            'created_by' => $request->user()->id,
            'title' => $validated['title'],
            'description' => $validated['description'] ?? null,
            'project_type' => $validated['project_type'] ?? null,
            'location' => $validated['location'] ?? null,
            'city' => $validated['city'] ?? null,
            'reference_code' => $validated['reference_code'] ?? null,
            'postal_code' => $validated['postal_code'] ?? null,
            'surface_area' => $validated['surface_area'] ?? null,
            'total_investment' => $validated['total_investment'] ?? null,
            'min_investment' => $validated['min_investment'] ?? null,
            'expected_return' => $validated['expected_return'] ?? null,
            'duration_months' => $validated['duration_months'] ?? null,
            'status' => $validated['status'] ?? 'open',
            'approval_status' => 'approved',
            'start_date' => $validated['start_date'] ?? null,
            'end_date' => $validated['end_date'] ?? null,
            'documents_path' => $validated['documents_path'] ?? null,
            'images_path' => $validated['images_path'] ?? null,
            'plans_path' => $validated['plans_path'] ?? null,
            'render_3d_path' => $validated['render_3d_path'] ?? null,
            'featured' => $validated['featured'] ?? false,
        ]);

        $documentPaths = $validated['documents_path'] ?? [];
        $imagePaths = $validated['images_path'] ?? [];
        $planPaths = $validated['plans_path'] ?? [];
        $render3DPaths = $validated['render_3d_path'] ?? [];

        if ($request->hasFile('documents')) {
            foreach ($request->file('documents') as $file) {
                $documentPaths[] = $file->store("investments/{$project->uuid}/documents", 'public');
            }
        }

        if ($request->hasFile('images')) {
            foreach ($request->file('images') as $file) {
                $imagePaths[] = $file->store("investments/{$project->uuid}/images", 'public');
            }
        }

        if ($request->hasFile('plans')) {
            foreach ($request->file('plans') as $file) {
                $planPaths[] = $file->store("investments/{$project->uuid}/plans", 'public');
            }
        }

        if ($request->hasFile('render_3d')) {
            foreach ($request->file('render_3d') as $file) {
                $render3DPaths[] = $file->store("investments/{$project->uuid}/render-3d", 'public');
            }
        }

        $project->update([
            'documents_path' => !empty($documentPaths) ? $documentPaths : null,
            'images_path' => !empty($imagePaths) ? $imagePaths : null,
            'plans_path' => !empty($planPaths) ? $planPaths : null,
            'render_3d_path' => !empty($render3DPaths) ? $render3DPaths : null,
        ]);

        return response()->json([
            'success' => true,
            'data' => $project
        ], 201);
    }

    // Admin - update projet
    public function update(Request $request, $uuid)
    {
        if ($request->has('featured')) {
            $request->merge([
                'featured' => filter_var($request->input('featured'), FILTER_VALIDATE_BOOLEAN)
            ]);
        }
        $project = InvestmentProject::where('uuid', $uuid)->firstOrFail();
        $validated = $request->validate([
            'title' => 'sometimes|required|string|max:255',
            'description' => 'nullable|string',
            'project_type' => 'sometimes|required|in:immobilier,construction,renovation',
            'location' => 'nullable|string|max:255',
            'city' => 'nullable|string|max:100',
            'reference_code' => 'nullable|string|max:50',
            'postal_code' => 'nullable|string|max:20',
            'surface_area' => 'nullable|numeric',
            'total_investment' => 'nullable|numeric',
            'min_investment' => 'nullable|numeric',
            'expected_return' => 'nullable|numeric',
            'duration_months' => 'nullable|integer',
            'status' => 'nullable|in:open,in_progress,closed,completed',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'documents_path' => 'nullable|array',
            'documents_path.*' => 'nullable|string',
            'images_path' => 'nullable|array',
            'images_path.*' => 'nullable|string',
            'plans_path' => 'nullable|array',
            'plans_path.*' => 'nullable|string',
            'render_3d_path' => 'nullable|array',
            'render_3d_path.*' => 'nullable|string',
            'documents' => 'nullable|array',
            'documents.*' => 'file|mimes:pdf,doc,docx,xls,xlsx,ppt,pptx',
            'images' => 'nullable|array',
            'images.*' => 'image|mimes:jpg,jpeg,png,webp',
            'plans' => 'nullable|array',
            'plans.*' => 'file|mimes:jpg,jpeg,png,webp,pdf',
            'render_3d' => 'nullable|array',
            'render_3d.*' => 'file|mimes:jpg,jpeg,png,webp,pdf',
            'remove_documents' => 'nullable|array',
            'remove_documents.*' => 'string',
            'remove_images' => 'nullable|array',
            'remove_images.*' => 'string',
            'remove_plans' => 'nullable|array',
            'remove_plans.*' => 'string',
            'remove_render_3d' => 'nullable|array',
            'remove_render_3d.*' => 'string',
            'featured' => 'nullable|boolean',
        ]);
        $payload = $validated;
        unset(
            $payload['documents'],
            $payload['images'],
            $payload['plans'],
            $payload['render_3d'],
            $payload['remove_documents'],
            $payload['remove_images'],
            $payload['remove_plans'],
            $payload['remove_render_3d']
        );
        $payload['approval_status'] = 'approved';
        $payload['rejection_reason'] = null;
        $project->update($payload);

        $documentPaths = $validated['documents_path'] ?? ($project->documents_path ?? []);
        $imagePaths = $validated['images_path'] ?? ($project->images_path ?? []);
        $planPaths = $validated['plans_path'] ?? ($project->plans_path ?? []);
        $render3DPaths = $validated['render_3d_path'] ?? ($project->render_3d_path ?? []);

        $removeDocuments = $validated['remove_documents'] ?? [];
        $removeImages = $validated['remove_images'] ?? [];
        $removePlans = $validated['remove_plans'] ?? [];
        $removeRender3D = $validated['remove_render_3d'] ?? [];

        if (!empty($removeDocuments)) {
            foreach ($removeDocuments as $path) {
                Storage::disk('public')->delete($path);
            }
            $documentPaths = array_values(array_diff($documentPaths, $removeDocuments));
        }

        if (!empty($removeImages)) {
            foreach ($removeImages as $path) {
                Storage::disk('public')->delete($path);
            }
            $imagePaths = array_values(array_diff($imagePaths, $removeImages));
        }

        if (!empty($removePlans)) {
            foreach ($removePlans as $path) {
                Storage::disk('public')->delete($path);
            }
            $planPaths = array_values(array_diff($planPaths, $removePlans));
        }

        if (!empty($removeRender3D)) {
            foreach ($removeRender3D as $path) {
                Storage::disk('public')->delete($path);
            }
            $render3DPaths = array_values(array_diff($render3DPaths, $removeRender3D));
        }

        if ($request->hasFile('documents')) {
            foreach ($request->file('documents') as $file) {
                $documentPaths[] = $file->store("investments/{$project->uuid}/documents", 'public');
            }
        }

        if ($request->hasFile('images')) {
            foreach ($request->file('images') as $file) {
                $imagePaths[] = $file->store("investments/{$project->uuid}/images", 'public');
            }
        }

        if ($request->hasFile('plans')) {
            foreach ($request->file('plans') as $file) {
                $planPaths[] = $file->store("investments/{$project->uuid}/plans", 'public');
            }
        }

        if ($request->hasFile('render_3d')) {
            foreach ($request->file('render_3d') as $file) {
                $render3DPaths[] = $file->store("investments/{$project->uuid}/render-3d", 'public');
            }
        }

        $project->update([
            'documents_path' => !empty($documentPaths) ? $documentPaths : null,
            'images_path' => !empty($imagePaths) ? $imagePaths : null,
            'plans_path' => !empty($planPaths) ? $planPaths : null,
            'render_3d_path' => !empty($render3DPaths) ? $render3DPaths : null,
        ]);

        return response()->json([
            'success' => true,
            'data' => $project
        ]);
    }

    // Admin/Gestionnaire - approuver un projet
    public function approveProject($uuid)
    {
        $project = InvestmentProject::where('uuid', $uuid)->firstOrFail();
        $project->update([
            'approval_status' => 'approved',
            'rejection_reason' => null,
        ]);
        return response()->json(['success' => true]);
    }

    // Admin/Gestionnaire - rejeter un projet
    public function rejectProject(Request $request, $uuid)
    {
        $project = InvestmentProject::where('uuid', $uuid)->firstOrFail();
        $validated = $request->validate([
            'rejection_reason' => 'required|string',
        ]);
        $project->update([
            'approval_status' => 'rejected',
            'rejection_reason' => $validated['rejection_reason'],
        ]);
        return response()->json(['success' => true]);
    }

    // AGENT - creer un projet d'investissement (en attente)
    public function agentCreate(Request $request)
    {
        if ($request->user()?->agent_type && $request->user()->agent_type !== 'investissement') {
            return response()->json([
                'success' => false,
                'message' => 'Acces reserve aux agents investissement.',
            ], 403);
        }
        if ($request->has('featured')) {
            $request->merge([
                'featured' => filter_var($request->input('featured'), FILTER_VALIDATE_BOOLEAN)
            ]);
        }
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'project_type' => 'required|in:immobilier,construction,renovation',
            'location' => 'nullable|string|max:255',
            'city' => 'nullable|string|max:100',
            'reference_code' => 'nullable|string|max:50',
            'postal_code' => 'nullable|string|max:20',
            'surface_area' => 'nullable|numeric',
            'total_investment' => 'nullable|numeric',
            'min_investment' => 'nullable|numeric',
            'expected_return' => 'nullable|numeric',
            'duration_months' => 'nullable|integer',
            'status' => 'nullable|in:open,in_progress,closed,completed',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'documents_path' => 'nullable|array',
            'documents_path.*' => 'nullable|string',
            'images_path' => 'nullable|array',
            'images_path.*' => 'nullable|string',
            'plans_path' => 'nullable|array',
            'plans_path.*' => 'nullable|string',
            'render_3d_path' => 'nullable|array',
            'render_3d_path.*' => 'nullable|string',
            'documents' => 'nullable|array',
            'documents.*' => 'file|mimes:pdf,doc,docx,xls,xlsx,ppt,pptx',
            'images' => 'nullable|array',
            'images.*' => 'image|mimes:jpg,jpeg,png,webp',
            'plans' => 'nullable|array',
            'plans.*' => 'file|mimes:jpg,jpeg,png,webp,pdf',
            'render_3d' => 'nullable|array',
            'render_3d.*' => 'file|mimes:jpg,jpeg,png,webp,pdf',
            'featured' => 'nullable|boolean',
        ]);

        $project = InvestmentProject::create([
            'uuid' => (string) Str::uuid(),
            'created_by' => $request->user()->id,
            'title' => $validated['title'],
            'description' => $validated['description'] ?? null,
            'project_type' => $validated['project_type'] ?? null,
            'location' => $validated['location'] ?? null,
            'city' => $validated['city'] ?? null,
            'reference_code' => $validated['reference_code'] ?? null,
            'postal_code' => $validated['postal_code'] ?? null,
            'surface_area' => $validated['surface_area'] ?? null,
            'total_investment' => $validated['total_investment'] ?? null,
            'min_investment' => $validated['min_investment'] ?? null,
            'expected_return' => $validated['expected_return'] ?? null,
            'duration_months' => $validated['duration_months'] ?? null,
            'status' => $validated['status'] ?? 'open',
            'approval_status' => 'pending',
            'start_date' => $validated['start_date'] ?? null,
            'end_date' => $validated['end_date'] ?? null,
            'documents_path' => $validated['documents_path'] ?? null,
            'images_path' => $validated['images_path'] ?? null,
            'plans_path' => $validated['plans_path'] ?? null,
            'render_3d_path' => $validated['render_3d_path'] ?? null,
            'featured' => $validated['featured'] ?? false,
        ]);

        $documentPaths = $validated['documents_path'] ?? [];
        $imagePaths = $validated['images_path'] ?? [];
        $planPaths = $validated['plans_path'] ?? [];
        $render3DPaths = $validated['render_3d_path'] ?? [];

        if ($request->hasFile('documents')) {
            foreach ($request->file('documents') as $file) {
                $documentPaths[] = $file->store("investments/{$project->uuid}/documents", 'public');
            }
        }

        if ($request->hasFile('images')) {
            foreach ($request->file('images') as $file) {
                $imagePaths[] = $file->store("investments/{$project->uuid}/images", 'public');
            }
        }

        if ($request->hasFile('plans')) {
            foreach ($request->file('plans') as $file) {
                $planPaths[] = $file->store("investments/{$project->uuid}/plans", 'public');
            }
        }

        if ($request->hasFile('render_3d')) {
            foreach ($request->file('render_3d') as $file) {
                $render3DPaths[] = $file->store("investments/{$project->uuid}/render-3d", 'public');
            }
        }

        $project->update([
            'documents_path' => !empty($documentPaths) ? $documentPaths : null,
            'images_path' => !empty($imagePaths) ? $imagePaths : null,
            'plans_path' => !empty($planPaths) ? $planPaths : null,
            'render_3d_path' => !empty($render3DPaths) ? $render3DPaths : null,
        ]);

        return response()->json([
            'success' => true,
            'data' => $project
        ], 201);
    }

    // AGENT - mise a jour d'un projet (repasse en attente)
    public function agentUpdate(Request $request, $uuid)
    {
        if ($request->user()?->agent_type && $request->user()->agent_type !== 'investissement') {
            return response()->json([
                'success' => false,
                'message' => 'Acces reserve aux agents investissement.',
            ], 403);
        }
        if ($request->has('featured')) {
            $request->merge([
                'featured' => filter_var($request->input('featured'), FILTER_VALIDATE_BOOLEAN)
            ]);
        }
        $project = InvestmentProject::where('uuid', $uuid)
            ->where('created_by', $request->user()->id)
            ->firstOrFail();

        $validated = $request->validate([
            'title' => 'sometimes|required|string|max:255',
            'description' => 'nullable|string',
            'project_type' => 'sometimes|required|in:immobilier,construction,renovation',
            'location' => 'nullable|string|max:255',
            'city' => 'nullable|string|max:100',
            'reference_code' => 'nullable|string|max:50',
            'postal_code' => 'nullable|string|max:20',
            'surface_area' => 'nullable|numeric',
            'total_investment' => 'nullable|numeric',
            'min_investment' => 'nullable|numeric',
            'expected_return' => 'nullable|numeric',
            'duration_months' => 'nullable|integer',
            'status' => 'nullable|in:open,in_progress,closed,completed',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'documents_path' => 'nullable|array',
            'documents_path.*' => 'nullable|string',
            'images_path' => 'nullable|array',
            'images_path.*' => 'nullable|string',
            'plans_path' => 'nullable|array',
            'plans_path.*' => 'nullable|string',
            'render_3d_path' => 'nullable|array',
            'render_3d_path.*' => 'nullable|string',
            'documents' => 'nullable|array',
            'documents.*' => 'file|mimes:pdf,doc,docx,xls,xlsx,ppt,pptx',
            'images' => 'nullable|array',
            'images.*' => 'image|mimes:jpg,jpeg,png,webp',
            'plans' => 'nullable|array',
            'plans.*' => 'file|mimes:jpg,jpeg,png,webp,pdf',
            'render_3d' => 'nullable|array',
            'render_3d.*' => 'file|mimes:jpg,jpeg,png,webp,pdf',
            'remove_documents' => 'nullable|array',
            'remove_documents.*' => 'string',
            'remove_images' => 'nullable|array',
            'remove_images.*' => 'string',
            'remove_plans' => 'nullable|array',
            'remove_plans.*' => 'string',
            'remove_render_3d' => 'nullable|array',
            'remove_render_3d.*' => 'string',
            'featured' => 'nullable|boolean',
        ]);

        $payload = $validated;
        unset(
            $payload['documents'],
            $payload['images'],
            $payload['plans'],
            $payload['render_3d'],
            $payload['remove_documents'],
            $payload['remove_images'],
            $payload['remove_plans'],
            $payload['remove_render_3d']
        );
        $payload['approval_status'] = 'pending';
        $payload['rejection_reason'] = null;
        $project->update($payload);

        $documentPaths = $validated['documents_path'] ?? ($project->documents_path ?? []);
        $imagePaths = $validated['images_path'] ?? ($project->images_path ?? []);
        $planPaths = $validated['plans_path'] ?? ($project->plans_path ?? []);
        $render3DPaths = $validated['render_3d_path'] ?? ($project->render_3d_path ?? []);
        $removeDocuments = $validated['remove_documents'] ?? [];
        $removeImages = $validated['remove_images'] ?? [];
        $removePlans = $validated['remove_plans'] ?? [];
        $removeRender3D = $validated['remove_render_3d'] ?? [];

        if (!empty($removeDocuments)) {
            foreach ($removeDocuments as $path) {
                Storage::disk('public')->delete($path);
            }
            $documentPaths = array_values(array_diff($documentPaths, $removeDocuments));
        }

        if (!empty($removeImages)) {
            foreach ($removeImages as $path) {
                Storage::disk('public')->delete($path);
            }
            $imagePaths = array_values(array_diff($imagePaths, $removeImages));
        }

        if (!empty($removePlans)) {
            foreach ($removePlans as $path) {
                Storage::disk('public')->delete($path);
            }
            $planPaths = array_values(array_diff($planPaths, $removePlans));
        }

        if (!empty($removeRender3D)) {
            foreach ($removeRender3D as $path) {
                Storage::disk('public')->delete($path);
            }
            $render3DPaths = array_values(array_diff($render3DPaths, $removeRender3D));
        }

        if ($request->hasFile('documents')) {
            foreach ($request->file('documents') as $file) {
                $documentPaths[] = $file->store("investments/{$project->uuid}/documents", 'public');
            }
        }

        if ($request->hasFile('images')) {
            foreach ($request->file('images') as $file) {
                $imagePaths[] = $file->store("investments/{$project->uuid}/images", 'public');
            }
        }

        if ($request->hasFile('plans')) {
            foreach ($request->file('plans') as $file) {
                $planPaths[] = $file->store("investments/{$project->uuid}/plans", 'public');
            }
        }

        if ($request->hasFile('render_3d')) {
            foreach ($request->file('render_3d') as $file) {
                $render3DPaths[] = $file->store("investments/{$project->uuid}/render-3d", 'public');
            }
        }

        $project->update([
            'documents_path' => !empty($documentPaths) ? $documentPaths : null,
            'images_path' => !empty($imagePaths) ? $imagePaths : null,
            'plans_path' => !empty($planPaths) ? $planPaths : null,
            'render_3d_path' => !empty($render3DPaths) ? $render3DPaths : null,
        ]);

        return response()->json([
            'success' => true,
            'data' => $project
        ]);
    }

    // AGENT - mes projets
    public function agentPublications(Request $request)
    {
        if ($request->user()?->agent_type && $request->user()->agent_type !== 'investissement') {
            return response()->json([
                'success' => false,
                'message' => 'Acces reserve aux agents investissement.',
            ], 403);
        }
        $projects = InvestmentProject::where('created_by', $request->user()->id)
            ->orderBy('updated_at', 'desc')
            ->paginate(20);

        return response()->json([
            'success' => true,
            'data' => $projects
        ]);
    }

    // Admin - delete projet
    public function destroy($uuid)
    {
        $project = InvestmentProject::where('uuid', $uuid)->firstOrFail();
        $project->delete();
        return response()->json(['success' => true]);
    }

    // Admin - toutes les propositions
    public function allProposals()
    {
        $proposals = InvestmentProposal::with(['investmentProject', 'user'])
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return response()->json([
            'success' => true,
            'data' => $proposals
        ]);
    }

    // Admin - approuver
    public function approveProposal($uuid)
    {
        $proposal = InvestmentProposal::where('uuid', $uuid)->firstOrFail();
        $proposal->update([
            'status' => 'approved',
            'reviewed_by' => auth()->id(),
            'reviewed_at' => now()
        ]);

        return response()->json(['success' => true]);
    }

    // Admin - rejeter
    public function rejectProposal(Request $request, $uuid)
    {
        $request->validate([
            'rejection_reason' => 'required|string',
        ]);

        $proposal = InvestmentProposal::where('uuid', $uuid)->firstOrFail();
        $proposal->update([
            'status' => 'rejected',
            'rejection_reason' => $request->rejection_reason,
            'reviewed_by' => auth()->id(),
            'reviewed_at' => now()
        ]);

        return response()->json(['success' => true]);
    }
}














