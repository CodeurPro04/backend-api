<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PropertyType;
use App\Models\PropertyFeature;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class PropertyTypeController extends Controller
{
    public function index()
    {
        try {
            $types = PropertyType::active()->get();

            return response()->json([
                'success' => true,
                'data' => $types
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la recuperation des types de proprietes',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255|unique:property_types,name',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $type = PropertyType::create([
                'name' => $request->name,
                'is_active' => true,
            ]);

            return response()->json([
                'success' => true,
                'data' => $type
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la creation du type',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function update(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255|unique:property_types,name,' . $id,
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $type = PropertyType::findOrFail($id);
            $type->update(['name' => $request->name]);

            return response()->json([
                'success' => true,
                'data' => $type
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la mise a jour du type',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function destroy($id)
    {
        try {
            $type = PropertyType::findOrFail($id);
            $type->delete();

            return response()->json([
                'success' => true,
                'message' => 'Type supprime'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la suppression du type',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function features()
    {
        try {
            $features = PropertyFeature::all();

            return response()->json([
                'success' => true,
                'data' => $features
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la recuperation des caracteristiques',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function storeFeature(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255|unique:property_features,name',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $feature = PropertyFeature::create([
                'name' => $request->name,
            ]);

            return response()->json([
                'success' => true,
                'data' => $feature
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la creation de la caracteristique',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function updateFeature(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255|unique:property_features,name,' . $id,
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $feature = PropertyFeature::findOrFail($id);
            $feature->update(['name' => $request->name]);

            return response()->json([
                'success' => true,
                'data' => $feature
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la mise a jour de la caracteristique',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function destroyFeature($id)
    {
        try {
            $feature = PropertyFeature::findOrFail($id);
            $feature->delete();

            return response()->json([
                'success' => true,
                'message' => 'Caracteristique supprimee'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la suppression de la caracteristique',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
