<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Property;
use App\Models\PropertyMedia;
use App\Models\ActivityLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class PropertyController extends Controller
{
    /**
     * CrÃ‡Â¸er une propriÃ‡Â¸tÃ‡Â¸ (admin)
     */
    public function adminStore(Request $request)
    {
        return $this->store($request);
    }

    /**
     * Mettre Ã‡Ã¿ jour une propriÃ‡Â¸tÃ‡Â¸ (admin)
     */
    public function adminUpdate(Request $request, $uuid)
    {
        $validator = Validator::make($request->all(), [
            'title' => 'sometimes|string|max:255',
            'description' => 'sometimes|string',
            'property_type_id' => 'sometimes|exists:property_types,id',
            'transaction_type' => 'sometimes|in:vente,location',
            'price' => 'sometimes|numeric|min:0',
            'surface_area' => 'nullable|numeric|min:0',
            'bedrooms' => 'nullable|integer|min:0',
            'bathrooms' => 'nullable|integer|min:0',
            'address' => 'sometimes|string',
            'city' => 'sometimes|string|max:100',
            'status' => 'sometimes|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $property = Property::where('uuid', $uuid)->firstOrFail();
            $property->update($request->all());

            if ($request->has('features')) {
                $property->features()->sync($request->features);
            }

            return response()->json([
                'success' => true,
                'message' => 'PropriÃ‡Â¸tÃ‡Â¸ mise Ã‡Ã¿ jour',
                'data' => $property->load(['propertyType', 'media', 'features', 'user', 'agent'])
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la mise Ã‡Ã¿ jour'
            ], 500);
        }
    }
    /**
     * Liste des propriÃ©tÃ©s (public)
     */
    public function index(Request $request)
    {
        try {
            $query = Property::with(['propertyType', 'user', 'primaryImage', 'features'])
                ->approved();

            // Filtres
            if ($request->has('transaction_type')) {
                $query->where('transaction_type', $request->transaction_type);
            }

            if ($request->has('property_type_id')) {
                $query->where('property_type_id', $request->property_type_id);
            }

            if ($request->has('city')) {
                $query->where('city', 'like', '%' . $request->city . '%');
            }

            if ($request->has('min_price')) {
                $query->where('price', '>=', $request->min_price);
            }

            if ($request->has('max_price')) {
                $query->where('price', '<=', $request->max_price);
            }

            if ($request->has('bedrooms')) {
                $query->where('bedrooms', '>=', $request->bedrooms);
            }

            if ($request->has('min_surface')) {
                $query->where('surface_area', '>=', $request->min_surface);
            }

            // Recherche textuelle
            if ($request->has('search')) {
                $search = $request->search;
                $query->where(function ($q) use ($search) {
                    $q->where('title', 'like', "%{$search}%")
                        ->orWhere('description', 'like', "%{$search}%")
                        ->orWhere('address', 'like', "%{$search}%");
                });
            }

            // Tri
            $sortBy = $request->get('sort_by', 'created_at');
            $sortOrder = $request->get('sort_order', 'desc');
            $query->orderBy($sortBy, $sortOrder);

            // Pagination
            $perPage = $request->get('per_page', 12);
            $properties = $query->paginate($perPage);

            return response()->json([
                'success' => true,
                'data' => $properties
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la rÃ©cupÃ©ration des propriÃ©tÃ©s',
                'error' => $e->getMessage()
            ], 500);
        }
    }


    /**
     * Liste de toutes les propriÃ©tÃ©s pour l'admin (tous les statuts)
     */
    public function adminIndex(Request $request)
    {
        try {
            $query = Property::with(['propertyType', 'user', 'agent', 'primaryImage', 'media', 'features']);

            // Filtres
            if ($request->has('transaction_type')) {
                $query->where('transaction_type', $request->transaction_type);
            }

            if ($request->has('property_type_id')) {
                $query->where('property_type_id', $request->property_type_id);
            }

            if ($request->has('city')) {
                $query->where('city', 'like', '%' . $request->city . '%');
            }

            if ($request->has('status')) {
                $query->where('status', $request->status);
            }

            if ($request->has('min_price')) {
                $query->where('price', '>=', $request->min_price);
            }

            if ($request->has('max_price')) {
                $query->where('price', '<=', $request->max_price);
            }

            if ($request->has('bedrooms')) {
                $query->where('bedrooms', '>=', $request->bedrooms);
            }

            if ($request->has('min_surface')) {
                $query->where('surface_area', '>=', $request->min_surface);
            }

            // Recherche textuelle
            if ($request->has('search')) {
                $search = $request->search;
                $query->where(function ($q) use ($search) {
                    $q->where('title', 'like', "%{$search}%")
                        ->orWhere('description', 'like', "%{$search}%")
                        ->orWhere('address', 'like', "%{$search}%");
                });
            }

            // Tri
            $sortBy = $request->get('sort_by', 'created_at');
            $sortOrder = $request->get('sort_order', 'desc');
            $query->orderBy($sortBy, $sortOrder);

            // Pagination
            $perPage = $request->get('per_page', 12);
            $properties = $query->paginate($perPage);

            return response()->json([
                'success' => true,
                'data' => $properties
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la rÃ©cupÃ©ration des propriÃ©tÃ©s',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * DÃ©tails d'une propriÃ©tÃ© (public)
     */
    public function show($uuid)
    {
        try {
            $property = Property::with([
                'propertyType',
                'user',
                'agent',
                'media',
                'features'
            ])->where('uuid', $uuid)
                ->where('status', 'approved')
                ->firstOrFail();

            // IncrÃ©menter les vues
            $property->incrementViews();

            return response()->json([
                'success' => true,
                'data' => $property
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'PropriÃ©tÃ© non trouvÃ©e'
            ], 404);
        }
    }

    /**
     * CrÃ©er une propriÃ©tÃ© (propriÃ©taire)
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'property_type_id' => 'required|exists:property_types,id',
            'transaction_type' => 'required|in:vente,location',
            'price' => 'required|numeric|min:0',
            'currency' => 'nullable|string|max:10',
            'negotiable' => 'nullable|boolean',
            'surface_area' => 'nullable|numeric|min:0',
            'land_area' => 'nullable|numeric|min:0',
            'bedrooms' => 'nullable|integer|min:0',
            'bathrooms' => 'nullable|integer|min:0',
            'parking_spaces' => 'nullable|integer|min:0',
            'floor_number' => 'nullable|integer',
            'total_floors' => 'nullable|integer',
            'year_built' => 'nullable|integer|min:1800|max:' . date('Y'),
            'address' => 'required|string',
            'city' => 'required|string|max:100',
            'commune' => 'nullable|string|max:100',
            'quartier' => 'nullable|string|max:100',
            'latitude' => 'nullable|numeric|between:-90,90',
            'longitude' => 'nullable|numeric|between:-180,180',
            'features' => 'nullable|array',
            'features.*' => 'exists:property_features,id',
            'images' => 'required|array|min:1',
            'images.*' => 'image|mimes:jpeg,png,jpg,webp|max:5120',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            // CrÃ©er la propriÃ©tÃ©
            $property = Property::create([
                'uuid' => (string) Str::uuid(),
                'slug' => Str::slug($request->title) . '-' . Str::random(6),
                'user_id' => $request->user()->id,
                'property_type_id' => $request->property_type_id,
                'title' => $request->title,
                'description' => $request->description,
                'transaction_type' => $request->transaction_type,
                'price' => $request->price,
                'currency' => $request->get('currency', 'XOF'),
                'negotiable' => $request->get('negotiable', false),
                'surface_area' => $request->surface_area,
                'land_area' => $request->land_area,
                'bedrooms' => $request->bedrooms,
                'bathrooms' => $request->bathrooms,
                'parking_spaces' => $request->parking_spaces,
                'floor_number' => $request->floor_number,
                'total_floors' => $request->total_floors,
                'year_built' => $request->year_built,
                'address' => $request->address,
                'city' => $request->city,
                'commune' => $request->commune,
                'quartier' => $request->quartier,
                'latitude' => $request->latitude,
                'longitude' => $request->longitude,
                'status' => 'pending',
            ]);

            // Ajouter les caractÃ©ristiques
            if ($request->has('features')) {
                $property->features()->attach($request->features);
            }

            // GÃ©rer les images
            if ($request->hasFile('images')) {
                foreach ($request->file('images') as $index => $image) {
                    $path = $image->store('properties/' . $property->uuid, 'public');

                    PropertyMedia::create([
                        'property_id' => $property->id,
                        'type' => 'image',
                        'file_path' => $path,
                        'file_name' => $image->getClientOriginalName(),
                        'file_size' => $image->getSize(),
                        'mime_type' => $image->getMimeType(),
                        'order' => $index,
                        'is_primary' => $index === 0,
                    ]);
                }
            }

            // Log l'activitÃ©
            ActivityLog::create([
                'user_id' => $request->user()->id,
                'action' => 'created',
                'model_type' => 'Property',
                'model_id' => $property->id,
                'description' => 'Nouvelle propriÃ©tÃ© crÃ©Ã©e: ' . $property->title,
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'created_at' => now(),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'PropriÃ©tÃ© crÃ©Ã©e avec succÃ¨s. En attente de validation.',
                'data' => $property->load(['propertyType', 'media', 'features'])
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la crÃ©ation de la propriÃ©tÃ©',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Mes propriÃ©tÃ©s (propriÃ©taire)
     */
    public function myProperties(Request $request)
    {
        try {
            $query = Property::with(['propertyType', 'agent', 'primaryImage', 'media'])
                ->where('user_id', $request->user()->id);

            // Filtres
            if ($request->has('status')) {
                $query->where('status', $request->status);
            }

            $properties = $query->orderBy('created_at', 'desc')->paginate(10);

            return response()->json([
                'success' => true,
                'data' => $properties
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la rÃ©cupÃ©ration'
            ], 500);
        }
    }

    /**
     * DÃ‡Â¸tails d'une propriÃ‡Â¸tÃ‡Â¸ (propriÃ‡Â¸taire)
     */
    public function ownerShow(Request $request, $uuid)
    {
        try {
            $property = Property::with(['propertyType', 'agent', 'media', 'features'])
                ->where('uuid', $uuid)
                ->where('user_id', $request->user()->id)
                ->firstOrFail();

            return response()->json([
                'success' => true,
                'data' => $property
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'PropriÃ‡Â¸tÃ‡Â¸ non trouvÃ‡Â¸e'
            ], 404);
        }
    }

    /**
     * Mettre Ã  jour une propriÃ©tÃ© (propriÃ©taire)
     */
    public function update(Request $request, $uuid)
    {
        try {
            $property = Property::where('uuid', $uuid)
                ->where('user_id', $request->user()->id)
                ->firstOrFail();

            // VÃ©rifier si la propriÃ©tÃ© peut Ãªtre modifiÃ©e
            if (!$property->canBeEdited()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cette propriÃ©tÃ© ne peut plus Ãªtre modifiÃ©e'
                ], 403);
            }

            $validator = Validator::make($request->all(), [
                'title' => 'sometimes|string|max:255',
                'description' => 'sometimes|string',
                'property_type_id' => 'sometimes|exists:property_types,id',
                'transaction_type' => 'sometimes|in:vente,location',
                'price' => 'sometimes|numeric|min:0',
                'surface_area' => 'nullable|numeric|min:0',
                'bedrooms' => 'nullable|integer|min:0',
                'bathrooms' => 'nullable|integer|min:0',
                'address' => 'sometimes|string',
                'city' => 'sometimes|string|max:100',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'errors' => $validator->errors()
                ], 422);
            }

            $property->update($request->only([
                'title',
                'description',
                'property_type_id',
                'transaction_type',
                'price',
                'surface_area',
                'bedrooms',
                'bathrooms',
                'address',
                'city'
            ]));

            // Mettre Ã  jour les features si fournies
            if ($request->has('features')) {
                $property->features()->sync($request->features);
            }

            return response()->json([
                'success' => true,
                'message' => 'PropriÃ©tÃ© mise Ã  jour avec succÃ¨s',
                'data' => $property->load(['propertyType', 'media', 'features'])
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la mise Ã  jour'
            ], 500);
        }
    }

    /**
     * Supprimer une propriÃ©tÃ© (propriÃ©taire)
     */
    public function destroy(Request $request, $uuid)
    {
        try {
            $property = Property::where('uuid', $uuid)
                ->where('user_id', $request->user()->id)
                ->firstOrFail();

            if (!$property->canBeEdited()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cette propriÃ©tÃ© ne peut pas Ãªtre supprimÃ©e'
                ], 403);
            }

            // Supprimer les fichiers mÃ©dias
            foreach ($property->media as $media) {
                Storage::disk('public')->delete($media->file_path);
            }

            $property->delete();

            return response()->json([
                'success' => true,
                'message' => 'PropriÃ©tÃ© supprimÃ©e avec succÃ¨s'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la suppression'
            ], 500);
        }
    }

    /**
     * PropriÃ©tÃ©s en attente (gestionnaire/admin)
     */
    public function pending(Request $request)
    {
        try {
            $properties = Property::with(['propertyType', 'user', 'agent', 'primaryImage'])
                ->whereIn('status', ['pending', 'draft', 'rejected'])
                ->orderBy('created_at', 'desc')
                ->paginate(15);

            return response()->json([
                'success' => true,
                'data' => $properties
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la rÃ©cupÃ©ration'
            ], 500);
        }
    }

    /**
     * Assigner une propriÃ©tÃ© Ã  un agent (gestionnaire)
     */
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

        try {
            $property = Property::where('uuid', $uuid)->firstOrFail();

            $property->update([
                'agent_id' => $request->agent_id,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'PropriÃ©tÃ© assignÃ©e avec succÃ¨s'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de l\'assignation'
            ], 500);
        }
    }

    /**
     * Valider/Rejeter une propriÃ©tÃ© (agent)
     */
    public function validate(Request $request, $uuid)
    {
        $validator = Validator::make($request->all(), [
            'status' => 'required|in:approved,draft',
            'rejection_reason' => 'required_if:status,draft|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $property = Property::where('uuid', $uuid)
                ->where('agent_id', $request->user()->id)
                ->firstOrFail();

            $property->update([
                'status' => $request->status,
                'rejection_reason' => $request->rejection_reason,
                'validated_at' => now(),
                'validated_by' => $request->user()->id,
                'published_at' => $request->status === 'approved' ? now() : null,
            ]);

            return response()->json([
                'success' => true,
                'message' => $request->status === 'approved'
                    ? 'PropriÃ©tÃ© approuvÃ©e avec succÃ¨s'
                    : 'Propriete renvoyee en brouillon'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la validation'
            ], 500);
        }
    }

    /**
     * PropriÃ‡Â¸tÃ‡Â¸s assignÃ‡Â¸es (agent)
     */
    public function assignedProperties(Request $request)
    {
        try {
            $properties = Property::with(['propertyType', 'user', 'primaryImage'])
                ->where('agent_id', $request->user()->id)
                ->orderBy('created_at', 'desc')
                ->paginate(15);

            return response()->json([
                'success' => true,
                'data' => $properties
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la rÃ‡Â¸cupÃ‡Â¸ration'
            ], 500);
        }
    }

    /**
     * Rejeter une propriÃ‡Â¸tÃ‡Â¸ (agent)
     */
    public function reject(Request $request, $uuid)
    {
        $request->merge(['status' => 'draft']);
        return $this->validate($request, $uuid);
    }

    /**
     * Ajouter des images (propriÃ‡Â¸taire)
     */
    public function addImages(Request $request, $uuid)
    {
        $validator = Validator::make($request->all(), [
            'images' => 'required|array|min:1',
            'images.*' => 'image|mimes:jpeg,png,jpg,webp|max:5120',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $property = Property::where('uuid', $uuid)
                ->where('user_id', $request->user()->id)
                ->firstOrFail();

            $startIndex = $property->media()->count();

            foreach ($request->file('images') as $index => $image) {
                $path = $image->store('properties/' . $property->uuid, 'public');

                PropertyMedia::create([
                    'property_id' => $property->id,
                    'type' => 'image',
                    'file_path' => $path,
                    'file_name' => $image->getClientOriginalName(),
                    'file_size' => $image->getSize(),
                    'mime_type' => $image->getMimeType(),
                    'order' => $startIndex + $index,
                    'is_primary' => ($startIndex + $index) === 0,
                ]);
            }

            return response()->json([
                'success' => true,
                'message' => 'Images ajoutÃ‡Â¸es avec succÃ‡Ã¹s'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de l\'ajout des images'
            ], 500);
        }
    }

    /**
     * Supprimer un mÃ‡Â¸dia (propriÃ‡Â¸taire)
     */
    public function deleteMedia(Request $request, $id)
    {
        try {
            $media = PropertyMedia::where('id', $id)->firstOrFail();
            $property = Property::where('id', $media->property_id)
                ->where('user_id', $request->user()->id)
                ->firstOrFail();

            Storage::disk('public')->delete($media->file_path);
            $media->delete();

            return response()->json([
                'success' => true,
                'message' => 'MÃ‡Â¸dia supprimÃ‡Â¸'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la suppression'
            ], 500);
        }
    }

    /**
     * Afficher un media (route securisee)
     */
    public function media(Request $request, $id)
    {
        $media = PropertyMedia::with('property')->findOrFail($id);
        $property = $media->property;
        $user = $request->user();
        $role = $user?->role?->slug;

        $canAccess = false;
        if (in_array($role, ['admin', 'gestionnaire'], true)) {
            $canAccess = true;
        } elseif ($property && $property->status === 'approved') {
            $canAccess = true;
        } elseif ($property && $property->user_id === $user->id) {
            $canAccess = true;
        } elseif ($property && $property->agent_id === $user->id) {
            $canAccess = true;
        }

        if (!$canAccess) {
            return response()->json(['success' => false, 'message' => 'Acces refuse'], 403);
        }

        if (!Storage::disk('public')->exists($media->file_path)) {
            return response()->json(['success' => false, 'message' => 'Fichier introuvable'], 404);
        }

        return Storage::disk('public')->response($media->file_path);
    }

    /**
     * Media public (pour les annonces approuvees)
     */
    public function publicMedia($id)
    {
        $media = PropertyMedia::with('property')->findOrFail($id);
        $property = $media->property;

        if (!$property || $property->status !== 'approved') {
            return response()->json(['success' => false, 'message' => 'Acces refuse'], 403);
        }

        if (!Storage::disk('public')->exists($media->file_path)) {
            return response()->json(['success' => false, 'message' => 'Fichier introuvable'], 404);
        }

        return Storage::disk('public')->response($media->file_path);
    }

    /**
     * Supprimer dÃ‡Â¸finitivement (admin)
     */
    public function forceDelete($uuid)
    {
        try {
            $property = Property::withTrashed()->where('uuid', $uuid)->firstOrFail();

            foreach ($property->media as $media) {
                Storage::disk('public')->delete($media->file_path);
            }

            $property->forceDelete();

            return response()->json([
                'success' => true,
                'message' => 'PropriÃ‡Â¸tÃ‡Â¸ supprimÃ‡Â¸e dÃ‡Â¸finitivement'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la suppression'
            ], 500);
        }
    }

    /**
     * Toggle featured (admin)
     */
    public function toggleFeatured($uuid)
    {
        try {
            $property = Property::where('uuid', $uuid)->firstOrFail();
            $property->update(['featured' => !$property->featured]);

            return response()->json([
                'success' => true,
                'data' => ['featured' => $property->featured]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la mise Ã‡Ã¿ jour'
            ], 500);
        }
    }

    /**
     * PropriÃ‡Â¸tÃ‡Â¸s par type (public)
     */
    public function byType($slug)
    {
        try {
            $properties = Property::with(['propertyType', 'user', 'primaryImage'])
                ->whereHas('propertyType', fn($q) => $q->where('slug', $slug))
                ->approved()
                ->paginate(12);

            return response()->json([
                'success' => true,
                'data' => $properties
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la rÃ‡Â¸cupÃ‡Â¸ration'
            ], 500);
        }
    }

    /**
     * PropriÃ‡Â¸tÃ‡Â¸s par ville (public)
     */
    public function byCity($city)
    {
        try {
            $properties = Property::with(['propertyType', 'user', 'primaryImage'])
                ->where('city', 'like', '%' . $city . '%')
                ->approved()
                ->paginate(12);

            return response()->json([
                'success' => true,
                'data' => $properties
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la rÃ‡Â¸cupÃ‡Â¸ration'
            ], 500);
        }
    }

    /**
     * PropriÃ‡Â¸tÃ‡Â¸s featured (public)
     */
    public function featured()
    {
        try {
            $properties = Property::with(['propertyType', 'user', 'primaryImage'])
                ->featured()
                ->approved()
                ->paginate(12);

            return response()->json([
                'success' => true,
                'data' => $properties
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la rÃ‡Â¸cupÃ‡Â¸ration'
            ], 500);
        }
    }


    /**
     * Liste complete des proprietes (gestionnaire)
     */
    public function managerIndex(Request $request)
    {
        try {
            $query = Property::with(['propertyType', 'user', 'agent', 'primaryImage', 'media']);

            if ($request->has('status')) {
                $query->where('status', $request->status);
            }

            $perPage = $request->get('per_page', 15);
            $properties = $query->orderBy('created_at', 'desc')->paginate($perPage);

            return response()->json([
                'success' => true,
                'data' => $properties
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la recuperation des proprietes',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
