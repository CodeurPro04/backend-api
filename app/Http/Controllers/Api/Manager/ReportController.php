<?php

namespace App\Http\Controllers\Api\Manager;

use App\Http\Controllers\Controller;
use App\Models\Property;
use App\Models\SearchRequest;
use App\Models\ConstructionProject;
use App\Models\User;

class ReportController extends Controller
{
    public function index()
    {
        $propertiesTotal = Property::count();
        $propertiesPending = Property::where('status', 'pending')->count();
        $propertiesAssigned = Property::whereNotNull('agent_id')->count();

        $searchPending = SearchRequest::where('status', 'pending')->count();
        $searchAssigned = SearchRequest::whereIn('status', ['assigned', 'in_progress', 'fulfilled'])->count();

        $constructionPending = ConstructionProject::where('status', 'submitted')->count();
        $constructionAssigned = ConstructionProject::whereIn('status', ['in_study', 'in_progress', 'completed', 'closed'])->count();

        $agentsActive = User::whereHas('role', function ($query) {
            $query->where('slug', 'agent');
        })->where('is_active', true)->count();

        $recentAssignments = [];

        $recentProperties = Property::with(['user', 'agent', 'primaryImage', 'media'])
            ->whereNotNull('agent_id')
            ->orderBy('updated_at', 'desc')
            ->take(3)
            ->get();

        foreach ($recentProperties as $property) {
            $imagePath = $property->primaryImage->file_path
                ?? $property->media->first()?->file_path
                ?? null;
            $recentAssignments[] = [
                'type' => 'property',
                'title' => $property->title ?: 'Annonce immobiliere',
                'subtitle' => trim("{$property->city} - {$property->user?->first_name} {$property->user?->last_name}"),
                'status' => $property->status ?? 'assigned',
                'image' => $imagePath,
            ];
        }

        $recentSearch = SearchRequest::with(['user', 'agent'])
            ->whereIn('status', ['assigned', 'in_progress', 'fulfilled'])
            ->orderBy('updated_at', 'desc')
            ->take(3)
            ->get();

        foreach ($recentSearch as $request) {
            $recentAssignments[] = [
                'type' => 'search',
                'title' => $request->propertyType?->name ?: 'Demande de recherche',
                'subtitle' => trim("{$request->city} - {$request->user?->first_name} {$request->user?->last_name}"),
                'status' => $request->status ?? 'assigned',
            ];
        }

        $recentConstruction = ConstructionProject::with(['user', 'agent'])
            ->whereIn('status', ['in_study', 'in_progress', 'completed', 'closed'])
            ->orderBy('updated_at', 'desc')
            ->take(3)
            ->get();

        foreach ($recentConstruction as $project) {
            $recentAssignments[] = [
                'type' => 'construction',
                'title' => $project->title ?: 'Projet construction',
                'subtitle' => trim("{$project->city} - {$project->user?->first_name} {$project->user?->last_name}"),
                'status' => $project->status ?? 'assigned',
            ];
        }

        return response()->json([
            'success' => true,
            'data' => [
                'properties_total' => $propertiesTotal,
                'properties_pending' => $propertiesPending,
                'properties_assigned' => $propertiesAssigned,
                'search_pending' => $searchPending,
                'search_assigned' => $searchAssigned,
                'construction_pending' => $constructionPending,
                'construction_assigned' => $constructionAssigned,
                'agents_active' => $agentsActive,
                'recent_assignments' => array_slice($recentAssignments, 0, 8),
            ],
        ]);
    }
}
