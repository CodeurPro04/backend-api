<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ConstructionProject;
use App\Models\InvestmentProject;
use App\Models\PartnerProduct;
use App\Models\Partnership;
use App\Support\CountryContext;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class PartnerProductController extends Controller
{
    // ─────────────────────────────────────────────────────
    // PUBLIC — produits / projets approuvés d'un partenaire
    // ─────────────────────────────────────────────────────

    /** PartnerProduct approuvés (partenaire immobilier / financier) */
    public function publicList(Request $request, string $uuid)
    {
        $partnership = Partnership::where('uuid', $uuid)
            ->where('status', 'approved')
            ->firstOrFail();

        $query = PartnerProduct::where('partnership_id', $partnership->id)
            ->approved();

        CountryContext::applyPriority($query, $request, 'partner_products');
        $products = $query
            ->orderByDesc('approved_at')
            ->get()
            ->map(fn($p) => $this->formatProduct($p));

        return response()->json(['success' => true, 'data' => $products]);
    }

    /** ConstructionProjects publiés (partenaire constructeur) */
    public function publicConstruction(Request $request, string $uuid)
    {
        $partnership = Partnership::where('uuid', $uuid)
            ->where('status', 'approved')
            ->firstOrFail();

        $query = ConstructionProject::where('user_id', $partnership->user_id)
            ->where('is_publication', true)
            ->where('status', 'published');

        CountryContext::applyPriority($query, $request, 'construction_projects');
        $projects = $query
            ->orderByDesc('created_at')
            ->get()
            ->map(fn($p) => $this->formatConstruction($p));

        return response()->json(['success' => true, 'data' => $projects]);
    }

    /** InvestmentProjects approuvés (partenaire investisseur) */
    public function publicInvestments(Request $request, string $uuid)
    {
        $partnership = Partnership::where('uuid', $uuid)
            ->where('status', 'approved')
            ->firstOrFail();

        $query = InvestmentProject::where('created_by', $partnership->user_id)
            ->where('approval_status', 'approved');

        CountryContext::applyPriority($query, $request, 'investment_projects');
        $projects = $query
            ->orderByDesc('created_at')
            ->get()
            ->map(fn($p) => $this->formatInvestment($p));

        return response()->json(['success' => true, 'data' => $projects]);
    }

    // ─────────────────────────────────────────────────────
    // ENTREPRISE — gestion de ses propres produits
    // ─────────────────────────────────────────────────────

    public function myProducts()
    {
        $partnership = $this->getMyPartnership();
        if (!$partnership) {
            return response()->json(['success' => false, 'message' => 'Aucun partenariat trouvé.'], 404);
        }

        $products = PartnerProduct::where('partnership_id', $partnership->id)
            ->withTrashed(false)
            ->orderByDesc('created_at')
            ->get()
            ->map(fn($p) => $this->formatProduct($p));

        return response()->json(['success' => true, 'data' => $products]);
    }

    public function store(Request $request)
    {
        $partnership = $this->getMyPartnership();
        if (!$partnership) {
            return response()->json(['success' => false, 'message' => 'Partenariat introuvable.'], 404);
        }
        if ($partnership->status !== 'approved') {
            return response()->json(['success' => false, 'message' => 'Votre partenariat doit être approuvé pour ajouter des produits.'], 403);
        }

        $validated = $request->validate([
            'title'            => 'required|string|max:255',
            'description'      => 'nullable|string|max:3000',
            'price'            => 'nullable|numeric|min:0',
            'currency'         => 'nullable|string|max:10',
            'type_data'        => 'nullable|string', // JSON string depuis FormData
            'images.*'         => 'nullable|image|mimes:jpg,jpeg,png,webp|max:4096',
            'plan_images.*'    => 'nullable|image|mimes:jpg,jpeg,png,webp|max:4096',
            'render_3d_images.*' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:4096',
        ]);

        // Décode type_data si envoyé comme JSON string
        $typeData = null;
        if (!empty($validated['type_data'])) {
            $typeData = json_decode($validated['type_data'], true);
        }

        $imagePaths = $this->uploadImages($request, $partnership->uuid);
        $planPaths  = $this->uploadImagesField($request, 'plan_images', $partnership->uuid, 'plans');
        $render3dPaths = $this->uploadImagesField($request, 'render_3d_images', $partnership->uuid, 'render3d');
        $allImages  = array_merge($imagePaths, $planPaths, $render3dPaths);

        $product = PartnerProduct::create([
            'uuid'           => (string) Str::uuid(),
            'country_id'     => $partnership->country_id ?: CountryContext::countryIdForUser($request->user(), $request),
            'partnership_id' => $partnership->id,
            'title'          => $validated['title'],
            'description'    => $validated['description'] ?? null,
            'price'          => $validated['price'] ?? null,
            'currency'       => $validated['currency'] ?? 'XOF',
            'type_data'      => $typeData,
            'images'         => $allImages,
            'status'         => 'pending',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Produit soumis avec succès. Il sera visible après validation.',
            'data'    => $this->formatProduct($product),
        ], 201);
    }

    public function update(Request $request, string $uuid)
    {
        $partnership = $this->getMyPartnership();
        $product = PartnerProduct::where('uuid', $uuid)
            ->where('partnership_id', $partnership?->id)
            ->firstOrFail();

        $validated = $request->validate([
            'title'              => 'sometimes|string|max:255',
            'description'        => 'nullable|string|max:3000',
            'price'              => 'nullable|numeric|min:0',
            'currency'           => 'nullable|string|max:10',
            'type_data'          => 'nullable|string',
            'images.*'           => 'nullable|image|mimes:jpg,jpeg,png,webp|max:4096',
            'plan_images.*'      => 'nullable|image|mimes:jpg,jpeg,png,webp|max:4096',
            'render_3d_images.*' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:4096',
            'remove_images'      => 'nullable|array',
        ]);

        $typeData = isset($validated['type_data'])
            ? json_decode($validated['type_data'], true)
            : $product->type_data;

        // Gestion des nouvelles images
        $currentImages = $product->images ?? [];
        if (!empty($validated['remove_images'])) {
            foreach ($validated['remove_images'] as $path) {
                Storage::disk('public')->delete($path);
                $currentImages = array_filter($currentImages, fn($i) => $i !== $path);
            }
        }
        $newImages    = $this->uploadImages($request, $partnership->uuid);
        $planPaths    = $this->uploadImagesField($request, 'plan_images', $partnership->uuid, 'plans');
        $render3dPaths = $this->uploadImagesField($request, 'render_3d_images', $partnership->uuid, 'render3d');
        $allImages = array_values(array_merge(array_values($currentImages), $newImages, $planPaths, $render3dPaths));

        $product->update([
            'title'       => $validated['title'] ?? $product->title,
            'description' => $validated['description'] ?? $product->description,
            'price'       => $validated['price'] ?? $product->price,
            'currency'    => $validated['currency'] ?? $product->currency,
            'type_data'   => $typeData,
            'images'      => $allImages,
            'status'      => 'pending', // repasse en attente après modification
            'rejection_reason' => null,
            'approved_by' => null,
            'approved_at' => null,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Produit mis à jour. Il repassera en validation.',
            'data'    => $this->formatProduct($product->fresh()),
        ]);
    }

    public function destroy(string $uuid)
    {
        $partnership = $this->getMyPartnership();
        $product = PartnerProduct::where('uuid', $uuid)
            ->where('partnership_id', $partnership?->id)
            ->firstOrFail();

        foreach ($product->images ?? [] as $path) {
            Storage::disk('public')->delete($path);
        }
        $product->delete();

        return response()->json(['success' => true, 'message' => 'Produit supprimé.']);
    }

    // ─────────────────────────────────────────────────────
    // ADMIN & GESTIONNAIRE — validation des produits
    // ─────────────────────────────────────────────────────

    public function pendingProducts()
    {
        $products = PartnerProduct::with(['partnership.user'])
            ->pending()
            ->orderBy('created_at')
            ->paginate(20)
            ->through(fn ($p) => $this->formatProductForReview($p));

        return response()->json([
            'success' => true,
            'data' => $products,
        ]);
    }

    public function allProducts(Request $request)
    {
        $query = PartnerProduct::with(['partnership', 'approver']);

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('partnership_uuid')) {
            $query->whereHas('partnership', fn($q) => $q->where('uuid', $request->partnership_uuid));
        }

        $perPage = min((int) $request->input('per_page', 20), 100) ?: 20;
        $products = $query->orderByDesc('created_at')
            ->paginate($perPage)
            ->through(fn ($p) => $this->formatProductForReview($p));

        return response()->json([
            'success' => true,
            'data' => $products,
        ]);
    }

    public function approve(string $uuid)
    {
        $product = PartnerProduct::where('uuid', $uuid)->firstOrFail();
        $product->update([
            'status'      => 'approved',
            'approved_by' => Auth::id(),
            'approved_at' => now(),
            'rejection_reason' => null,
        ]);

        return response()->json(['success' => true, 'message' => 'Produit approuvé et publié.']);
    }

    public function reject(Request $request, string $uuid)
    {
        $request->validate(['reason' => 'required|string|max:500']);

        $product = PartnerProduct::where('uuid', $uuid)->firstOrFail();
        $product->update([
            'status'           => 'rejected',
            'rejection_reason' => $request->reason,
            'approved_by'      => null,
            'approved_at'      => null,
        ]);

        return response()->json(['success' => true, 'message' => 'Produit rejeté.']);
    }

    // ─────────────────────────────────────────────────────
    // Helpers
    // ─────────────────────────────────────────────────────

    private function getMyPartnership(): ?Partnership
    {
        return Partnership::where('user_id', Auth::id())
            ->orderByDesc('created_at')
            ->first();
    }

    private function uploadImages(Request $request, string $partnerUuid): array
    {
        return $this->uploadImagesField($request, 'images', $partnerUuid, 'photos');
    }

    private function uploadImagesField(Request $request, string $field, string $partnerUuid, string $subfolder): array
    {
        $paths = [];
        if ($request->hasFile($field)) {
            foreach ($request->file($field) as $file) {
                $path = $file->store("partner_products/{$partnerUuid}/{$subfolder}", 'public');
                $paths[] = $path;
            }
        }
        return $paths;
    }

    private function formatConstruction(ConstructionProject $p): array
    {
        $toUrl = fn($path) => $path
            ? (str_starts_with($path, 'http') ? $path : asset("storage/{$path}"))
            : null;

        return [
            'uuid'        => $p->uuid,
            'kind'        => 'construction',
            'title'       => $p->title,
            'description' => $p->description,
            'city'        => $p->city,
            'location'    => $p->location,
            'surface_area'=> $p->surface_area,
            'budget_min'  => $p->budget_min,
            'budget_max'  => $p->budget_max,
            'currency'    => 'XOF',
            'images'      => array_values(array_filter(array_map($toUrl, $p->images_path ?? []))),
            'plans'       => array_values(array_filter(array_map($toUrl, $p->plans_path ?? []))),
            'render_3d'   => array_values(array_filter(array_map($toUrl, $p->render_3d_path ?? []))),
            'link'        => "/construction/{$p->uuid}",
            'created_at'  => $p->created_at->toISOString(),
        ];
    }

    private function formatInvestment(InvestmentProject $p): array
    {
        $toUrl = fn($path) => $path
            ? (str_starts_with($path, 'http') ? $path : asset("storage/{$path}"))
            : null;

        return [
            'uuid'             => $p->uuid,
            'kind'             => 'investment',
            'title'            => $p->title,
            'description'      => $p->description,
            'project_type'     => $p->project_type,
            'city'             => $p->city,
            'location'         => $p->location,
            'total_investment' => $p->total_investment,
            'min_investment'   => $p->min_investment,
            'expected_return'  => $p->expected_return,
            'duration_months'  => $p->duration_months,
            'status'           => $p->status,
            'start_date'       => $p->start_date,
            'end_date'         => $p->end_date,
            'currency'         => 'XOF',
            'images'           => array_values(array_filter(array_map($toUrl, $p->images_path ?? []))),
            'plans'            => array_values(array_filter(array_map($toUrl, $p->plans_path ?? []))),
            'render_3d'        => array_values(array_filter(array_map($toUrl, $p->render_3d_path ?? []))),
            'link'             => "/investment/{$p->uuid}",
            'created_at'       => $p->created_at->toISOString(),
        ];
    }

    // Comme formatProduct(), mais inclut aussi le partenariat (nom/type)
    // necessaire a l'ecran de validation admin/gestionnaire.
    private function formatProductForReview(PartnerProduct $product): array
    {
        return array_merge($this->formatProduct($product), [
            'partnership' => $product->partnership ? [
                'uuid'         => $product->partnership->uuid,
                'company_name' => $product->partnership->company_name,
                'company_type' => $product->partnership->company_type,
            ] : null,
        ]);
    }

    private function formatProduct(PartnerProduct $product): array
    {
        $images = array_map(
            fn($path) => str_starts_with($path, 'http') ? $path : asset("storage/{$path}"),
            $product->images ?? []
        );

        return [
            'uuid'             => $product->uuid,
            'title'            => $product->title,
            'description'      => $product->description,
            'price'            => $product->price,
            'currency'         => $product->currency,
            'type_data'        => $product->type_data,
            'images'           => $images,
            'status'           => $product->status,
            'rejection_reason' => $product->rejection_reason,
            'approved_at'      => $product->approved_at?->toISOString(),
            'created_at'       => $product->created_at->toISOString(),
        ];
    }
}
