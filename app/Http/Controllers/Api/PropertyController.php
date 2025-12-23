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
     * Liste des propriétés (public)
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
                $query->where(function($q) use ($search) {
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
                'message' => 'Erreur lors de la récupération des propriétés',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Détails d'une propriété (public)
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

            // Incrémenter les vues
            $property->incrementViews();

            return response()->json([
                'success' => true,
                'data' => $property
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Propriété non trouvée'
            ], 404);
        }
    }

    /**
     * Créer une propriété (propriétaire)
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
            // Créer la propriété
            $property = Property::create([
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

            // Ajouter les caractéristiques
            if ($request->has('features')) {
                $property->features()->attach($request->features);
            }

            // Gérer les images
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

            // Log l'activité
            ActivityLog::create([
                'user_id' => $request->user()->id,
                'action' => 'created',
                'model_type' => 'Property',
                'model_id' => $property->id,
                'description' => 'Nouvelle propriété créée: ' . $property->title,
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'created_at' => now(),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Propriété créée avec succès. En attente de validation.',
                'data' => $property->load(['propertyType', 'media', 'features'])
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la création de la propriété',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Mes propriétés (propriétaire)
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
                'message' => 'Erreur lors de la récupération'
            ], 500);
        }
    }

    /**
     * Mettre à jour une propriété (propriétaire)
     */
    public function update(Request $request, $uuid)
    {
        try {
            $property = Property::where('uuid', $uuid)
                ->where('user_id', $request->user()->id)
                ->firstOrFail();

            // Vérifier si la propriété peut être modifiée
            if (!$property->canBeEdited()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cette propriété ne peut plus être modifiée'
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
                'title', 'description', 'property_type_id', 'transaction_type',
                'price', 'surface_area', 'bedrooms', 'bathrooms', 'address', 'city'
            ]));

            // Mettre à jour les features si fournies
            if ($request->has('features')) {
                $property->features()->sync($request->features);
            }

            return response()->json([
                'success' => true,
                'message' => 'Propriété mise à jour avec succès',
                'data' => $property->load(['propertyType', 'media', 'features'])
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la mise à jour'
            ], 500);
        }
    }

    /**
     * Supprimer une propriété (propriétaire)
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
                    'message' => 'Cette propriété ne peut pas être supprimée'
                ], 403);
            }

            // Supprimer les fichiers médias
            foreach ($property->media as $media) {
                Storage::disk('public')->delete($media->file_path);
            }

            $property->delete();

            return response()->json([
                'success' => true,
                'message' => 'Propriété supprimée avec succès'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la suppression'
            ], 500);
        }
    }

    /**
     * Propriétés en attente (gestionnaire/admin)
     */
    public function pending(Request $request)
    {
        try {
            $properties = Property::with(['propertyType', 'user', 'primaryImage'])
                ->where('status', 'pending')
                ->orderBy('created_at', 'desc')
                ->paginate(15);

            return response()->json([
                'success' => true,
                'data' => $properties
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération'
            ], 500);
        }
    }

    /**
     * Assigner une propriété à un agent (gestionnaire)
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
                'message' => 'Propriété assignée avec succès'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de l\'assignation'
            ], 500);
        }
    }

    /**
     * Valider/Rejeter une propriété (agent)
     */
    public function validate(Request $request, $uuid)
    {
        $validator = Validator::make($request->all(), [
            'status' => 'required|in:approved,rejected',
            'rejection_reason' => 'required_if:status,rejected|string',
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
                    ? 'Propriété approuvée avec succès' 
                    : 'Propriété rejetée'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la validation'
            ], 500);
        }
    }
}