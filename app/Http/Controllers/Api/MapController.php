<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ConstructionProject;
use App\Models\InvestmentProject;
use App\Models\Property;
use Illuminate\Http\Request;

class MapController extends Controller
{
    // Public - pins legers pour la carte interactive (page d'accueil)
    public function pins(Request $request)
    {
        $properties = Property::with('primaryImage')
            ->approved()
            ->whereNotNull('latitude')
            ->whereNotNull('longitude')
            ->get()
            ->map(function ($property) {
                return [
                    'type' => 'property',
                    'uuid' => $property->uuid,
                    'title' => $property->title,
                    'city' => $property->city,
                    'address' => $property->address,
                    'latitude' => (float) $property->latitude,
                    'longitude' => (float) $property->longitude,
                    'price' => $property->price,
                    'currency' => $property->currency,
                    'transaction_type' => $property->transaction_type,
                    'image' => $property->primaryImage->file_path ?? null,
                    'link' => "/property/{$property->uuid}",
                ];
            });

        $constructionProjects = ConstructionProject::where('status', 'published')
            ->where('is_publication', true)
            ->whereNotNull('latitude')
            ->whereNotNull('longitude')
            ->get()
            ->map(function ($project) {
                $images = is_array($project->images_path) ? $project->images_path : [];
                return [
                    'type' => 'construction',
                    'uuid' => $project->uuid,
                    'title' => $project->title,
                    'city' => $project->city,
                    'address' => $project->location,
                    'latitude' => (float) $project->latitude,
                    'longitude' => (float) $project->longitude,
                    'budget_min' => $project->budget_min,
                    'budget_max' => $project->budget_max,
                    'image' => $images[0] ?? null,
                    'link' => "/construction/{$project->uuid}",
                ];
            });

        $investmentProjects = InvestmentProject::where('approval_status', 'approved')
            ->whereNotNull('latitude')
            ->whereNotNull('longitude')
            ->get()
            ->map(function ($project) {
                $images = is_array($project->images_path) ? $project->images_path : [];
                return [
                    'type' => 'investment',
                    'uuid' => $project->uuid,
                    'title' => $project->title,
                    'city' => $project->city,
                    'address' => $project->location,
                    'latitude' => (float) $project->latitude,
                    'longitude' => (float) $project->longitude,
                    'total_investment' => $project->total_investment,
                    'expected_return' => $project->expected_return,
                    'image' => $images[0] ?? null,
                    'link' => "/investment/{$project->uuid}",
                ];
            });

        $pins = $properties
            ->concat($constructionProjects)
            ->concat($investmentProjects)
            ->values();

        return response()->json([
            'success' => true,
            'data' => $pins,
        ]);
    }
}
