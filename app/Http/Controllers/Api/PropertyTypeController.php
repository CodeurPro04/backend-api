<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PropertyType;
use Illuminate\Http\Request;

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
                'message' => 'Erreur lors de la récupération des types de propriétés',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function features()
    {
        try {
            // Si vous avez un modèle PropertyFeature
            $features = \App\Models\PropertyFeature::all();
            
            return response()->json([
                'success' => true,
                'data' => $features
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération des caractéristiques',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}